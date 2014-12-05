<?php
/* ************************************************************************ */
/* Connect to MySQL database                                                */
/*                                                                          */
/* ************************************************************************ */

$vf_db_conn = @MYSQL_CONNECT($vf_db_host,$vf_db_user,$vf_db_pssw);
IF (!$vf_db_conn):
    DIE("Can't connect to VuFind MySQL server");
ENDIF;

$vf_db_open = @MYSQL_SELECT_DB($vf_db_name, $vf_db_conn);
IF (!$vf_db_open):
    DIE("Vufind database is disable");
ENDIF;

MYSQL_QUERY("SET CHARACTER SET '$vf_db_charset'", $vf_db_conn);
MYSQL_QUERY("SET NAMES         '$vf_db_charset'", $vf_db_conn);

/* ************************************************************************ */
?>
