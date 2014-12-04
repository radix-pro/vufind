<?php
/* ************************************************************************ */
/* Show statistics for parsed CSV-file                                      */
/* Input:  $wos_csv_arr[], $header_size, $count_{emp,err,ok,all}            */
/*                                                                          */
/* ************************************************************************ */
                                                                /* Subtitle */
ECHO "<H4><FONT COLOR='$color_txt'>Анализ загруженного файла</FONT></H4>";

                                             /* JS: prevent double clicking */
ECHO "<SCRIPT language=\"JavaScript\">
      <!--\n
      var click_counter = 0;

      function Check_click() {  
        if (click_counter == 0)
           {
            click_counter++;
            return true;
           }
        else 
           {
            alert(\"Идет процесс индексирования. Ждите ...\");
            return false;
           }
      }
\n// -->
\n</SCRIPT>";

ECHO "<TABLE BORDER='0' WIDTH='' CELLPADDING='2' CELLSPACING='2'>";
ECHO "<FORM METHOD='POST' ACTION='$wos2vf_home_url' TARGET='_self' OnSubmit='return Check_click()'>";
ECHO "<style type='text/css'>TD {vertical-align: top}</style>";        /* ! */

                                                              /* Statistics */
ECHO "<TR ALIGN='LEFT' VALIGN='TOP' BGCOLOR='$color_ttl'>";
ECHO "<TD colspan='2'><NOBR><B>Для продолжения нажмитке кнопку  \"Выполнить\"</B></NOBR></TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD width='10%'><NOBR><B>Колонок</B></NOBR></TD>";
ECHO "<TD><B>$header_size</B>&nbsp;</TD>";
ECHO "</TR>";

$count_err_percent  = ($count_err / $count_all) * 100.0;
$count_err_percent2 = SPRINTF("%00.2f", STRVAL($count_err_percent));
ECHO "<TR align='left' valign='top'>";
ECHO "<TD><NOBR><B>Строк</B></NOBR></TD>";
ECHO "<TD><B>$count_all</B>";
IF ($count_ok != $count_all):
    ECHO "<BR><NOBR>в том числе:</NOBR>";
    ECHO "<UL>";
    ECHO "<LI><NOBR>заголовок с кодами колонок: <B>1</B></NOBR>";
    IF ($count_emp > 0):
        ECHO "<LI><NOBR>пустых: <B>$count_emp</B></NOBR>";
    ENDIF;
    IF ($count_err > 0):
        ECHO "<LI><NOBR>с ошибками: <B>$count_err ($count_err_percent2 %)</B></NOBR> - отсутствует один из обязательных параметров (ISSN, год, issue/volume, название статьи/издания, уникальный идентификатор WOS)";
    ENDIF;
    ECHO "<LI><NOBR>Статей: <B>$count_ok</B></NOBR>";
    ECHO "</UL>";
ENDIF;
ECHO "</TD>";
ECHO "</TR>";

$count_numbers = COUNT($wos_csv_arr["numbers"]);
ECHO "<TR align='left' valign='top'>";
ECHO "<TD><NOBR><B>Номеров (томов)</NOBR></B></TD>";
ECHO "<TD><B>$count_numbers</B>&nbsp;</TD>";
ECHO "</TR>";

$count_journals = COUNT($wos_csv_arr["journals"]);
ECHO "<TR align='left' valign='top'>";
ECHO "<TD><NOBR><B>Изданий</B></NOBR></TD>";
ECHO "<TD><B>$count_journals</B>&nbsp;</TD>";
ECHO "</TR>";
                                                                 /* Buttons */

ECHO "<TR ALIGN='LEFT' VALIGN='TOP' BGCOLOR='$color_ttl'>";
ECHO "<TD colspan='2' style='background:$color_ttl'><NOBR>";      /* Style! */
IF ($count_err_percent <= $wos2vf_err_percent):
    IF (STRTOLOWER($wos2vf_harvest_commit) == "optimize" ||
        STRTOLOWER($wos2vf_harvest_commit) == "commit"   ):
        $execute_msg = "Предупреждение! Процедура индексирования может занять довольно много времени (до нескольких минут)";
    ELSE:
        $execute_msg = "Запустить процедуру индексирования";
    ENDIF;
    ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Выполнить' TITLE='$execute_msg'>";
    ECHO "&nbsp;";
    ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Отказаться'>";
ELSE:
    ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Отказаться'>";
    ECHO "&nbsp;&nbsp;&nbsp;";
    ECHO "<FONT COLOR='$color_err'>Слишком много строк с ошибками ($count_err_percent2 %). Проверьте исходный файл</FONT>";
ENDIF;
ECHO "</NOBR></TD>";
ECHO "</TR>";

          /* Serialize & post array (will be unserialized in "execute.php") */
$wos_csv_arr_ser = SERIALIZE($wos_csv_arr);
IF (FUNCTION_EXISTS("GZCOMPRESS") && FUNCTION_EXISTS("GZUNCOMPRESS")):
    $wos_csv_arr_ser = GZCOMPRESS($wos_csv_arr_ser, 9);
ENDIF;
$wos_csv_arr_ser = BASE64_ENCODE($wos_csv_arr_ser);
ECHO "<INPUT NAME='wos_csv_arr_ser' TYPE='hidden' VALUE='$wos_csv_arr_ser'>";


ECHO "</FORM>";
ECHO "</TABLE>";
/* ************************************************************************ */
?>                               
