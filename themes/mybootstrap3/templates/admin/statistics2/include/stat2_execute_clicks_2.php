<?php
/* ************************************************************************ */
/* Count documents clicks                                                   */
/*                                                                          */
/* ************************************************************************ */

ECHO '<SCRIPT language="JavaScript"><!--
      function Stat2_NewWin(url) {
      var w = Math.floor(screen.width  * 0.9);
      var h = Math.floor(screen.height * 0.8);
      var options = "width=" + w + ",height=" + h + ",";
      options += "screenX=10,screenY=10,left=10,top=10,";
      options += "resizable=no,scrollbars=yes,status=no,";
      options += "menubar=no,toolbar=no,location=no,directories=no";
      var newWin = window.open(url, "newWin", options);
      newWin.focus();
     }
//--></SCRIPT>';

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
                                                        /* Count all clicks */

$sql_where_clicks = "user_stats.id = user_stats_fields.id AND 
                     user_stats_fields.field = 'recordId'";

IF ($sql_where_dates != ""):
    $sql_where_clicks = "$sql_where_clicks AND ($sql_where_dates)";
ENDIF;

$tm_1 = TIME();
$res = MYSQL_QUERY("SELECT COUNT(*) AS clicks_cnt 
                           FROM  user_stats, user_stats_fields
                           WHERE $sql_where_clicks", $vf_db_conn);

$clicks_total = INTVAL(MYSQL_RESULT($res, 0, "clicks_cnt"));
MYSQL_FREERESULT($res);
$tm_1 = TIME() - $tm_1;
                                                               /* Subtitle */

ECHO "<DIV STYLE='background:$color_tr2; padding:2px'>";
ECHO "<H3><FONT COLOR='$color_txt'>Статистика просмотров ($clicks_total)</FONT></H3>";
ECHO "</DIV>";
                                    /* VuFind (or mixed with Summon) clicks */

$tm_clicks = TIME();

$stat_clicks_engine = "VuFind";
$stat_record_URL    = $this->url('home') . "Record";
INCLUDE ("stat2_execute_clicks_1.php");

$tm_clicks = TIME() - $tm_clicks;
IF (STRTOUPPER($stat2_show_execution_time) == "Y_SEP"):
    ECHO "<I><FONT style='font-size:9px'>Время выполнения: $tm_clicks сек.</FONT></I>";
    ECHO "<BR>";
ENDIF;

                                                           /* Summon clicks */
IF ($stat2_summon_prefix != ""):
    $tm_clicks = TIME();

    ECHO "<BR>";
    $stat_clicks_engine = "Summon";
    $stat_record_URL    = $this->url('home') . "SummonRecord";
    INCLUDE ("stat2_execute_clicks_1.php");

    $tm_clicks = TIME() - $tm_clicks;
    IF (STRTOUPPER($stat2_show_execution_time) == "Y_SEP"):
        ECHO "<I><FONT style='font-size:9px'>Время выполнения: $tm_clicks сек.</FONT></I>";
    ENDIF;
ENDIF;

/* ************************************************************************ */
?>                               
