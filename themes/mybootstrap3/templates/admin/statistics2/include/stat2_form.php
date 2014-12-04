<?php
/* ************************************************************************ */
/* Statistics form                                                          */
/*                                                                          */
/* ************************************************************************ */
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
            alert(\"Идет обработка статистики. Ждите ...\");
            return false;
           }
      }
\n// -->
\n</SCRIPT>";                                /* JS: prevent double clicking */

                                                                    /* Form */

ECHO "<FORM METHOD='POST' ACTION='$stat2_home_url' TARGET='_self' OnSubmit='return Check_click()'>";
ECHO "<DIV style='background:$color_tr2; color:#000000; padding:5px; !width:50%'>";

                                                                  /* Errors */
IF ($stat2_form_err != ""):
    ECHO "<FONT SIZE='-1' COLOR='$color_err'>$stat2_form_err</FONT>";
    ECHO "<BR>";
ENDIF;
                                                                  /* Fields */

ECHO "<TABLE BORDER='1' WIDTH='' CELLPADDING='10' CELLSPACING='0' BGCOLOR='$color_tr2'>";
ECHO "<style type='text/css'>TABLE {margin:0}</style>";               /* ! */
ECHO "<style type='text/css'>TD {vertical-align:top}</style>";        /* ! */
ECHO "<TR>";
ECHO "<TD width='1%'><NOBR>";
  ECHO "Период (д.м.гггг)";
  ECHO "<table border='0' cellpadding='0' cellspacing='0'>";
  $td_style = "style='margin:0; padding:0; background:$color_tr2'";
  ECHO "<tr><td $td_style>с  </td><td $td_style><INPUT  TYPE='text' NAME='stat_dt_1' SIZE='10' MAXLENGTH='10' VALUE='$stat_dt_1'></td></tr>";
  ECHO "<tr><td $td_style>по </td><td $td_style><INPUT TYPE='text' NAME='stat_dt_2' SIZE='10' MAXLENGTH='10' VALUE='$stat_dt_2'></td></tr>";
  ECHO "</table>";
ECHO "</NOBR></TD>";

ECHO "<TD><NOBR>";
  ECHO "Что выводить";
  ECHO "<BR>";

  IF ($stat_rep_searches == "Y"):
      ECHO "<INPUT NAME='stat_rep_searches' TYPE='checkbox' VALUE='Y' CHECKED>";
  ELSE:
      ECHO "<INPUT NAME='stat_rep_searches' TYPE='checkbox' VALUE='Y'>";
  ENDIF;
  ECHO " Статистика поисковых запросов";
  ECHO "<BR>";

  IF ($stat_rep_clicks == "Y"):
      ECHO "<INPUT NAME='stat_rep_clicks' TYPE='checkbox' VALUE='Y' CHECKED>";
  ELSE:
      ECHO "<INPUT NAME='stat_rep_clicks' TYPE='checkbox' VALUE='Y'>";
  ENDIF;
  ECHO " Статистика просмотров (\"кликов\")";
  ECHO "<BR>";

  IF ($stat_rep_fulltext == "Y"):
      ECHO "<INPUT NAME='stat_rep_fulltext' TYPE='checkbox' VALUE='Y' CHECKED>";
  ELSE:
      ECHO "<INPUT NAME='stat_rep_fulltext' TYPE='checkbox' VALUE='Y'>";
  ENDIF;
  ECHO " Статистика полных текстов";
  ECHO "<BR>";

  IF ($stat_rep_browsers == "Y"):
      ECHO "<INPUT NAME='stat_rep_browsers' TYPE='checkbox' VALUE='Y' CHECKED>";
  ELSE:
      ECHO "<INPUT NAME='stat_rep_browsers' TYPE='checkbox' VALUE='Y'>";
  ENDIF;
  ECHO " Статистика браузеров";
ECHO "</NOBR></TD>";
ECHO "</TR>";

                                                                 /* Buttons */
ECHO "<TR>";
$td_style = "style='background:$color_tr2; vertical-align:middle'";
ECHO "<TD $td_style><NOBR>";
  ECHO "Сколько 1-х строк показывать &nbsp; &nbsp; &nbsp; &nbsp;";
  ECHO "<BR>";

  ECHO "<SELECT NAME='stat_rows'>";
        IF ($stat_rows == ""):
            ECHO "<OPTION VALUE='' SELECTED>";
        ELSE:
            ECHO "<OPTION VALUE=''>";
        ENDIF;
        IF ($stat_rows == "5"):
            ECHO "<OPTION VALUE='5' SELECTED>5";
        ELSE:
            ECHO "<OPTION VALUE='5'>5";
        ENDIF;
        IF ($stat_rows == "10"):
            ECHO "<OPTION VALUE='10' SELECTED>10";
        ELSE:
            ECHO "<OPTION VALUE='10'>10";
        ENDIF;
        IF ($stat_rows == "20"):
            ECHO "<OPTION VALUE='20' SELECTED>20";
        ELSE:
            ECHO "<OPTION VALUE='20'>20";
        ENDIF;
        IF ($stat_rows == "30"):
            ECHO "<OPTION VALUE='30' SELECTED>30";
        ELSE:
            ECHO "<OPTION VALUE='30'>30";
        ENDIF;
  ECHO "</SELECT>";
ECHO "</NOBR></TD>";

ECHO "<TD $td_style>";
    ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Выполнить' TITLE='Предупреждение! Обработка может занять довольно много времени (до нескольких минут)'>";
ECHO "</TD>";
ECHO "</TR>";
ECHO "</TABLE>";


ECHO "</FORM>";
ECHO "</DIV>";

ECHO "<BR>";
/* ************************************************************************ */
?>
