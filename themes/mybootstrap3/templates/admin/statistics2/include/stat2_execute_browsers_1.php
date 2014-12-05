<?php
/* ************************************************************************ */
/* Count browsers                                                           */
/*                                                                          */
/* ************************************************************************ */

IF ($stat_browsers_engine == "Summon"):          /* Came from parent script */
   $summon_prefix_like = "LIKE";
ELSE:
   $summon_prefix_like = "NOT LIKE";
ENDIF;

IF ($stat2_summon_prefix != ""):
    $sql_from_browsers  = "user_stats, user_stats_fields AS user_stats_fields_1,
                                       user_stats_fields AS user_stats_fields_2";

    $sql_where_browsers = "(user_stats.id = user_stats_fields_1.id     AND 
                            user_stats_fields_1.field = 'phrase'       AND
                            user_stats.id = user_stats_fields_2.id     AND 
                            user_stats_fields_2.field = 'searchSource' AND
                            user_stats_fields_2.value {$summon_prefix_like} '%{$stat2_summon_prefix}%')
                            OR
                           (user_stats.id = user_stats_fields_1.id     AND 
                            user_stats_fields_1.field = 'recordId'     AND
                            user_stats.id = user_stats_fields_2.id     AND 
                            user_stats_fields_2.field = 'recordSource' AND
                            user_stats_fields_2.value {$summon_prefix_like} '%{$stat2_summon_prefix}%')";
ELSE:
    $sql_from_browsers  = "user_stats";
    $sql_where_browsers = "2 > 1";                     /* For std. scheme ! */
ENDIF;

IF ($sql_where_dates != ""):                     /* Came from parent script */
    $sql_where_browsers = "($sql_where_browsers) AND ($sql_where_dates)";
ENDIF;

                                                        /* Browsers counter */
IF ($stat2_summon_prefix != ""):
    $tm_1 = TIME();
    $res = MYSQL_QUERY("SELECT COUNT(DISTINCT(user_stats.browser)) AS browsers_cnt 
                               FROM  $sql_from_browsers 
                               WHERE $sql_where_browsers", $vf_db_conn);

    $browsers_cnt = INTVAL(MYSQL_RESULT($res, 0, "browsers_cnt"));
    MYSQL_FREERESULT($res);
    $tm_1 = TIME() - $tm_1;

    ECHO "<H4><FONT COLOR='$color_txt'>Браузеры $stat_browsers_engine ($browsers_cnt)</FONT></H4>";
ENDIF;

                                                       /* Browsers top list */
$tm_2 = TIME();
$res = MYSQL_QUERY("SELECT user_stats.browser AS browser, 
                           COUNT(*)           AS browser_cnt
                           FROM  $sql_from_browsers
                           WHERE $sql_where_browsers
                           GROUP BY user_stats.browser
                           ORDER BY browser_cnt DESC
                           LIMIT 0, $stat_rows", $vf_db_conn);

$res_len = MYSQL_NUM_ROWS($res);

IF ($res_len <= 0):
    ECHO "<I>Не найдено ни одного браузера</I>";
    ECHO "<BR>";
ELSE:
    ECHO "<B>$stat_rows наиболее используемых</B>";

    ECHO "<TABLE BORDER='0' WIDTH='' CELLPADDING='0' CELLSPACING='2'
          style='border-collapse:separate; border-spacing:1px; border:2px solid $color_tr2'>";
    ECHO "<style type='text/css'>TD {vertical-align:top}</style>";     /* ! */

    $i=0;
    WHILE ($i < $res_len):
           $browser     = TRIM(MYSQL_RESULT($res, $i, "browser"));
           $browser_cnt = TRIM(MYSQL_RESULT($res, $i, "browser_cnt"));

           $percents = ($browser_cnt/$records_cnt) * 100.0;  /* From parent */
           $percents = SPRINTF("%00.1f", $percents) . "%";

           IF (STRTOUPPER(GETTYPE($i/2)) == "INTEGER"):
               $tr_color = $color_tr1;
           ELSE:
               $tr_color = $color_tr2;
           ENDIF;

           $i1=$i+1;
           ECHO "<TR !bgcolor='$tr_color'>";
           ECHO "<TD width='2%' style='text-align:right'>{$i1}&nbsp;</TD>";
           ECHO "<TD>{$browser}&nbsp;</TD>";
           ECHO "<TD width='10%' style='text-align:center'>{$browser_cnt}&nbsp;</TD>";
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
