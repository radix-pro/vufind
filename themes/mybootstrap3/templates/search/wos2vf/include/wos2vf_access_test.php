<?php
/* ************************************************************************ */
/* Test harvest parameters, came from config file                           */
/*                                                                          */
/* ************************************************************************ */
$wos2vf_access_err = "";


/** Define it in properies file **
IF ($wos2vf_harvest_collection == ""):
    $wos2vf_access_err .= "В настройках отсутствует параметр <I>\"collection\"</I><BR>";
ENDIF;

IF ($wos2vf_harvest_institution == ""):
    $wos2vf_access_err .= "В настройках отсутствует параметр <I>\"institution\"</I><BR>";
ENDIF;
**/

IF ($wos2vf_harvest_subdir == ""):
    $wos2vf_access_err .= "В настройках не указан <I>подкаталог для харвестинга</I><BR>";
ELSE:
    $harvest_subdir_path = $vf_harvest_xmldir_path ."/". $wos2vf_harvest_subdir;
    IF (!IS_DIR($harvest_subdir_path)):
        $wos2vf_access_err .= "На сервере не найден подкаталог для харвестинга <I>{$wos2vf_harvest_subdir}</I><BR>";
    ELSE:
        IF (!IS_WRITABLE($harvest_subdir_path)):
            $wos2vf_access_err .= "Подкаталог для харвестинга <I>{$wos2vf_harvest_subdir}</I> на сервере недоступен по записи<BR>";
        ELSE:
                                                    /* Test properties file */
             IF ($wos2vf_harvest_properties_file == ""):
                 $wos2vf_access_err .= "В настройках не указано имя properties-файла<BR>";
             ELSE:
                 $wos2vf_harvest_properties_path = $vf_import_dir_path . "/" . $wos2vf_harvest_properties_file;
                 IF (!IS_FILE($wos2vf_harvest_properties_path)):
                     $wos2vf_access_err .= "На сервере не найден файл для харвестинга <I>$wos2vf_harvest_properties_file</I><BR>";
                 ENDIF;
             ENDIF;
                                                           /* Test XSL file */
             IF ($wos2vf_harvest_xsl_file == ""):
                 $wos2vf_access_err .= "В настройках не указано имя XSL-файла<BR>";
             ELSE:
                 $wos2vf_harvest_xsl_path = $vf_harvest_xsldir_path . "/" . $wos2vf_harvest_xsl_file;
                 IF (!IS_FILE($wos2vf_harvest_xsl_path)):
                     $wos2vf_access_err .= "На сервере не найден XSL-файл <I>$wos2vf_harvest_xsl_file</I><BR>";
                 ENDIF;
             ENDIF;

        ENDIF;
    ENDIF;
ENDIF;


IF ($wos2vf_access_err != ""):
    $wos2vf_access_err .= "Обратитесь к администратору системы VuFind";
ENDIF;

/* ************************************************************************ */
?>
