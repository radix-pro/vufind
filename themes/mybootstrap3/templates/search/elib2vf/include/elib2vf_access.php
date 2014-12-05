<?php
/* ************************************************************************ */
/* Define VF user's login => eLibrary editor => editor's requisites         */
/* Return  $elib2vf_access_arr[] - editor's requisites, $elib2vf_access_err */
/*                                                                          */
/* ************************************************************************ */
$elib2vf_access_arr = ARRAY();
$elib2vf_access_err = "";

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
                                      /* (2) Define editor & his requisites */

IF (!IS_FILE($elib2vf_editors_file)):
    $elib2vf_access_err = "eLibrary/VuFind editors file wasn't found";
ELSE:                                                  /* File lines => arr */
    $elib2vf_editors_arr = @FILE($elib2vf_editors_file);

    IF (!IS_ARRAY($elib2vf_editors_arr) || COUNT($elib2vf_editors_arr) < 1):
        $elib2vf_access_err = "eLibrary/VuFind editors file is empty";
    ENDIF;
ENDIF;


IF ($vf_userlogin != "" && $elib2vf_access_err == ""):

    $l=0;
    WHILE ($l < COUNT($elib2vf_editors_arr)):
           $line = TRIM($elib2vf_editors_arr[$l]);

           IF (SUBSTR($line, 0, 1) == "#"):    /* Begin with "#" => comment */
               $l++;
               CONTINUE;
           ENDIF;

           $pos_sep = STRPOS($line, $elib2vf_editors_sep);
           IF ($pos_sep === false):
               $l++;
               CONTINUE;
           ENDIF;

           $line_sep_arr = EXPLODE($elib2vf_editors_sep, $line);
           $line_user_login          = TRIM($line_sep_arr[0]);
           $line_journal_issn        = TRIM($line_sep_arr[1]);
           $line_journal_vfid        = TRIM($line_sep_arr[2]);
           $line_journal_title       = TRIM($line_sep_arr[3]);
           $line_harvest_subdir      = TRIM($line_sep_arr[4]);
           $line_harvest_collection  = TRIM($line_sep_arr[5]);
           $line_harvest_institution = TRIM($line_sep_arr[6]);

           IF (STRTOLOWER($line_user_login) == STRTOLOWER($vf_userlogin)):
                                                                  /* Fix it */
               $elib2vf_access_arr["$vf_userlogin"]["journal_issn"]        = $line_journal_issn;
               $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"]        = $line_journal_vfid;
               $elib2vf_access_arr["$vf_userlogin"]["journal_title"]       = $line_journal_title;
               $elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"]      = $line_harvest_subdir;
               $elib2vf_access_arr["$vf_userlogin"]["harvest_collection"]  = $line_harvest_collection;
               $elib2vf_access_arr["$vf_userlogin"]["harvest_institution"] = $line_harvest_institution;
             /*BREAK;*/                      /* Don't exit from loop here ! */

           ELSE:

               IF (TRIM(STR_REPLACE("*", "", STRTOLOWER($line_user_login))) == STRTOLOWER($vf_userlogin)):
               /* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
               /* It is SuperUser (login with "*") !!!                      */
               /* P.S. $select_ISSN came from upload form                   */

               $elib2vf_access_arr["$vf_userlogin"]["SuperUser"] = "Y";  /*!*/

               IF (ISSET($select_ISSN) && TRIM($select_ISSN) != ""):
                   $elib2vf_editors_arr_2 = $elib2vf_editors_arr;
                        /* Find for SuperUser some journal = F(select_ISSN) */
                   $m=0;
                   WHILE ($m < COUNT($elib2vf_editors_arr_2)):
                          $line = TRIM($elib2vf_editors_arr_2[$m]);

                          IF (SUBSTR($line, 0, 1) == "#"):
                              $m++;
                              CONTINUE;
                          ENDIF;

                          $pos_sep = STRPOS($line, $elib2vf_editors_sep);
                          IF ($pos_sep === false):
                              $m++;
                              CONTINUE;
                          ENDIF;

                          $line_sep_arr = EXPLODE($elib2vf_editors_sep, $line);
                          $line_journal_issn        = TRIM($line_sep_arr[1]);
                          $line_journal_vfid        = TRIM($line_sep_arr[2]);
                          $line_journal_title       = TRIM($line_sep_arr[3]);
                          $line_harvest_subdir      = TRIM($line_sep_arr[4]);
                          $line_harvest_collection  = TRIM($line_sep_arr[5]);
                          $line_harvest_institution = TRIM($line_sep_arr[6]);

                          IF ($line_journal_issn == TRIM($select_ISSN)): /*!*/
                              $elib2vf_access_arr["$vf_userlogin"]["journal_issn"]        = $line_journal_issn;
                              $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"]        = $line_journal_vfid;
                              $elib2vf_access_arr["$vf_userlogin"]["journal_title"]       = $line_journal_title;
                              $elib2vf_access_arr["$vf_userlogin"]["harvest_subdir"]      = $line_harvest_subdir;
                              $elib2vf_access_arr["$vf_userlogin"]["harvest_collection"]  = $line_harvest_collection;
                              $elib2vf_access_arr["$vf_userlogin"]["harvest_institution"] = $line_harvest_institution;
                              BREAK;                   /* Exit from $m-loop */
                          ENDIF;

                          $m++;
                   ENDWHILE;
               ENDIF;

               /*                                                           */
               /*                                                           */
               /* +++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
               BREAK;            /* SuperUser was found => exit from loop ! */
               ENDIF;

           ENDIF;

           $l++;
    ENDWHILE;

ENDIF;
                                                         /* (3) Test errors */

IF ($elib2vf_access_err == ""):
    IF (!IS_ARRAY($elib2vf_access_arr) || COUNT($elib2vf_access_arr) < 1):
        IF ($vf_userlogin == ""):
            $elib2vf_access_err = "Для работы необходимо войти в систему VuFind";
        ELSE:
            $elib2vf_access_err = "У пользователя <B>$vf_userlogin</B> отсутствуют права eLibrary/VuFind редактора";
        ENDIF;
    ENDIF;
ENDIF;

IF ($elib2vf_access_err == ""):
IF ($elib2vf_access_arr["$vf_userlogin"]["SuperUser"] != "Y" || TRIM($select_ISSN) != ""):
    INCLUDE ("elib2vf_access_test.php");      /* Test $elib2vf_access_arr[] */
ENDIF;                                        /* Return $elib2vf_access_err */
ENDIF;

/* ************************************************************************ */
?>
