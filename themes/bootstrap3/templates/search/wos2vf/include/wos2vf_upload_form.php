<?php
/* ************************************************************************ */
/* Form to upload WoS CSV-file                                              */
/*                                                                          */
/* ************************************************************************ */
ECHO "<TABLE BORDER='1' WIDTH='' CELLPADDING='10' CELLSPACING='0' BGCOLOR='$color_ttl'>";
ECHO "<FORM METHOD='POST' ENCTYPE='multipart/form-data' ACTION='$wos2vf_home_url' TARGET='_self'>";
$td_columns = 2;

                                                                    /* FORM */
ECHO "<TR ALIGN='LEFT' VALIGN='TOP'>";
ECHO "<TD colspan='$td_columns'>";
ECHO "<NOBR>";
                                                                   /* Title */
ECHO "<H4><NOBR>Введите исходный CSV-файл, выведенный с сайта WoS</NOBR></H4>";
ECHO "<HR size='0' noshade>";
                                                                  /* Errors */
IF ($upload_err != ""):
    ECHO "<FONT SIZE='-1' COLOR='$color_err'>$upload_err</FONT>";
    ECHO "<BR>";
ENDIF;

ECHO "<INPUT NAME='MAX_FILE_SIZE' TYPE='hidden' VALUE='$upload_files_max_size'>";
ECHO "<INPUT NAME='userfile1' TYPE='file' SIZE='50'>"; /*After max_file_size*/
ECHO "<UL>";
/*ECHO "<FONT SIZE='-1'>";*/
ECHO "<LI>Взять исходный файл с локального диска";
ECHO "<LI>Размер файла - не более $upload_files_max_size байт";
ECHO "<LI>Допустимые форматы: " . STRTOUPPER($upload_files_types);
ECHO "<LI>";
ECHO "<SELECT NAME='csv_sep'>";
      IF ($csv_sep == ""):
          ECHO "<OPTION VALUE='' SELECTED>";
      ELSE:
          ECHO "<OPTION VALUE=''>";
      ENDIF;
      IF ($csv_sep == ";"):
          ECHO "<OPTION VALUE=';' SELECTED>;";
      ELSE:
          ECHO "<OPTION VALUE=';'>;";
      ENDIF;
      IF ($csv_sep == ","):
          ECHO "<OPTION VALUE=',' SELECTED>,";
      ELSE:
          ECHO "<OPTION VALUE=','>,";
      ENDIF;
      IF ($csv_sep == "\t"):
          ECHO "<OPTION VALUE='\t' SELECTED>Tab";
      ELSE:
          ECHO "<OPTION VALUE='\t'>Tab";
      ENDIF;
ECHO "</SELECT>";
ECHO " - CSV разделитель";
/*ECHO "</FONT>";*/
ECHO "</UL>";

                                                                 /* BUTTONS */
ECHO "<HR size='0' noshade>";
ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Загрузить'>";
/**
ECHO "&nbsp;";
ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Отказаться'>";
**/

ECHO "</NOBR>";
ECHO "</TD>";
ECHO "</TR>";


ECHO "</FORM>";
ECHO "</TABLE>";
/* ************************************************************************ */
?>
