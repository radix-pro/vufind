<?php
/* ************************************************************************ */
/* Define VF user's login => WoS editor                                     */
/* Return  $vf_userlogin, $wos2vf_access_err                                */
/*                                                                          */
/* ************************************************************************ */
$wos2vf_access_err = "";

                                                 /* (1) Define user's login */
$vf_userlogin = "";

IF ($vf_shibboleth == "Y"):                 /* Used Shibboleth login method */

    $vf_userlogin = GETENV("$sh_userlogin_var");
    $vf_userlogin = TRIM($vf_userlogin);

ELSE:                                         /* Take login from VF session */

    IF (ISSET($_SESSION['Account'])):
        $Account_obj = $_SESSION['Account'];
        $Account_arr = CURRENT($Account_obj);
        $Account_arr = CURRENT($Account_arr);

        $user_arr = ARRAY();
        RESET ($Account_arr);  /* Difficalt access to protected key "*data" */
        $i=0;
        WHILE ($i < COUNT($Account_arr)):
               $key = KEY    ($Account_arr);
               $cur = CURRENT($Account_arr);
               IF (STRSTR($key, "data")):
                   $user_arr = $cur;
               ENDIF; 
               $i++;
               NEXT ($Account_arr);
        ENDWHILE;
    ENDIF;

    $vf_userlogin = isset($user_arr["username"])? $user_arr["username"] : '';
    $vf_userlogin = TRIM($vf_userlogin);

ENDIF;
                                                   /* (2) Define WoS editor */

IF (!IS_FILE($wos2vf_editors_file)):
    $wos2vf_access_err = "WoS/VuFind editors file wasn't found";
ELSE:                                                  /* File lines => arr */
    $wos2vf_editors_arr = @FILE($wos2vf_editors_file);

    IF (!IS_ARRAY($wos2vf_editors_arr) || COUNT($wos2vf_editors_arr) < 1):
        $wos2vf_access_err = "WoS/VuFind editors file is empty";
    ENDIF;
ENDIF;


IF ($vf_userlogin != "" && $wos2vf_access_err == ""):
    $wos2vf_editor = "";

    $l=0;
    WHILE ($l < COUNT($wos2vf_editors_arr)):
           $line = TRIM($wos2vf_editors_arr[$l]);

           IF (SUBSTR($line, 0, 1) == "#"):    /* Begin with "#" => comment */
               $l++;
               CONTINUE;
           ENDIF;

           $line_user_login = TRIM($line);                             /* ! */

           IF ($line_user_login == $vf_userlogin):                /* Fix it */
               $wos2vf_editor = $line_user_login;
               BREAK;                   /* User was found => exit from loop */
           ENDIF;

           $l++;
    ENDWHILE;

ENDIF;
                                                         /* (3) Test errors */

IF ($wos2vf_access_err == ""):
    IF ($vf_userlogin == ""):
        $wos2vf_access_err = "Для работы необходимо войти в систему VuFind";
    ELSE:
        IF ($wos2vf_editor == ""):
            $wos2vf_access_err = "У пользователя <B>$vf_userlogin</B> отсутствуют права WoS/VuFind редактора";
        ENDIF;
    ENDIF;
ENDIF;

IF ($wos2vf_access_err == ""):
    INCLUDE ("wos2vf_access_test.php");        /* Test harvest enveronment  */
ENDIF;                                         /* Return $wos2vf_access_err */

/* ************************************************************************ */
?>
