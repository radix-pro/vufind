<?php
/* ************************************************************************ */
/* Form to upload eLibrary XML-file                                         */
/*                                                                          */
/* ************************************************************************ */
ECHO "<TABLE BORDER='1' WIDTH='' CELLPADDING='10' CELLSPACING='0' BGCOLOR='$color_ttl'>";
ECHO "<FORM METHOD='POST' ENCTYPE='multipart/form-data' ACTION='$elib2vf_home_url' TARGET='_self'>";
$td_columns = 2;

                                                                    /* FORM */
ECHO "<TR ALIGN='LEFT' VALIGN='TOP'>";
ECHO "<TD colspan='$td_columns'>";
ECHO "<NOBR>";
                                                                   /* Title */
ECHO "<H4><NOBR>Введите исходный XML-файл</NOBR></H4>";
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
ECHO "<LI>Взять исходный XML-файл с локального диска";
ECHO "<LI>Размер файла - не более $upload_files_max_size байт";
ECHO "<LI>Допустимые форматы: " . STRTOUPPER($upload_files_types);

                                       /* Show journals list for SuperUsers */
IF ($elib2vf_access_arr["$vf_userlogin"]["SuperUser"] == "Y"):
    ECHO "<LI>Журнал: ";
    ECHO "<SELECT NAME='select_ISSN' style='width:400'>";
    IF ($select_ISSN == ""):
        ECHO "<OPTION VALUE='' SELECTED>";
    ELSE:
        ECHO "<OPTION VALUE=''>";
    ENDIF;

    $elib2vf_editors_arr = @FILE($elib2vf_editors_file);
    $l=0;
    WHILE ($l < COUNT($elib2vf_editors_arr)):
           $line = TRIM($elib2vf_editors_arr[$l]);

           IF (SUBSTR($line, 0, 1) == "#"):    /* Begin with "#" => comment */
               $l++;
               CONTINUE;
           ENDIF;

           $pos_sep = STRPOS($line, $elib2vf_editors_sep);
           IF ($pos_sep === false):
               $l++;
               CONTINUE;
           ENDIF;

           $line_sep_arr = EXPLODE($elib2vf_editors_sep, $line);
           $journal_issn  = TRIM($line_sep_arr[1]);
           $journal_title = TRIM($line_sep_arr[3]);
           $journal_title = "ISSN: $journal_issn $journal_title";

           IF (ISSET($journal_issn_arr["$journal_issn"])):  /* Already used */
               $l++;
               CONTINUE;
           ELSE:
               $journal_issn_arr["$journal_issn"] = $journal_title;
           ENDIF;

           IF ($journal_issn == $select_ISSN):
               ECHO "<OPTION VALUE='$journal_issn' SELECTED>$journal_title";
           ELSE:
               ECHO "<OPTION VALUE='$journal_issn'>$journal_title";
           ENDIF;

           $l++;
    ENDWHILE;
    ECHO "</SELECT>";
ENDIF;
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
