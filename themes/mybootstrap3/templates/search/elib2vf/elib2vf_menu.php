<?php
/* ************************************************************************ */
/* Show link to elib2vf.php for eLibrary editors                            */
/*                                                                          */
/* ************************************************************************ */
INCLUDE ("include/elib2vf_config.php");        /* File with conf. variables */
INCLUDE ("include/elib2vf_access.php");
                                   /* => $vf_userlogin, $elib2vf_access_err */
IF ($elib2vf_access_err == ""):
    $li_menu_elib2vf = "<li><a href='{$elib2vf_home_url}' title='Загрузка данных eLibrary в систему VuFind'>Индексирование eLibrary</a></li>";
ELSE:
    $li_menu_elib2vf = "";
ENDIF;
/* ************************************************************************ */
?>
