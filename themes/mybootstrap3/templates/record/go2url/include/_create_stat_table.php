<?php
/* ************************************************************************ */
/* Craete stat. table "user_stats_fulltext"                                 */
/*                                                                          */
/* ************************************************************************ */
INCLUDE ("go2url_config.php");
INCLUDE ("go2url_mysql_connect.php");

$cr_tbl = MYSQL_QUERY("CREATE TABLE user_stats_fulltext
                             (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                              `record_id`    VARCHAR(255) NOT NULL DEFAULT '',
                              `fulltext_url` VARCHAR(512) NOT NULL DEFAULT '',
                              `engine`       VARCHAR(32)  NOT NULL DEFAULT '',
                              `browser`      VARCHAR(32)  NOT NULL DEFAULT '',
                              `date_time`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              `ip_address`   VARCHAR(15)  NOT NULL DEFAULT '',
                               KEY `date_time` (`date_time`)
                             ) DEFAULT CHARSET='$vf_db_charset'", 
                               $vf_db_conn);

IF (!$cr_tbl):
    ECHO "<br>Error: table <B>user_stats_fulltext</B> wasn't created";
ELSE:
    ECHO "<br>Table <B>user_stats_fulltext</B> was created";
ENDIF;

/* ************************************************************************ */
?>
