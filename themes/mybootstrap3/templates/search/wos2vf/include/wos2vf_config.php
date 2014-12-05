<?php
/* ************************************************************************ */
/* Config variables                                                         */
/*                                                                          */
/* ************************************************************************ */

$PHP_SELF = $_SERVER['PHP_SELF'];

error_reporting(E_ALL);
ini_set('display_errors','Off');

/* Design */

$color_ttl = "#c0d1f7";
$color_tr1 = "#d8edf9";
$color_txt = "#1c1c80";
$color_tr2 = "#3333cc";
$color_err = "#ff0000";

/* URLs & PATHs */

$vf_home_dir = substr($this->url('home'), 0, strlen($this->url('home')) -1);
$vf_home_url = "http://" . $_SERVER["SERVER_NAME"] . $vf_home_dir;

$wos2vf_home_url = "{$vf_home_url}/Search/wos2vf";

$current_dir_path = dirname(__FILE__); /* Config is inside folder "include" */
$wos2vf_home_path = $current_dir_path;
$wos2vf_home_path = STR_IREPLACE("/include", "", $wos2vf_home_path);
$wos2vf_home_path = STR_IREPLACE("\include", "", $wos2vf_home_path);

$vf_home_path  = GETCWD();
$vf_cache_path = "{$vf_home_path}/local/cache";

/* Authorization */

$vf_shibboleth = "Y";                  /* "Y" - use Shibboleth login method */
$sh_userlogin_var = "uid";                   /* Env. var with VF user login */

$wos2vf_editors_file = "{$wos2vf_home_path}/wos2vf_editors.txt";

/* Upload */

$upload_tempdir_path = "{$wos2vf_home_path}/temp_files";

$upload_files_max_size  = 20000000;  /* Max size (bytes) of loaded CSV-file */
$upload_files_bad_types = "[exe] [com] [bat] [cgi] [pl] [php] [phtml]";
$upload_files_types     = "[csv] [txt]";

/* Harvesting */

/** Define this values in properties file **
$wos2vf_harvest_collection  = "Зарубежные публикации сотрудников КФУ";
$wos2vf_harvest_institution = "Web of Science";
**/
$wos2vf_harvest_subdir = "WOS";

$vf_import_dir_path      = "{$vf_home_path}/import";
$vf_harvest_xsldir_path  = "{$vf_home_path}/import/xsl";
$vf_harvest_xmldir_path  = "{$vf_home_path}/local/harvest";
/*$vf_harvest_indexer_path = "{$vf_home_path}/harvest/batch-import-xsl.bat";*/
$vf_harvest_indexer_path = "{$vf_home_path}/harvest/batch-import-xsl2.sh";

$wos2vf_harvest_xsl_file = "wos.xsl";
$wos2vf_harvest_properties_file = "wos.properties";

$wos2vf_harvest_commit = "cron";        /* cron (default), optimize, commit */

$wos2vf_xml_prefix = "wos";   /* If not empty: <prefix:tag>...</prefix:tag> */

/* Misc */

$wos2vf_list_sep = ";";             /* Sep. char inside lists (ex: authors) */

$wos2vf_use_hierarchy = "Y";

$wos2vf_err_percent = 50.0;    /* Max. possible error lines (% of CSV-file) */

/* ************************************************************************ */
?>
