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

/* Misc */

$go2url_clicks_interval = "120";         /* Sec. (don't used if empty or 0) */

/* ************************************************************************ */
?>
