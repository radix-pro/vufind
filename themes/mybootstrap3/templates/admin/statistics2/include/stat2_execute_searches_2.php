<?php
/* ************************************************************************ */
/* Count search phrases                                                     */
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
                                      /* Count all search phrases (queries) */

$sql_where_phrases = "user_stats.id = user_stats_fields.id AND 
                      user_stats_fields.field = 'phrase'";

IF ($sql_where_dates != ""):
    $sql_where_phrases = "$sql_where_phrases AND ($sql_where_dates)";
ENDIF;

$tm_1 = TIME();
$res = MYSQL_QUERY("SELECT COUNT(*) AS phrases_cnt 
                           FROM  user_stats, user_stats_fields
                           WHERE $sql_where_phrases", $vf_db_conn);

$phrases_total = INTVAL(MYSQL_RESULT($res, 0, "phrases_cnt"));
MYSQL_FREERESULT($res);
$tm_1 = TIME() - $tm_1;
                                                               /* Subtitle */

ECHO "<DIV STYLE='background:$color_tr2; padding:2px'>";
ECHO "<H3><FONT COLOR='$color_txt'>Статистика поисковых запросов ($phrases_total)</FONT></H3>";
ECHO "</DIV>";
                                  /* VuFind (or mixed with Summon) searches */

$tm_searches = TIME();

$stat_searches_engine = "VuFind";
INCLUDE ("stat2_execute_searches_1.php");

$tm_searches = TIME() - $tm_searches;
IF (STRTOUPPER($stat2_show_execution_time) == "Y_SEP"):
    ECHO "<I><FONT style='font-size:9px'>Время выполнения: $tm_searches сек.</FONT></I>";
    ECHO "<BR>";
ENDIF;

                                                         /* Summon searches */
IF ($stat2_summon_prefix != ""):
    $tm_searches = TIME();

    ECHO "<BR>";
    $stat_searches_engine = "Summon";
    INCLUDE ("stat2_execute_searches_1.php");

    $tm_searches = TIME() - $tm_searches;
    IF (STRTOUPPER($stat2_show_execution_time) == "Y_SEP"):
        ECHO "<I><FONT style='font-size:9px'>Время выполнения: $tm_searches сек.</FONT></I>";
    ENDIF;
ENDIF;

/* ************************************************************************ */
?>                               
