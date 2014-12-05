<?php
/* ************************************************************************ */
/* Test $elib2vf_access_arr[], came from "access.php"                       */
/*                                                                          */
/* ************************************************************************ */
$elib2vf_access_err = "";

IF ($elib2vf_access_arr["$vf_userlogin"]["journal_issn"] == ""):
    $elib2vf_access_err .= "В настройках пользователя <B>$vf_userlogin</B> отсутствует <I>ISSN журнала</I><BR>";
ENDIF;

IF ($elib2vf_access_arr["$vf_userlogin"]["journal_title"] == ""):
    $elib2vf_access_err .= "В настройках пользователя <B>$vf_userlogin</B> отсутствует <I>название журнала</I><BR>";
ENDIF;

IF ($elib2vf_access_arr["$vf_userlogin"]["journal_vfid"] == ""):
    $elib2vf_access_err .= "В настройках пользователя <B>$vf_userlogin</B> отсутствует <I>VF-идентификатор журнала</I><BR>";
ENDIF;


IF ($elib2vf_access_arr["$vf_userlogin"]["harvest_collection"] == ""):
    $elib2vf_access_err .= "В настройках пользователя <B>$vf_userlogin</B> отсутствует параметр <I>\"collection\"</I><BR>";
ENDIF;                              /* It may be defined in properties file */

IF ($elib2vf_access_arr["$vf_userlogin"]["harvest_institution"] == ""):
    $elib2vf_access_err .= "В настройках пользователя <B>$vf_userlogin</B> отсутствует параметр <I>\"institution\"</I><BR>";
ENDIF;                              /* It may be defined in properties file */


IF ($elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"] == ""):
    $elib2vf_access_err .= "В настройках пользователя <B>$vf_userlogin</B> не указан <I>подкаталог для харвестинга</I><BR>";
ELSE:
    $harvest_subdir_path = $vf_harvest_xmldir_path ."/". $elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"];
    IF (!IS_DIR($harvest_subdir_path)):
        $elib2vf_access_err .= "На сервере не найден подкаталог для харвестинга <I>{$elib2vf_access_arr[$vf_userlogin][harvest_subdir]}</I><BR>";
    ELSE:
        IF (!IS_WRITABLE($harvest_subdir_path)):
            $elib2vf_access_err .= "Подкаталог для харвестинга <I>{$elib2vf_access_arr[$vf_userlogin][harvest_subdir]}</I> на сервере недоступен по записи<BR>";
        ELSE:
                                                    /* Test properties file */
             IF ($elib2vf_harvest_properties_file == ""):
                 $harvest_subdir = $elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"];
                 $harvest_subdir = STR_IREPLACE("\\", "_", $harvest_subdir);
                 $harvest_subdir = STR_IREPLACE("/",  "_", $harvest_subdir);
                 $harvest_subdir = STRTOLOWER($harvest_subdir);
                 $elib2vf_harvest_properties_file = $harvest_subdir . ".properties";
             ENDIF;
             $elib2vf_harvest_properties_path = $vf_import_dir_path . "/" . $elib2vf_harvest_properties_file;
             IF (!IS_FILE($elib2vf_harvest_properties_path)):
                 $elib2vf_access_err .= "На сервере не найден файл для харвестинга <I>$elib2vf_harvest_properties_file</I><BR>";
             ENDIF;
                                                           /* Test XSL file */
             IF ($elib2vf_harvest_xsl_file == ""):
                 $harvest_subdir = $elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"];
                 $harvest_subdir = STR_IREPLACE("\\", "_", $harvest_subdir);
                 $harvest_subdir = STR_IREPLACE("/",  "_", $harvest_subdir);
                 $harvest_subdir = STRTOLOWER($harvest_subdir);
                 $elib2vf_harvest_xsl_file = $harvest_subdir . ".xsl";
             ENDIF;
             $elib2vf_harvest_xsl_path = $vf_harvest_xsldir_path . "/" . $elib2vf_harvest_xsl_file;
             IF (!IS_FILE($elib2vf_harvest_xsl_path)):
                 $elib2vf_access_err .= "На сервере не найден XSL-файл <I>$elib2vf_harvest_xsl_file</I><BR>";
             ENDIF;

        ENDIF;
    ENDIF;
ENDIF;


IF ($elib2vf_access_err != ""):
    $elib2vf_access_err .= "Обратитесь к администратору системы VuFind";
ENDIF;

/* ************************************************************************ */
?>
