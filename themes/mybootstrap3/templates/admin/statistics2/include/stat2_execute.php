<?php
/* ************************************************************************ */
/* Build & output statistics                                                */
/*                                                                          */
/* ************************************************************************ */
$tm_total = TIME();

                                                             /* (SQL) Where */
$stat2_sql_where = "";

IF ($stat_dt_1 != "" || $stat_dt_2 != ""):
    IF ($stat_dt_1 == ""):
        $dt_where = "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') <= '$stat_dt_2ymd'";
    ENDIF;
    IF ($stat_dt_2 == ""):
        $dt_where = "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') >= '$stat_dt_1ymd'";
    ENDIF;
    IF ($stat_dt_1 != "" && $stat_dt_2 != ""):
        $dt_where = "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') >= '$stat_dt_1ymd' AND "
                  . "DATE_FORMAT(user_stats.datestamp, '%Y-%m-%d') <= '$stat_dt_2ymd'";
    ENDIF;

    IF ($stat2_sql_where != ""):
        $stat2_sql_where = "$stat2_sql_where AND ($dt_where)";
    ELSE:
        $stat2_sql_where = $dt_where;
    ENDIF;
ENDIF;

IF ($stat2_sql_where != ""):
    $stat2_sql_where = "WHERE $stat2_sql_where";
ENDIF;


IF ($stat_rep_searches != "" || $stat_rep_clicks != "" || $stat_rep_browsers != ""):
    $res = MYSQL_QUERY("SELECT COUNT(*) AS records_cnt,
                               DATE_FORMAT(MIN(datestamp), '%d.%m.%Y \'%H:%i:%S') AS date_min, 
                               DATE_FORMAT(MAX(datestamp), '%d.%m.%Y \'%H:%i:%S') AS date_max,
                               DATE_FORMAT(MIN(datestamp), '%Y.%m.%d \'%H:%i:%S') AS date_min_2,
                               DATE_FORMAT(MAX(datestamp), '%Y.%m.%d \'%H:%i:%S') AS date_max_2
                               FROM user_stats 
                               $stat2_sql_where", $vf_db_conn);

    $records_cnt = INTVAL(MYSQL_RESULT($res, 0, "records_cnt"));
    $date_min    = TRIM  (MYSQL_RESULT($res, 0, "date_min"));
    $date_max    = TRIM  (MYSQL_RESULT($res, 0, "date_max"));
    $date_min_2  = TRIM  (MYSQL_RESULT($res, 0, "date_min_2"));
    $date_max_2  = TRIM  (MYSQL_RESULT($res, 0, "date_max_2"));

    MYSQL_FREERESULT($res);
ELSE:
    $records_cnt = 0;
    $date_min    = "";
    $date_max    = "";
    $date_min_2  = "";
    $date_max_2  = "";
ENDIF;

                                        /* Let consider fulltext statistics */
IF ($stat_rep_fulltext != ""):
    $stat2_sql_where_full = STR_REPLACE("user_stats.datestamp", "user_stats_fulltext.date_time", $stat2_sql_where);

    $res = MYSQL_QUERY("SELECT COUNT(*) AS fulltext_cnt,
                               DATE_FORMAT(MIN(date_time), '%d.%m.%Y \'%H:%i:%S') AS date_min_full, 
                               DATE_FORMAT(MAX(date_time), '%d.%m.%Y \'%H:%i:%S') AS date_max_full,
                               DATE_FORMAT(MIN(date_time), '%Y.%m.%d \'%H:%i:%S') AS date_min_full_2, 
                               DATE_FORMAT(MAX(date_time), '%Y.%m.%d \'%H:%i:%S') AS date_max_full_2
                               FROM user_stats_fulltext 
                               $stat2_sql_where_full", $vf_db_conn);

    $fulltext_cnt    = INTVAL(MYSQL_RESULT($res, 0, "fulltext_cnt"));
    $date_min_full   = TRIM  (MYSQL_RESULT($res, 0, "date_min_full"));
    $date_max_full   = TRIM  (MYSQL_RESULT($res, 0, "date_max_full"));
    $date_min_full_2 = TRIM  (MYSQL_RESULT($res, 0, "date_min_full_2"));
    $date_max_full_2 = TRIM  (MYSQL_RESULT($res, 0, "date_max_full_2"));

    MYSQL_FREERESULT($res);

    IF ($fulltext_cnt > 0):
        $records_cnt = $records_cnt + $fulltext_cnt;
    ENDIF;

    IF ($date_min_full_2 != "" && $date_max_full_2 != ""):
        IF ($date_min_2 == "" || $date_min_2 > $date_min_full_2):
            $date_min = $date_min_full;
        ENDIF;
        IF ($date_max_2 == "" || $date_max_2 < $date_max_full_2):
            $date_max = $date_max_full;
        ENDIF;
    ENDIF;
ENDIF;


IF ($records_cnt < 1 || $date_min == "" || $date_max == ""):
    ECHO "<I>По запросу ничего не найдено ...</I>";
ELSE:
    ECHO "<H4><NOBR>$records_cnt записей в журнале &nbsp; [ $date_min - $date_max ]</NOBR></H4>";

    IF ($stat_rep_searches != ""):
        ECHO "<H3>* * *</H3>";
        INCLUDE ("stat2_execute_searches_2.php");
    ENDIF;

    IF ($stat_rep_clicks != ""):
        ECHO "<H3>* * *</H3>";
        INCLUDE ("stat2_execute_clicks_2.php");
    ENDIF;

    IF ($stat_rep_fulltext != ""):
        ECHO "<H3>* * *</H3>";
        INCLUDE ("stat2_execute_fulltext_2.php");
    ENDIF;

    IF ($stat_rep_browsers != ""):
        ECHO "<H3>* * *</H3>";
        INCLUDE ("stat2_execute_browsers_2.php");
    ENDIF;

    $tm_total = TIME() - $tm_total;
    IF (STRTOUPPER($stat2_show_execution_time) == "Y_TOT"):
        ECHO "<BR>";
        ECHO "<I><FONT style='font-size:10px'>Время выполнения: $tm_total сек.</FONT></I>";
    ENDIF;
ENDIF;

ECHO "<H3>* * *</H3>";

/* ************************************************************************ */
?>                               
