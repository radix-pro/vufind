<?php
/* ************************************************************************ */
/* Our self VF statistics (standart statistics work very slow)              */
/*                                                                          */
/* File "config.ini"                                                        */
/* mode[] = Db => enable tables user_stats & user_stats_fields              */
/*                                                                          */
/* ************************************************************************ */
INCLUDE ("include/stat2_config.php");          /* File with conf. variables */
INCLUDE ("include/stat2_mysql_connect.php");              /* => $vf_db_conn */

$v_submit = $_REQUEST["v_submit"];
?>


<?php
/* * * * * * * * * * * */
/* M A I N   L O G I C */
/* * * * * * * * * * * */
                                                                   /* Title */
ECHO "<H2>Статистика</H2>";

INCLUDE ("include/stat2_access.php");
                                     /* => $vf_userlogin, $stat2_access_err */
IF ($stat2_access_err != ""):
    $v_submit = "Error: userlogin";
ENDIF;


SWITCH($v_submit):
CASE("Error: userlogin"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Empty user's login or user have no rights                                */

      ECHO "<FONT COLOR='$color_err'>$stat2_access_err</FONT>";

BREAK;


CASE ("Выполнить"):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/* Build VF statistics                                                      */

      INCLUDE ("include/stat2_form_test.php");        /* => $stat2_form_err */

      IF ($stat2_form_err == ""):
          INCLUDE ("include/stat2_execute.php");        /* Build statistics */
          ECHO "<BR>";
      ENDIF;

      INCLUDE ("include/stat2_form.php");           /* Let always show form */

BREAK;


DEFAULT:
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/*                                                                          */

      IF (!ISSET($stat_dt_1) && !ISSET($stat_dt_2)):
          $stat_dt_1 = "01." . DATE("m.Y");                /* Current month */
      ENDIF;

      IF (!ISSET($stat_rep_searches)):
          $stat_rep_searches = $stat2_rep_searches_default;
      ENDIF;
      IF (!ISSET($stat_rep_clicks)):
          $stat_rep_clicks = $stat2_rep_clicks_default;
      ENDIF;
      IF (!ISSET($stat_rep_fulltext)):
          $stat_rep_fulltext = $stat2_rep_fulltext_default;
      ENDIF;
      IF (!ISSET($stat_rep_browsers)):
          $stat_rep_browsers = $stat2_rep_browsers_default;
      ENDIF;

      IF (!ISSET($stat_rows)):
          $stat_rows = $stat2_rows_default;
      ENDIF;

      INCLUDE ("include/stat2_form.php");

BREAK;


/*                                                                          */
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
ENDSWITCH;


@MYSQL_CLOSE($vf_db_conn);
/* ************************************************************************ */
?>
