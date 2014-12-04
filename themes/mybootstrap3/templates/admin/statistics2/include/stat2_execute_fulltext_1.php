<?php
/* ************************************************************************ */
/* Count fulltext clicks                                                    */
/*                                                                          */
/* ************************************************************************ */

IF ($stat_fulltext_engine == "Summon"):          /* Came from parent script */
   $summon_prefix_like = "LIKE";
ELSE:
   $summon_prefix_like = "NOT LIKE";
ENDIF;

IF ($stat2_summon_prefix != ""):
    $sql_from_fulltext  = "user_stats_fulltext";
    $sql_where_fulltext = "user_stats_fulltext.engine {$summon_prefix_like} '%{$stat2_summon_prefix}%'";
ELSE:
    $sql_from_fulltext  = "user_stats_fulltext";
    $sql_where_fulltext = "";
ENDIF;

IF ($sql_where_dates != ""):                     /* Came from parent script */
    IF ($sql_where_fulltext != ""):
        $sql_where_fulltext = "$sql_where_fulltext AND ($sql_where_dates)";
    ELSE:
        $sql_where_fulltext = $sql_where_dates;
    ENDIF;
ENDIF;

                                                        /* Fulltext counter */
IF ($stat2_summon_prefix != ""):
    $tm_1 = TIME();
    $res = MYSQL_QUERY("SELECT COUNT(*) AS fulltext_cnt 
                               FROM  $sql_from_fulltext 
                               WHERE $sql_where_fulltext", $vf_db_conn);

    $fulltext_cnt = INTVAL(MYSQL_RESULT($res, 0, "fulltext_cnt"));
    MYSQL_FREERESULT($res);
    $tm_1 = TIME() - $tm_1;

    ECHO "<H4><FONT COLOR='$color_txt'>Полные тексты $stat_fulltext_engine ($fulltext_cnt)</FONT></H4>";
ENDIF;

                                                       /* Fulltext top list */
$tm_2 = TIME();
$res = MYSQL_QUERY("SELECT user_stats_fulltext.fulltext_url   AS fulltext_url, 
                           COUNT(*)                           AS fulltext_cnt,
                           MAX(user_stats_fulltext.record_id) AS record_id 
                           FROM  $sql_from_fulltext
                           WHERE $sql_where_fulltext
                           GROUP BY user_stats_fulltext.fulltext_url
                           ORDER BY fulltext_cnt DESC
                           LIMIT 0, $stat_rows", $vf_db_conn);

$res_len = MYSQL_NUM_ROWS($res);

IF ($res_len <= 0):
    ECHO "<I>Не найдено ни одного просмотренного полного текста</I>";
    ECHO "<BR>";
ELSE:
    ECHO "<B>$stat_rows наиболее используемых</B>";

    ECHO "<TABLE BORDER='0' WIDTH='' CELLPADDING='0' CELLSPACING='2'
          style='border-collapse:separate; border-spacing:1px; border:2px solid $color_tr2'>";
    ECHO "<style type='text/css'>TD {vertical-align:top}</style>";     /* ! */

    $i=0;
    WHILE ($i < $res_len):
           $fulltext_url = TRIM(MYSQL_RESULT($res, $i, "fulltext_url"));
           $fulltext_cnt = TRIM(MYSQL_RESULT($res, $i, "fulltext_cnt"));
           $record_id    = TRIM(MYSQL_RESULT($res, $i, "record_id"));

           $record_url   = $stat_record_URL ."/". $record_id; /*From parent */
           $record_txt   = "Показать библиографическое описание VuFind в отдельном окне";

           $fulltext_txt = "Показать полный текст в отдельном окне";
           IF (STRLEN($fulltext_url) > 100):
               $fulltext_label = SUBSTR($fulltext_url, 0, 97) . "...";
           ELSE:
               $fulltext_label = $fulltext_url;
           ENDIF;

           $percents = ($fulltext_cnt/$fulltext_total) * 100.0;   /* Parent */
           $percents = SPRINTF("%00.1f", $percents) . "%";

           IF (STRTOUPPER(GETTYPE($i/2)) == "INTEGER"):
               $tr_color = $color_tr1;
           ELSE:
               $tr_color = $color_tr2;
           ENDIF;

           $i1=$i+1;
           ECHO "<TR !bgcolor='$tr_color'>";
           ECHO "<TD width='2%' style='text-align:right'>{$i1}&nbsp;</TD>";
           ECHO "<TD><a href='$fulltext_url' onClick=\"Stat2_NewWin('$fulltext_url'); return false\" title='$fulltext_txt'>{$fulltext_label}</a>"
              . "<BR>&gt;&nbsp;&nbsp;<a href='$record_url'   onClick=\"Stat2_NewWin('$record_url');   return false\" title='$record_txt'>Запись VuFind</a>"
              . "</TD>";
           ECHO "<TD width='10%' style='text-align:center'>{$fulltext_cnt}&nbsp;</TD>";
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
