<?php
/* ************************************************************************ */
/* Define VF user's login => statistics user                                */
/* Return  $vf_userlogin, $stat2_access_err                                 */
/*                                                                          */
/* ************************************************************************ */
$stat2_access_err = "";

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
                                              /* (2) Define statistics user */

IF (!IS_FILE($stat2_users_file)):
    $stat2_access_err = "Statistics users file wasn't found";
ELSE:                                                  /* File lines => arr */
    $stat2_users_arr = @FILE($stat2_users_file);

    IF (!IS_ARRAY($stat2_users_arr) || COUNT($stat2_users_arr) < 1):
        $stat2_access_err = "Statistics users file is empty";
    ENDIF;
ENDIF;


IF ($vf_userlogin != "" && $stat2_access_err == ""):
    $stat2_user = "";

    $l=0;
    WHILE ($l < COUNT($stat2_users_arr)):
           $line = TRIM($stat2_users_arr[$l]);

           IF (SUBSTR($line, 0, 1) == "#"):    /* Begin with "#" => comment */
               $l++;
               CONTINUE;
           ENDIF;

           $line_user_login = TRIM($line);                             /* ! */

           IF (STRTOLOWER($line_user_login) == STRTOLOWER($vf_userlogin)):
               $stat2_user = $line_user_login;                    /* Fix it */
               BREAK;                   /* User was found => exit from loop */
           ENDIF;

           $l++;
    ENDWHILE;

ENDIF;
                                                         /* (3) Test errors */

IF ($stat2_access_err == ""):
    IF ($vf_userlogin == ""):
        $stat2_access_err = "Для работы необходимо войти в систему VuFind";
    ELSE:
        IF ($stat2_user == ""):
            $stat2_access_err = "У пользователя <B>$vf_userlogin</B> отсутствуют права на просмотр статистики";
        ENDIF;
    ENDIF;
ENDIF;
                                                       /* Is mode[] == "Db" */
IF ($stat2_access_err == ""):
ENDIF;

/* ************************************************************************ */
?>
