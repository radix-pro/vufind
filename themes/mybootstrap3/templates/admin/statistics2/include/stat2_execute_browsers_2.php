<?php
/* ************************************************************************ */
/* Count browsers                                                           */
/*                                                                          */
/* ************************************************************************ */

                                                             /* Common part */
$sql_where_dates = "";

IF ($stat_dt_1 != "" || $stat_dt_2 != ""):
    IF ($stat_dt_1 == ""):
        $sql_where_dates = "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') <= '$stat_dt_2ymd'";
    ENDIF;
    IF ($stat_dt_2 == ""):
        $sql_where_dates = "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') >= '$stat_dt_1ymd'";
    ENDIF;
    IF ($stat_dt_1 != "" && $stat_dt_2 != ""):
        $sql_where_dates = "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') >= '$stat_dt_1ymd' AND "
                         . "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') <= '$stat_dt_2ymd'";
    ENDIF;
ENDIF;
                                                      /* Count all browsers */

IF ($sql_where_dates != ""):
    $sql_where_browsers = $sql_where_dates;
ELSE:
    $sql_where_browsers = "2 > 1";                     /* For std. scheme ! */
ENDIF;

$tm_1 = TIME();
$res = MYSQL_QUERY("SELECT COUNT(DISTINCT(browser)) AS browsers_cnt 
                           FROM  user_stats
                           WHERE $sql_where_browsers", $vf_db_conn);

$browsers_cnt = INTVAL(MYSQL_RESULT($res, 0, "browsers_cnt"));
MYSQL_FREERESULT($res);
$tm_1 = TIME() - $tm_1;
                                                                /* Subtitle */

ECHO "<DIV STYLE='background:$color_tr2; padding:2px'>";
ECHO "<H3><FONT COLOR='$color_txt'>Статистика браузеров ($browsers_cnt)</FONT></H3>";
ECHO "</DIV>";
                                  /* VuFind (or mixed with Summon) browsers */

$tm_browsers = TIME();

$stat_browsers_engine = "VuFind";
INCLUDE ("stat2_execute_browsers_1.php");

$tm_browsers = TIME() - $tm_browsers;
IF (STRTOUPPER($stat2_show_execution_time) == "Y_SEP"):
    ECHO "<I><FONT style='font-size:9px'>Время выполнения: $tm_browsers сек.</FONT></I>";
    ECHO "<BR>";
ENDIF;

                                                         /* Summon browsers */
IF ($stat2_summon_prefix != ""):
    $tm_browsers = TIME();

    ECHO "<BR>";
    $stat_browsers_engine = "Summon";
    INCLUDE ("stat2_execute_browsers_1.php");

    $tm_browsers = TIME() - $tm_browsers;
    IF (STRTOUPPER($stat2_show_execution_time) == "Y_SEP"):
        ECHO "<I><FONT style='font-size:9px'>Время выполнения: $tm_browsers сек.</FONT></I>";
    ENDIF;
ENDIF;

/* ************************************************************************ */
?>                               
