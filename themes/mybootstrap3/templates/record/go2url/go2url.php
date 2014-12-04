<?php
/* ************************************************************************ */
/* Count given URL clicks and redirect to given URL                         */
/* Input parameters: url    - fulltext URL,                                 */
/*                   record - VF record ID                                  */
/*                   engine - Summon / Solr(Vufind)                         */
/*                                                                          */
/* P.S. Remember theory: no any output before header !!!                    */
/*                                                                          */
/* ************************************************************************ */
INCLUDE ("include/go2url_config.php");
INCLUDE ("include/go2url_mysql_connect.php");
INCLUDE ("include/go2url_functions.php");

IF (ISSET($_GET["url"])    && TRIM($_GET["url"])    != "" &&
    ISSET($_GET["record"]) && TRIM($_GET["record"]) != "" ):

    $goto_url = TRIM($_GET["url"]);
    $record   = TRIM($_GET["record"]);
    $engine   = TRIM($_GET["engine"]);
                                            /* Insert stat. record to table */
    IF ($go2url_clicks_interval == "" 
        || !ISSET($_SESSION['go2url_last_url'])
        || !ISSET($_SESSION['go2url_last_dttm'])
        || $_SESSION['go2url_last_url'] != $goto_url
        || DATE("U") - INTVAL($_SESSION['go2url_last_dttm']) > INTVAL($go2url_clicks_interval)
       ):
                                                              /* SQL-insert */
        $sql_url    = ADDSLASHES($goto_url);
        $sql_record = ADDSLASHES($record);
        $sql_ip     = GETENV("REMOTE_ADDR");
        $sql_dttm   = DATE("Y-m-d H:i:s");

        IF ($engine != ""):
            $sql_engine = ADDSLASHES($engine);
            $sql_engine = "'" . $sql_engine . "'";
        ELSE:
            $sql_engine = "NULL";
        ENDIF;
                                                          /* Define browser */
        $user_agent = GETENV("HTTP_USER_AGENT");
        $browser = getBrowser($user_agent);
        $browser = explode(' ', $browser);                   /* Cut version */
        $browser = TRIM($browser[0]);   

        IF ($browser != ""):
            $sql_browser = ADDSLASHES($browser);
            $sql_browser = "'" . $sql_browser . "'";
        ELSE:
            $sql_browser = "NULL";
        ENDIF;

        $ins = @MYSQL_QUERY("INSERT INTO user_stats_fulltext (`record_id`, `fulltext_url`, `engine`, `browser`, `date_time`, `ip_address`) 
                                                      VALUES ('$sql_record', '$sql_url', $sql_engine, $sql_browser, '$sql_dttm', '$sql_ip')", $vf_db_conn);

        $_SESSION['go2url_last_url']  = $goto_url;      /* Fix inserted url */
        $_SESSION['go2url_last_dttm'] = DATE("U");

    ENDIF;

    HEADER("Location: $goto_url");                      /* Redirect browser */

    ECHO "Redirect don't work. <a href='$goto_url'>Click here</a>";
                                                  /* If redirect don't work */
ENDIF;

@MYSQL_CLOSE($vf_db_conn);
EXIT();                       /* Don't work without it inside "/record" !!! */
/* ************************************************************************ */
?>
