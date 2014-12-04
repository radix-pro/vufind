<?php
/* ************************************************************************ */
/* Count search phrases                                                     */
/*                                                                          */
/* ************************************************************************ */

IF ($stat_searches_engine == "Summon"):          /* Came from parent script */
   $summon_prefix_like = "LIKE";
ELSE:
   $summon_prefix_like = "NOT LIKE";
ENDIF;

IF ($stat2_summon_prefix != ""):
    $sql_from_phrases  = "user_stats, user_stats_fields AS user_stats_fields_1,
                                      user_stats_fields AS user_stats_fields_2";

    $sql_where_phrases = "user_stats.id = user_stats_fields_1.id     AND 
                          user_stats_fields_1.field = 'phrase'       AND
                          user_stats.id = user_stats_fields_2.id     AND 
                          user_stats_fields_2.field = 'searchSource' AND
                          user_stats_fields_2.value {$summon_prefix_like} '%{$stat2_summon_prefix}%'";
ELSE:
    $sql_from_phrases  = "user_stats, user_stats_fields AS user_stats_fields_1";

    $sql_where_phrases = "user_stats.id = user_stats_fields_1.id AND 
                          user_stats_fields_1.field = 'phrase'";
ENDIF;

IF ($sql_where_dates != ""):                     /* Came from parent script */
    $sql_where_phrases = "$sql_where_phrases AND ($sql_where_dates)";
ENDIF;

                                                         /* Phrases counter */
IF ($stat2_summon_prefix != ""):
    $tm_1 = TIME();
    $res = MYSQL_QUERY("SELECT COUNT(*) AS phrases_cnt 
                               FROM  $sql_from_phrases 
                               WHERE $sql_where_phrases", $vf_db_conn);

    $phrases_cnt = INTVAL(MYSQL_RESULT($res, 0, "phrases_cnt"));
    MYSQL_FREERESULT($res);
    $tm_1 = TIME() - $tm_1;

    ECHO "<H4><FONT COLOR='$color_txt'>Запросы $stat_searches_engine ($phrases_cnt)</FONT></H4>";
ENDIF;

                                                        /* Phrases top list */
$tm_2 = TIME();
$res = MYSQL_QUERY("SELECT user_stats_fields_1.value AS phrase, 
                           COUNT(*)                  AS phrase_cnt
                           FROM  $sql_from_phrases
                           WHERE $sql_where_phrases
                           GROUP BY user_stats_fields_1.value
                           ORDER BY phrase_cnt DESC
                           LIMIT 0, $stat_rows", $vf_db_conn);

$res_len = MYSQL_NUM_ROWS($res);

IF ($res_len <= 0):
    ECHO "<I>Не найдено ни одного поискового запроса</I>";
    ECHO "<BR>";
ELSE:
    ECHO "<B>$stat_rows наиболее используемых</B>";

    ECHO "<TABLE BORDER='0' WIDTH='' CELLPADDING='0' CELLSPACING='2'
          style='border-collapse:separate; border-spacing:1px; border:2px solid $color_tr2'>";
    ECHO "<style type='text/css'>TD {vertical-align:top}</style>";     /* ! */

    $i=0;
    WHILE ($i < $res_len):
           $phrase     = TRIM(MYSQL_RESULT($res, $i, "phrase"));
           $phrase_cnt = TRIM(MYSQL_RESULT($res, $i, "phrase_cnt"));

           $percents = ($phrase_cnt/$phrases_total) * 100.0; /* From parent */
           $percents = SPRINTF("%00.1f", $percents) . "%";

           IF (STRTOUPPER(GETTYPE($i/2)) == "INTEGER"):
               $tr_color = $color_tr1;
           ELSE:
               $tr_color = $color_tr2;
           ENDIF;

           $i1=$i+1;
           ECHO "<TR !bgcolor='$tr_color'>";
           ECHO "<TD width='2%' style='text-align:right'>{$i1}&nbsp;</TD>";
           ECHO "<TD>{$phrase}&nbsp;</TD>";
           ECHO "<TD width='10%' style='text-align:center'>{$phrase_cnt}&nbsp;</TD>";
           ECHO "<TD width='5%'  style='text-align:center'>{$percents}&nbsp;</TD>";
           ECHO "</TR>";

           $i++;
    ENDWHILE;

    ECHO "</TABLE>";
ENDIF;

MYSQL_FREE_RESULT($res);
$tm_2 = TIME() - $tm_2;

/* ************************************************************************ */
?>                               
