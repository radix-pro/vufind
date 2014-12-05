#!/usr/bin/php
<?php
/* ************************************************************************ */
/* Index new XML-files (call it from cron)                                  */
/*                                                                          */
/* ************************************************************************ */

$elib_dir_path    = DIRNAME(__FILE__);
$VUFIND_HOME      = REALPATH($elib_dir_path . "/../../../");
$VUFIND_LOCAL_DIR = REALPATH($elib_dir_path . "/../../");


$optimizing = "-s";    /* Empty: optimize, "-s": skip optimize (use commit) */ 
$use_commit = "Y";     /* "Y" - use commit,  else - stop/start Solr in cron */

$harvest_indexer_path = "{$VUFIND_HOME}/harvest/batch-import-xsl2.sh";
$harvest_deleter_path = "{$VUFIND_HOME}/harvest/batch-delete2.sh";
$hierarchy_cache_path = "{$VUFIND_LOCAL_DIR}/cache/hierarchy";
$commit_files_counter = 0;

/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Editable part (one record for each journal)                              */
/*                                                                          */

                                                                /* ELIB/KEV */
$commit_files_counter = XML_Indexing("ELIB/KEV", "elib.properties", "ELIB23054212");

                                                   /* ELIB/UCH_{HUM,EST,FM} */
$commit_files_counter = XML_Indexing("ELIB/UCH_HUM", "elib.properties", "ELIB18156126");
$commit_files_counter = XML_Indexing("ELIB/UCH_EST", "elib.properties", "ELIB18156169");
$commit_files_counter = XML_Indexing("ELIB/UCH_FM",  "elib.properties", "ELIB18156088");

                                                                /* ELIB/F_C */
$commit_files_counter = XML_Indexing("ELIB/F_C", "elib.properties", "ELIB20740239");

                                                                     /* WOS */
$commit_files_counter = XML_Indexing("WOS", "wos.properties", "*WOS*");


/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Fixed part of script                                                     */
/*                                                                          */

IF ($commit_files_counter < 1):
    ECHO "Nothing to index ...";
    EXIT();
ENDIF;
                   /* Skip optimizing => use commit (if don't restart Solr) */

IF ($optimizing == "-s" && $use_commit == "Y"):
    $vf_solr_url = "";                  /* Take Solr URL from VF config.ini */

    $config_ini_main_path  = "{$VUFIND_HOME}/config/vufind/config.ini";
    $config_ini_local_path = "{$VUFIND_LOCAL_DIR}/config/vufind/config.ini";

    IF (!IS_FILE($config_ini_main_path) && !IS_FILE($config_ini_local_path)):
        ECHO "Error: file VF config.ini wasn't found";
        EXIT();
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
            ECHO "Error: Solr URL value wasn't found in file VF config.ini";
            EXIT();
        ENDIF;
    ENDIF;
                                                             /* Make commit */
    ECHO "<BR>Commit ...<BR>"; 
    $solr_commit_url = "{$vf_solr_url}/biblio/update";

    IF (STRTOUPPER(SUBSTR(@PHP_UNAME(), 0, 1)) == "W"):          /* Windows */
        $solr_commit_url .= "?" . "stream.body=<commit/>";
        ECHO FILE_GET_CONTENTS($solr_commit_url);
    ELSE:                                                          /* Linux */
        $solr_commit_url .= "?" . "softCommit=true";
        PASSTHRU("curl $solr_commit_url");
    ENDIF;

ENDIF;

/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Functions                                                                */
/*                                                                          */

FUNCTION XML_Indexing ($xml_subdir_name, $xsl_properties_file, $hierarchy_name) {
GLOBAL $VUFIND_HOME, $VUFIND_LOCAL_DIR;
GLOBAL $optimizing, $commit_files_counter;
GLOBAL $harvest_indexer_path, $harvest_deleter_path, $hierarchy_cache_path;

$xml_subdir_path  = "{$VUFIND_LOCAL_DIR}/harvest/{$xml_subdir_name}";
$xml_subdir_files = COUNT(GLOB("{$xml_subdir_path}/*.xml"));
$del_subdir_files = COUNT(GLOB("{$xml_subdir_path}/*.delete"));
$xml_subdir_files = $xml_subdir_files + $del_subdir_files;

IF ($xml_subdir_files > 0):         /* XML-files exist => use VF harvesting */
    IF ($del_subdir_files > 0):
        PASSTHRU("$harvest_deleter_path $optimizing $xml_subdir_name");
    ENDIF;
    PASSTHRU("$harvest_indexer_path $optimizing $xml_subdir_name $xsl_properties_file");
    $commit_files_counter = $commit_files_counter + $xml_subdir_files;

    IF ($hierarchy_name != ""):                    /* Clear hierarchy cache */
    IF (STRSTR($hierarchy_name, "*")):                  /* Many cache files */
        $cache_files_mask = "{$hierarchy_name}.xml";     /* ex: "*WOS*.xml" */
        $hierarchy_cache_arr = GLOB("$hierarchy_cache_path/$cache_files_mask");
        FOREACH ($hierarchy_cache_arr AS $cache_file):
                 IF (IS_FILE($cache_file)):
                     $fd = @UNLINK($cache_file);
                 ENDIF;
        ENDFOREACH;
        @CLEARSTATCACHE();
    ELSE:                                               /* Alone cache file */
        $hierarchy_cache_file = "hierarchyTree_" . $hierarchy_name . ".xml";
        $hierarchy_cache_file = $hierarchy_cache_path ."/". $hierarchy_cache_file;
        IF (IS_FILE($hierarchy_cache_file)):
            $fd = @UNLINK($hierarchy_cache_file);
            @CLEARSTATCACHE();
        ENDIF;
    ENDIF;
    ENDIF;
ENDIF;

RETURN $commit_files_counter;
}

/* ************************************************************************ */
?>                               
