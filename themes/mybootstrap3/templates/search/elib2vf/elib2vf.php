<?php
/* ************************************************************************ */
/* Add articles to VuFind/Solr index from eLibrary XML-file                 */
/* P.S. Used VF harvesting technique, based on XML/XSL files                */
/*                                                                          */
/* ************************************************************************ */
INCLUDE ("include/elib2vf_config.php");        /* File with conf. variables */

$v_submit = $_REQUEST["v_submit"];
$select_ISSN = $_REQUEST["select_ISSN"];   /* From upload form (SuperUsers) */
?>


<?php
/* * * * * * * * * * * */
/* M A I N   L O G I C */
/* * * * * * * * * * * */
                                                                   /* Title */
ECHO "<H2>Загрузка данных eLibrary в систему VuFind</H2>";

INCLUDE ("include/elib2vf_access.php");
            /* => $vf_userlogin, $elib2vf_access_err, $elib2vf_access_arr[] */

IF ($elib2vf_access_err != ""):
    $v_submit = "Error: userlogin";
ELSE:                                                           /* Subtitle */
    IF ($elib2vf_access_arr["$vf_userlogin"]["journal_title"] != ""):
        ECHO "<H3>";
        ECHO $elib2vf_access_arr["$vf_userlogin"]["journal_title"];

        IF ($elib2vf_access_arr["$vf_userlogin"]["journal_issn"] != ""):
            ECHO "&nbsp;ISSN:&nbsp;";
            ECHO $elib2vf_access_arr["$vf_userlogin"]["journal_issn"];
        ENDIF;
        ECHO "</H3>";
    ENDIF;
ENDIF;


SWITCH($v_submit):
CASE("Error: userlogin"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Empty user's login or user is not editor                                 */

      ECHO "<FONT COLOR='$color_err'>$elib2vf_access_err</FONT>";

BREAK;


CASE ("Выполнить"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Build VF/Solr index                                                      */

      INCLUDE ("include/elib2vf_execute.php");

BREAK;


CASE ("Загрузить"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Test loaded file. Confirm & add some settings                            */

      INCLUDE ("include/elib2vf_upload.php");

BREAK;


DEFAULT:
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/*                                                                          */

      IF (ISSET($_GET["help"])):
          INCLUDE ("include/elib2vf_help.php");
      ELSE:
          INCLUDE ("include/elib2vf_show.php");
      ENDIF;

BREAK;


/*                                                                          */
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
ENDSWITCH;


/* ************************************************************************ */
?>
