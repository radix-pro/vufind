<?php
/* ************************************************************************ */
/* Show link to "stat2.php" for administrators                              */
/*                                                                          */
/* ************************************************************************ */
INCLUDE ("include/stat2_config.php");          /* File with conf. variables */
INCLUDE ("include/stat2_access.php");
                                     /* => $vf_userlogin, $stat2_access_err */
IF ($stat2_access_err == ""):
    $li_menu_stat2 = "<li><a href='{$stat2_home_url}' title='Статистика VuFind'>Статистика VuFind</a></li>";
ELSE:
    $li_menu_stat2 = "";
ENDIF;
/* ************************************************************************ */
?>
