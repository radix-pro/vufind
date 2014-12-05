<?php
/* ************************************************************************ */
/* Perform fields, posted from upload form                                  */
/*                                                                          */
/* ************************************************************************ */
$upload_err = "";
$upload_err_title = "<NOBR>Ошибка загрузки файла</NOBR>";


IF ($elib2vf_access_arr["$vf_userlogin"]["SuperUser"] == "Y" 
    && TRIM($select_ISSN) == ""):
    $upload_err .= "{$upload_err_title}: не выбран журнал<BR>";
ENDIF;


$userfile1_name = $_FILES['userfile1']['name'];
$userfile1_size = $_FILES['userfile1']['size'];
$userfile1_mime = $_FILES['userfile1']['type'];
$userfile1_temp = $_FILES['userfile1']['tmp_name'];


IF (TRIM($userfile1_name) == ""):

    $upload_err .= "{$upload_err_title}: не выбран файл на локальном диске<BR>";

ELSE:

    IF ($userfile1_size < 1 || $userfile1_temp == ""):
        $upload_err .= "{$upload_err_title}: файл \"$userfile1_name\" не был загружен (либо он отсутствует, либо его размер превышает <NOBR>$upload_files_max_size байт</NOBR>)<BR>";
    ELSE:
        IF ($userfile1_size > $upload_files_max_size):
            $upload_err .= "{$upload_err_title}: размер $userfile1_size исходного файла \"$userfile1_name\" превышает ограничение <NOBR>$upload_files_max_size байт</NOBR><BR>";
        ENDIF;

        $locfile_arr      = EXPLODE(".", $userfile1_name);
        $locfile_arr_size = COUNT($locfile_arr);
        $locfile_arr_last = MAX(1, $locfile_arr_size - 1);
        $locfile_name     = TRIM($locfile_arr[0]);           /* Let be so ! */
        $locfile_type     = TRIM($locfile_arr[$locfile_arr_last]);
        $userfile1_type   = $locfile_type;   /* It is extension, not MIME ! */

        IF (STRLEN($userfile1_type) < 1):
            $upload_err .= "{$upload_err_title}: исходный файл \"$userfile1_name\" не имеет расширения<BR>";
        ENDIF;

        IF ($upload_files_types != ""):
        IF (!STRSTR(STRTOLOWER($upload_files_types), "[".STRTOLOWER($userfile1_type)."]")):
            $upload_err .= "{$upload_err_title}: исходный файл \"$userfile1_name\" имеет расширение, отличное от допустимых <NOBR>{$upload_files_types}</NOBR><BR>";
        ENDIF;
        ENDIF;

        IF (STRSTR(STRTOLOWER($upload_files_bad_types), "[".STRTOLOWER($userfile1_type)."]")):
            $upload_err .= "{$upload_err_title}: исходный файл \"$userfile1_name\" имеет недопустимое расширение<BR>";
        ENDIF;
    ENDIF;
                                                               /* Test size */

    IF ($upload_err == ""):
        IF (!IS_FILE($userfile1_temp)):                       /* Perestrah. */
            $upload_err .= "{$upload_err_title}: по каким-то причинам файл не был загружен<BR>";
        ELSE:
            /** Already was used $userfile1_size **
            $tempfile_size = INTVAL(FILESIZE($userfile1_temp));
            IF ($tempfile_size > $upload_files_max_size):
                $upload_err .= "{$upload_err_title}: размер $tempfile_size исходного файла \"$userfile1_name\" превышает ограничение <NOBR>$upload_files_max_size байт</NOBR><BR>";
            ENDIF;
            **/
        ENDIF;
    ENDIF;
                                              /* Make name of uploaded file */
    
    IF ($upload_err == ""):
        $tempfile_name = "";
        $symb_line = "abcdefghijklmnopqrstuvwxyz0123456789-_";
        $t=0;
        WHILE ($t < STRLEN($locfile_name)):
               $symb = STRTOLOWER(SUBSTR($locfile_name,$t,1));
               IF (STRSTR($symb_line,$symb)):
                   $tempfile_name = $tempfile_name . $symb;
               ENDIF;
               $t=$t+1;
        ENDWHILE;
        $tempfile_name = $tempfile_name ."-". STRVAL(RAND(1,1000)) ."-". STRVAL(DATE("U"));
        $tempfile_name = MD5($tempfile_name);

        $tempfile      = STRTOLOWER($tempfile_name . "." . $userfile1_type);
        $tempfile_path = $upload_tempdir_path . "/" . $tempfile;

        $fc = MOVE_UPLOADED_FILE($userfile1_temp, $tempfile_path);     /* ! */

        IF (!$fc):
            $upload_err .= "{$upload_err_title}: загрузка файла невозможна из-за сбоя на сервере<BR>";
        ENDIF;

        @UNLINK($userfile1_temp);                             /* Perestrah. */
        @CLEARSTATCACHE();
    ENDIF;

ENDIF;
/* ************************************************************************ */
?>                               
