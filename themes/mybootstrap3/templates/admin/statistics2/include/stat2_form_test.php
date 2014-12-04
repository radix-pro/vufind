<?php
/* ************************************************************************ */
/* Perform fields, posted from stat. form                                   */
/*                                                                          */
/* ************************************************************************ */
$stat_dt_1 = STRIPSLASHES(TRIM($_POST["stat_dt_1"]));
$stat_dt_2 = STRIPSLASHES(TRIM($_POST["stat_dt_2"]));
$stat_rows = STRIPSLASHES(TRIM($_POST["stat_rows"]));

$stat_rep_searches = STRIPSLASHES(TRIM($_POST["stat_rep_searches"]));
$stat_rep_clicks   = STRIPSLASHES(TRIM($_POST["stat_rep_clicks"]));
$stat_rep_fulltext = STRIPSLASHES(TRIM($_POST["stat_rep_fulltext"]));
$stat_rep_browsers = STRIPSLASHES(TRIM($_POST["stat_rep_browsers"]));


$stat2_form_err = "";
                                                              /* Test dates */
IF ($stat_dt_1 != ""):
    $dt_arr = EXPLODE("." ,$stat_dt_1);
    $dt_d   = INTVAL($dt_arr[0]);
    $dt_m   = INTVAL($dt_arr[1]);
    $dt_y   = INTVAL($dt_arr[2]);
    IF ($dt_y <= 2000 || $dt_y >= 2038 || (!CHECKDATE($dt_m, $dt_d, $dt_y))):
        $stat2_form_err .= "Ошибка: неправильная дата #1 <B><I>$stat_dt_1</I></B><BR>";
    ELSE:
        $dt_d = SPRINTF("%02d", $dt_d);                       /* '1' ->'01' */
        $dt_m = SPRINTF("%02d", $dt_m);
        $stat_dt_1    = "{$dt_d}.{$dt_m}.{$dt_y}";
        $stat_dt_1ymd = "{$dt_y}-{$dt_m}-{$dt_d}";
    ENDIF;
ENDIF;

IF ($stat_dt_2 != ""):
    $dt_arr = EXPLODE("." ,$stat_dt_2);
    $dt_d   = INTVAL($dt_arr[0]);
    $dt_m   = INTVAL($dt_arr[1]);
    $dt_y   = INTVAL($dt_arr[2]);
    IF ($dt_y <= 2000 || $dt_y >= 2038 || (!CHECKDATE($dt_m, $dt_d, $dt_y))):
        $stat2_form_err .= "Ошибка: неправильная дата #2 <B><I>$stat_dt_2</I></B><BR>";
    ELSE:
        $dt_d = SPRINTF("%02d", $dt_d);
        $dt_m = SPRINTF("%02d", $dt_m);
        $stat_dt_2    = "{$dt_d}.{$dt_m}.{$dt_y}";
        $stat_dt_2ymd = "{$dt_y}-{$dt_m}-{$dt_d}";
    ENDIF;
ENDIF;

IF ($stat2_form_err == "" 
    && ($stat_dt_1 != "" && $stat_dt_2 != "") 
    && ($stat_dt_1ymd > $stat_dt_2ymd)):
    $stat2_form_err .= "Ошибка: начальная дата больше конечной <NOBR><B><I>$stat_dt_1 > $stat_dt_2</I></B></NOBR><BR>";
ENDIF;
                                                            /* Test reports */

IF ($stat_rep_searches == "" && $stat_rep_clicks   == "" && 
    $stat_rep_fulltext == "" && $stat_rep_browsers == "" ):
    $stat2_form_err .= "Ошибка: не указано, какие статистические данные выводить<BR>";
ENDIF;
                                                            /* Test reports */
/* ************************************************************************ */
?>                               
