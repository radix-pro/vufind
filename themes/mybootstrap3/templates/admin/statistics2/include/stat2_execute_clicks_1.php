<?php
/* ************************************************************************ */
/* Count documents clicks                                                   */
/*                                                                          */
/* ************************************************************************ */

IF ($stat_clicks_engine == "Summon"):            /* Came from parent script */
   $summon_prefix_like = "LIKE";
ELSE:
   $summon_prefix_like = "NOT LIKE";
ENDIF;

IF ($stat2_summon_prefix != ""):
    $sql_from_clicks  = "user_stats, user_stats_fields AS user_stats_fields_1,
                                     user_stats_fields AS user_stats_fields_2";

    $sql_where_clicks = "user_stats.id = user_stats_fields_1.id     AND 
                         user_stats_fields_1.field = 'recordId'     AND
                         user_stats.id = user_stats_fields_2.id     AND 
                         user_stats_fields_2.field = 'recordSource' AND
                         user_stats_fields_2.value {$summon_prefix_like} '%{$stat2_summon_prefix}%'";
ELSE:
    $sql_from_clicks  = "user_stats, user_stats_fields AS user_stats_fields_1";

    $sql_where_clicks = "user_stats.id = user_stats_fields_1.id AND 
                         user_stats_fields_1.field = 'recordId'";
ENDIF;

IF ($sql_where_dates != ""):                     /* Came from parent script */
    $sql_where_clicks = "$sql_where_clicks AND ($sql_where_dates)";
ENDIF;

                                                           /* Cliks counter */
IF ($stat2_summon_prefix != ""):
    $tm_1 = TIME();
    $res = MYSQL_QUERY("SELECT COUNT(*) AS clicks_cnt 
                               FROM  $sql_from_clicks 
                               WHERE $sql_where_clicks", $vf_db_conn);

    $clicks_cnt = INTVAL(MYSQL_RESULT($res, 0, "clicks_cnt"));
    MYSQL_FREERESULT($res);
    $tm_1 = TIME() - $tm_1;

    ECHO "<H4><FONT COLOR='$color_txt'>Документы $stat_clicks_engine ($clicks_cnt)</FONT></H4>";
ENDIF;

                                                         /* Clicks top list */
$tm_2 = TIME();
$res = MYSQL_QUERY("SELECT user_stats_fields_1.value AS record_id, 
                           COUNT(*)                  AS record_cnt
                           FROM  $sql_from_clicks
                           WHERE $sql_where_clicks
                           GROUP BY user_stats_fields_1.value
                           ORDER BY record_cnt DESC
                           LIMIT 0, $stat_rows", $vf_db_conn);

$res_len = MYSQL_NUM_ROWS($res);

IF ($res_len <= 0):
    ECHO "<I>Не найдено ни одного просмотренного документа</I>";
    ECHO "<BR>";
ELSE:
    ECHO "<B>$stat_rows наиболее используемых</B>";

    ECHO "<TABLE BORDER='0' WIDTH='' CELLPADDING='0' CELLSPACING='2'
          style='border-collapse:separate; border-spacing:1px; border:2px solid $color_tr2'>";
    ECHO "<style type='text/css'>TD {vertical-align:top}</style>";     /* ! */

    $i=0;
    WHILE ($i < $res_len):
           $record_id  = TRIM(MYSQL_RESULT($res, $i, "record_id"));
           $record_cnt = TRIM(MYSQL_RESULT($res, $i, "record_cnt"));

           $record_url = $stat_record_URL . "/" . $record_id;          /* ! */
           $record_txt = "Показать документ в отдельном окне";

           $percents = ($record_cnt/$clicks_total) * 100.0;  /* From parent */
           $percents = SPRINTF("%00.1f", $percents) . "%";

           IF (STRTOUPPER(GETTYPE($i/2)) == "INTEGER"):
               $tr_color = $color_tr1;
           ELSE:
               $tr_color = $color_tr2;
           ENDIF;

           $i1=$i+1;
           ECHO "<TR !bgcolor='$tr_color'>";
           ECHO "<TD width='2%' style='text-align:right'>{$i1}&nbsp;</TD>";
           ECHO "<TD><a href='$record_url' onClick=\"Stat2_NewWin('$record_url'); return false\" title='$record_txt'>{$record_id}</a>&nbsp;</TD>";
           ECHO "<TD width='10%' style='text-align:center'>{$record_cnt}&nbsp;</TD>";
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
