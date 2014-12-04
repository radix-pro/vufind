<?php
/* ************************************************************************ */
/* Journal, number, article: array -> XML file                              */
/*                                                                          */
FUNCTION Write_Arr2Xml ($article_arr) {  /* ******************************* */
GLOBAL $elib2vf_xml_prefix,$vf_harvest_xml_subdir_path;

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

       IF ($elib2vf_xml_prefix != ""):
           $xml_tag_name = $elib2vf_xml_prefix . ":" . $xml_tag_name;
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
IF ($elib2vf_xml_prefix != ""):
    $oai_prefix = $elib2vf_xml_prefix;
ELSE:
    $oai_prefix = "dc";                                          /* Default */
ENDIF;
                        /* 1) If we don't use $oai_prefix before every tag  */
                        /*    (ex: <authors> instead of <elib:authors>), we */
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

$vf_harvest_xml_subdir_path = $vf_harvest_xmldir_path ."/". $elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"];
                                         /* Make it visible inside function */
$GLOBALS["vf_harvest_xml_subdir_path"] = $vf_harvest_xml_subdir_path;
$GLOBALS["elib2vf_xml_prefix"] = $elib2vf_xml_prefix;


$articles_counter = COUNT($elib_xml_arr["articles"]);

$i=0;
WHILE ($i < $articles_counter):
       $article_arr = $elib_xml_arr["articles"][$i];
                                              /* Article: array -> XML file */
       $xml_file_err = Write_Arr2Xml($article_arr);

       IF ($xml_file_err != ""):
           $execute_err = $xml_file_err;
           BREAK;                                         /* Exit from loop */
       ENDIF;

       $i++;
ENDWHILE;
                                                           /* (2) Hierarchy */

IF ($elib2vf_access_arr["$vf_userlogin"]["journal_vfid"] != ""):
                                /* (2.1) Write journal's number to XML file */
    IF ($execute_err == ""):
        $number_arr = $elib_xml_arr;
        UNSET($number_arr["articles"]);

        $number_arr["type"] = "Serial";                           /* Format */
        $number_arr["title"]  = $number_arr["journal_title"];
        /** Comment, because title already combined with id2 (for V.U.) **
        $number_arr["title"] .= " " . $number_arr["identifier2"];
        **/
        $number_arr["description"] = "$articles_counter статей";

        $xml_file_err = Write_Arr2Xml($number_arr);

        IF ($xml_file_err != ""):
            $execute_err = $xml_file_err;
        ENDIF;
    ENDIF;
                                         /* (2.2) Write journal to XML file */
    IF ($execute_err == ""):              /* Write only if it doesn't exist */
        $journal_link  = "http://" . $_SERVER["SERVER_NAME"]; 
        $journal_link .= $this->url('record');
        $journal_link .= $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"];

        $fo = @FOPEN($journal_link, "r");

        IF (!$fo):
            $journal_arr = ARRAY();

            $journal_arr["type"] = "Journal";                     /* Format */
            $journal_arr["identifier"] = $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"];
            $journal_arr["description"] = "КПФУ";

            IF (ISSET($elib_xml_arr["collection"])):
                $journal_arr["collection"] = $elib_xml_arr["collection"];
            ENDIF;
            IF (ISSET($elib_xml_arr["institution"])):
                $journal_arr["institution"] = $elib_xml_arr["institution"];
            ENDIF;
            /** Comment, because it is number's title (with id2) **
            IF (ISSET($elib_xml_arr["journal_title"])):
                $journal_arr["title"] = $elib_xml_arr["journal_title"];
            ENDIF;
            **/
            IF (ISSET($elib_xml_arr["institution"])):     /* Common title ! */
                $journal_arr["title"] = $elib_xml_arr["institution"];
            ENDIF;
            IF (ISSET($elib_xml_arr["journal_issn"])):
                $journal_arr["journal_issn"] = $elib_xml_arr["journal_issn"];
            ENDIF;
                                               /* Don't use "parent" here ! */
            $journal_arr["hierarchy_top_id"]    = $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"];
            $journal_arr["hierarchy_top_title"] = $journal_arr["institution"];

            $journal_arr["is_hierarchy_id"]    = $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"];
            $journal_arr["is_hierarchy_title"] = $journal_arr["institution"];

            $xml_file_err = Write_Arr2Xml($journal_arr);

            IF ($xml_file_err != ""):
                $execute_err = $xml_file_err;
            ENDIF;
        ENDIF;

        @FCLOSE($fo);
    ENDIF;

ENDIF;

$write_tm = TIME() - $write_tm;
                                   /* (3) Execute XML/XSL harvest procedure */

IF ($execute_err == ""):
    ECHO "<H4><FONT COLOR='$color_txt'>Протокол индексирования</FONT></H4>";

    $elib2vf_harvest_subdir = $elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"];

    IF ($elib2vf_harvest_properties_file == ""):
        $harvest_subdir = $elib2vf_harvest_subdir;
        $harvest_subdir = STR_IREPLACE("\\", "_", $harvest_subdir);
        $harvest_subdir = STR_IREPLACE("/",  "_", $harvest_subdir);
        $harvest_subdir = STRTOLOWER($harvest_subdir);
        $elib2vf_harvest_properties_file = $harvest_subdir . ".properties";
    ENDIF;

    $exec_tm = TIME();

    ECHO "<DIV style='background:#000000; color:#ffffff'>";
    ECHO "<PRE>";
    SWITCH (STRTOLOWER($elib2vf_harvest_commit)):
      CASE ("optimize"):
            PASSTHRU("{$vf_harvest_indexer_path}    {$elib2vf_harvest_subdir} {$elib2vf_harvest_properties_file}");

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

            PASSTHRU("{$vf_harvest_indexer_path} -s {$elib2vf_harvest_subdir} {$elib2vf_harvest_properties_file}");

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
            ECHO "<B>Список XML-файлов, поставленных в очередь на индексирование</B><BR>";
          /*PASSTHRU("{$vf_harvest_indexer_path} -s {$elib2vf_harvest_subdir} {$elib2vf_harvest_properties_file}");*/

            $xml_files_arr = GLOB("$vf_harvest_xml_subdir_path/*.xml");
            $i=1;
            FOREACH ($xml_files_arr AS $xml_file):
                     ECHO "$i) " . BASENAME($xml_file) . "<BR>";
                     $i++;
            ENDFOREACH;

            BREAK;
    ENDSWITCH;
    ECHO "</PRE>";
    ECHO "</DIV>";

    $exec_tm = TIME() - $exec_tm;
    $full_tm = $test_tm + $write_tm + $exec_tm;
    echo "Execution time: $full_tm sec. ($test_tm + $write_tm + $exec_tm)";
    echo "<br><br>";
                                               /* (4) Output result message */

    IF ($execute_err == ""):
    IF (STRTOLOWER($elib2vf_harvest_commit) == "optimize" ||
        STRTOLOWER($elib2vf_harvest_commit) == "commit"   ):
                                                   /* Clear hierarchy cache */
        $hierarchy_cache_file = "hierarchyTree_" . $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"] . ".xml";
        $hierarchy_cache_file = $vf_cache_path . "/hierarchy/" . $hierarchy_cache_file;
        IF (IS_FILE($hierarchy_cache_file)):
            $fd = @UNLINK($hierarchy_cache_file);
            CLEARSTATCACHE();
        ENDIF;
                                           /* Goto journal's info in VuFind */
        IF ($elib_xml_arr["journal_title"] != ""):
        IF ($elib_xml_arr["identifier"] != ""):      /* It is number's id ! */
                                              /* Goto uploaded issue/number */
            $journal_identifier = $elib_xml_arr["identifier"] . "*";
            $vf_filter_getstr  .= "&" . "filter[]=id:{$journal_identifier}";

            ECHO "<B>Проиндексированный номер журнала <a href='{$vf_home_url}/Search/Results?{$vf_filter_getstr}'>см. здесь</a></B>";

        ELSE:
                                                 /* Goto all issues/numbers */ 
            $journal_title = $elib_xml_arr["journal_title"];
            $lookfor       = STR_IREPLACE(" ", "+", $journal_title);
            ECHO "<B>Все проиндексированные номера журнала <a href='{$vf_home_url}/Search/Results?lookfor={$lookfor}&type=JournalTitle'>см. здесь</a></B>";

        ENDIF;
        ENDIF;

    ELSE:                           /* All updates will be visible tomorrow */

        ECHO "<B>Внимание!!! Все внесенные в индекс изменения будут актуализированы в течение суток</B>";

    ENDIF;
    ENDIF;

ENDIF;

/* ************************************************************************ */
?>                               
