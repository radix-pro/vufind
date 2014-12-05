<?php
/* ************************************************************************ */
/* Add articles to VuFind/Solr index from WoS CSV-file                      */
/* P.S. Used VF harvesting technique, based on XML/XSL files                */
/*                                                                          */
/* ************************************************************************ */
INCLUDE ("include/wos2vf_config.php");         /* File with conf. variables */

$v_submit = $_REQUEST["v_submit"];
?>


<?php
/* * * * * * * * * * * */
/* M A I N   L O G I C */
/* * * * * * * * * * * */
                                                                   /* Title */
ECHO "<H2>Загрузка данных Web of Science в систему VuFind</H2>";

INCLUDE ("include/wos2vf_access.php");
                                    /* => $vf_userlogin, $wos2vf_access_err */
IF ($wos2vf_access_err != ""):
    $v_submit = "Error: userlogin";
ENDIF;


SWITCH($v_submit):
CASE("Error: userlogin"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Empty user's login or user is not editor                                 */

      ECHO "<FONT COLOR='$color_err'>$wos2vf_access_err</FONT>";

BREAK;


CASE ("Выполнить"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Build VF/Solr index                                                      */

      INCLUDE ("include/wos2vf_execute.php");

BREAK;


CASE ("Загрузить"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Test loaded file. Show some statistics. Confirm execution                */

      INCLUDE ("include/wos2vf_upload.php");

BREAK;


DEFAULT:
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/*                                                                          */

      IF (ISSET($_GET["help"])):
          INCLUDE ("include/wos2vf_help.php");
      ELSE:
          INCLUDE ("include/wos2vf_upload_form.php");
      ENDIF;

BREAK;


/*                                                                          */
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
ENDSWITCH;


/* ************************************************************************ */
?>
