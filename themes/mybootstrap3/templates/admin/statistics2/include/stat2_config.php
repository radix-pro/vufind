<?php
/* ************************************************************************ */
/* Config variables                                                         */
/*                                                                          */
/* ************************************************************************ */

$PHP_SELF = $_SERVER['PHP_SELF'];

error_reporting(E_ALL);
ini_set('display_errors','Off');

/* MySQL */

$vf_db_host    = "vufind.kpfu.ru";
$vf_db_name    = "vufind";
$vf_db_user    = "vufind";
$vf_db_pssw    = "vufind";
$vf_db_charset = "utf8";

/* Design */

$color_ttl = "#c0d1f7";
$color_tr1 = "#d8edf9";
$color_txt = "#1c1c80";
$color_tr2 = "#f5f5f5";
$color_err = "#ff0000";

/* URLs & PATHs */

$vf_home_dir = substr($this->url('home'), 0, strlen($this->url('home')) -1);
$vf_home_url = "http://" . $_SERVER["SERVER_NAME"] . $vf_home_dir;

$stat2_home_url = "{$vf_home_url}/Admin/Statistics2";

/* Authorization */

$vf_shibboleth = "Y";                  /* "Y" - use Shibboleth login method */
$sh_userlogin_var = "uid";                   /* Env. var with VF user login */

$current_dir_path = dirname(__FILE__); /* Config is inside folder "include" */
$stat2_home_path  = $current_dir_path;
$stat2_home_path  = STR_IREPLACE("/include", "", $stat2_home_path);
$stat2_home_path  = STR_IREPLACE("\include", "", $stat2_home_path);

$stat2_users_file = "{$stat2_home_path}/stat2_users.txt";

/* Default values */

$stat2_rep_searches_default = "Y";
$stat2_rep_clicks_default   = "Y";
$stat2_rep_fulltext_default = "Y";
$stat2_rep_browsers_default = "";

$stat2_rows_default = 10;

/* Misc */

$stat2_summon_prefix = "Summon";    /* If empty: don't separate Summon & VF */

$stat2_show_execution_time = "Y_tot"; /* "Y_sep", "Y_tot", nothing if empty */

/* ************************************************************************ */
?>
