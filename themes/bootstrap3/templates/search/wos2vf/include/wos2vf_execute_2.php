<?php
/* ************************************************************************ */
/* Journal, number, article: array -> XML file                              */
/*                                                                          */
FUNCTION Write_Arr2Xml ($article_arr) {  /* ******************************* */
GLOBAL $wos2vf_xml_prefix,$vf_harvest_xml_subdir_path;

IF (!ISSET($article_arr["identifier"]) || $article_arr["identifier"] == ""):
    RETURN "";
ENDIF;

$article_xml = "";
                                                                /* XML body */
RESET ($article_arr);
$j=0;
WHILE ($j < COUNT($article_arr)):       /* <tag_name> tag_value </tag_name> */
       $xml_tag_name  = KEY    ($article_arr);
       $xml_tag_value = CURRENT($article_arr);

       IF (!ISSET($xml_tag_value) && !IS_ARRAY($xml_tag_value)):
           NEXT($article_arr);
           $j++;
           CONTINUE;                                   /* Ignore empty tags */
       ENDIF;
       IF (!IS_ARRAY($xml_tag_value) && TRIM($xml_tag_value) == ""):
           NEXT($article_arr);
           $j++;
           CONTINUE;
       ENDIF;

       IF ($wos2vf_xml_prefix != ""):
           $xml_tag_name = $wos2vf_xml_prefix . ":" . $xml_tag_name;
       ENDIF;

       IF (!IS_ARRAY($xml_tag_value)):                   /* Alone requisite */
           $xml_tag_value_2 = HTMLSPECIALCHARS($xml_tag_value);
           $article_xml .= "<{$xml_tag_name}>{$xml_tag_value_2}</{$xml_tag_name}>";
       ELSE:
           $k=0;                                         /* Multi requisite */
           WHILE ($k < COUNT($xml_tag_value)):
                  $xml_tag_value_2 = HTMLSPECIALCHARS($xml_tag_value[$k]);
                  $article_xml .= "<{$xml_tag_name}>{$xml_tag_value_2}</{$xml_tag_name}>";
                  $k++;
           ENDWHILE;
       ENDIF;

       $j++;
       NEXT($article_arr);
ENDWHILE;

                                                     /* XML header & footer */
IF ($wos2vf_xml_prefix != ""):
    $oai_prefix = $wos2vf_xml_prefix;
ELSE:
    $oai_prefix = "dc";                                          /* Default */
ENDIF;
                        /* 1) If we don't use $oai_prefix before every tag  */
                        /*    (ex: <authors> instead of <wos:authors>), we  */
                        /*    can skip "xmlns:$oai_prefix='http://purl..."  */
                        /* 2) $oai_prfix also used in XSL-file:             */
                        /*    ex1: <xsl:template match="oai_dc:dc">         */
                        /*    ex2: <xsl:if test="//dc:language">            */

$article_xml = "<oai_dc:$oai_prefix 
                xmlns:oai_dc='http://www.openarchives.org/OAI/2.0/oai_dc/' 
                xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
                xmlns:$oai_prefix='http://purl.org/dc/elements/1.1/'>"
             .  $article_xml 
             . "</oai_dc:$oai_prefix>";

                                                          /* Write XML file */
$xml_file_err = "";

$article_xml_file_name = $article_arr["identifier"] . ".xml"; /* Let be so! */
$article_xml_file_path = $vf_harvest_xml_subdir_path . "/" . $article_xml_file_name;

$fx = FILE_PUT_CONTENTS($article_xml_file_path, $article_xml);

IF (!$fx):
    $xml_file_err = "произошел сбой при записи XML-файла $article_xml_file_name";
ENDIF;

RETURN $xml_file_err;
}  /* ********************************************************************* */

                                         /* (1) Write articles to XML files */
$write_tm = TIME();

$vf_harvest_xml_subdir_path = $vf_harvest_xmldir_path . "/" . $wos2vf_harvest_subdir;
                                         /* Make it visible inside function */
$GLOBALS["vf_harvest_xml_subdir_path"] = $vf_harvest_xml_subdir_path;
$GLOBALS["wos2vf_xml_prefix"] = $wos2vf_xml_prefix;


$articles_counter = COUNT($wos_csv_arr["articles"]);

$i=0;
WHILE ($i < $articles_counter):
       $article_row = $wos_csv_arr["articles"][$i];
                                              /* Article: array -> XML file */
       $xml_file_err = Write_Arr2Xml($article_row);

       IF ($xml_file_err != ""):
           $execute_err = $xml_file_err;
           BREAK;                                         /* Exit from loop */
       ENDIF;

       $i++;
ENDWHILE;
                                  /* (2) Write journal numbers to XML file */

IF ($execute_err == ""):
IF ($wos2vf_use_hierarchy == "Y"):
    $numbers_counter = COUNT($wos_csv_arr["numbers"]);
    RESET($wos_csv_arr["numbers"]);

    $i=0;
    WHILE ($i < $numbers_counter):
           $number_row = CURRENT($wos_csv_arr["numbers"]);
           $number_row["type"] = "Serial";                   /* Let be so ! */

           $xml_file_err = Write_Arr2Xml($number_row);

           IF ($xml_file_err != ""):
               $execute_err = $xml_file_err;
               BREAK;                                     /* Exit from loop */
           ENDIF;

           NEXT($wos_csv_arr["numbers"]);
           $i++;
    ENDWHILE;
ENDIF;
ENDIF;
                                           /* (3) Write journal to XML file */

IF ($execute_err == ""):
IF ($wos2vf_use_hierarchy == "Y"):
    $journals_counter = COUNT($wos_csv_arr["journals"]);
    RESET($wos_csv_arr["journals"]);

    $i=0;
    WHILE ($i < $journals_counter):
           $journal_row = CURRENT($wos_csv_arr["journals"]);

           $xml_file_err = Write_Arr2Xml($journal_row);

           IF ($xml_file_err != ""):
               $execute_err = $xml_file_err;
               BREAK;                                     /* Exit from loop */
           ENDIF;

           NEXT($wos_csv_arr["journals"]);
           $i++;
    ENDWHILE;
ENDIF;
ENDIF;

$write_tm = TIME() - $write_tm;
                                   /* (4) Execute XML/XSL harvest procedure */

IF ($execute_err == ""):
    ECHO "<H4><FONT COLOR='$color_txt'>Протокол индексирования</FONT></H4>";

    $exec_tm = TIME();

    ECHO "<DIV style='background:#000000; color:#ffffff'>";
    ECHO "<PRE>";
    SWITCH (STRTOLOWER($wos2vf_harvest_commit)):
      CASE ("optimize"):
            PASSTHRU("{$vf_harvest_indexer_path}    {$wos2vf_harvest_subdir} {$wos2vf_harvest_properties_file}");

            BREAK;

      CASE ("commit"):
            $vf_solr_url = "";             /* Take Solr url from config.ini */

            $config_ini_main_path  = "{$vf_home_path}/config/vufind/config.ini";
            $config_ini_local_path = "{$vf_home_path}/local/config/vufind/config.ini";

            IF (!IS_FILE($config_ini_main_path) && !IS_FILE($config_ini_local_path)):
                $execute_err = "не найден файл Vufind config.ini";
                BREAK;                                  /* Exit from Switch */
            ELSE:
                IF (!IS_FILE($config_ini_local_path)):
                    $config_ini_arr = PARSE_INI_FILE($config_ini_main_path, true);
                ELSE:
                    $config_ini_arr = PARSE_INI_FILE($config_ini_local_path, true);
                ENDIF;

                IF (ISSET($config_ini_arr["Index"]["url"])):
                    $vf_solr_url = TRIM($config_ini_arr["Index"]["url"]);
                ELSE:
                    IF (ISSET($config_ini_arr["Statistics"]["solr"])):
                        $vf_solr_url = TRIM($config_ini_arr["Statistics"]["solr"]);
                    ENDIF;
                ENDIF;

                IF ($vf_solr_url == ""):
                    $execute_err = "не найден Solr url в файле config.ini";
                    BREAK;                              /* Exit from Switch */
                ENDIF;
            ENDIF;

            PASSTHRU("{$vf_harvest_indexer_path} -s {$wos2vf_harvest_subdir} {$wos2vf_harvest_properties_file}");

            ECHO "<BR>Commit ...<BR>"; 
            $solr_commit_url = "{$vf_solr_url}/biblio/update";
            IF (STRTOUPPER(SUBSTR(@PHP_UNAME(),0,1)) == "W"):    /* Windows */
                $solr_commit_url .= "?" . "stream.body=<commit/>";
                ECHO FILE_GET_CONTENTS($solr_commit_url);
            ELSE:                                                  /* Linux */
                $solr_commit_url .= "?" . "softCommit=true";
                PASSTHRU("curl $solr_commit_url");
            ENDIF;

            BREAK;

      DEFAULT:                                                    /* "cron" */
            ECHO "<B>Статистика по XML-файлам, поставленным в очередь на индексирование</B><BR>";

            $count_articles = COUNT($wos_csv_arr["articles"]);
            $count_numbers  = COUNT($wos_csv_arr["numbers"]);
            $count_journals = COUNT($wos_csv_arr["journals"]);
            $count_total = $count_articles + $count_numbers + $count_journals;

            ECHO "<BR>Статей: $count_articles<BR>";
            ECHO "<BR>Номеров (томов): $count_numbers<BR>";
            ECHO "<BR>Изданий: $count_journals<BR>";
            ECHO "<BR>Итого XML-файлов: $count_total<BR>";
            ECHO "<BR>";

            BREAK;
    ENDSWITCH;
    ECHO "</PRE>";
    ECHO "</DIV>";

    $exec_tm = TIME() - $exec_tm;
    $full_tm = $write_tm + $exec_tm;
    echo "Execution time: $full_tm sec. ($write_tm + $exec_tm)";
    echo "<br><br>";
                                               /* (5) Output result message */

    IF ($execute_err == ""):
        IF (STRTOLOWER($wos2vf_harvest_commit) == "optimize" ||
            STRTOLOWER($wos2vf_harvest_commit) == "commit"   ):
                                                       /* Clear hierarchy cache */
            $hierarchy_cache_arr = GLOB("$vf_cache_path/hierarchy/*WOS*.xml");
            FOREACH ($hierarchy_cache_arr AS $cache_file):
                     IF (IS_FILE($cache_file)):
                         $fd = @UNLINK($cache_file);
                     ENDIF;
            ENDFOREACH;
            @CLEARSTATCACHE();

            ECHO "<B>Индексирование выполнено</B>";

        ELSE:                           /* All updates will be visible tomorrow */

            ECHO "<B>Внимание!!! Все внесенные в индекс изменения будут актуализированы в течение суток</B>";

        ENDIF;
                                               /* Goto WOS collection in VF */ 
        /**
        $building_filter = "Web of Science";
        $building_filter = STR_IREPLACE(" ", "+", $building_filter);
        $vf_filter_getstr .= "&" . "filter[]=building:$building_filter";

        ECHO "<B>Текущий список зарубежных публикаций <a href='{$vf_home_url}/Search/Results?{$vf_filter_getstr}'>см. здесь</a></B>";
        **/

        /**
        $wos_record_prefix = "WOS";
        $lookfor = $wos_record_prefix;
        ECHO "<B>Текущий список зарубежных публикаций <a href='{$vf_home_url}/Search/Results?lookfor={$lookfor}'>см. здесь</a></B>";
        **/

        $wos_record_filter = "WOS*";
        $vf_filter_getstr .= "&" . "filter[]=id:$wos_record_filter";

        ECHO "<BR><B>Текущий список зарубежных публикаций <a href='{$vf_home_url}/Search/Results?{$vf_filter_getstr}'>см. здесь</a></B>";
    ENDIF;

ENDIF;

/* ************************************************************************ */
?>                               
