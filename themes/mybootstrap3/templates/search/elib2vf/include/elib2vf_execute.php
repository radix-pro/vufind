<?php
/* ************************************************************************ */
/* Scheme:                                                                  */
/* $elib_xml_arr[] => articles XML-files => XSLT => VF XML/harvest engine   */
/*                                                                          */
/* Input: $elib_xml_arr_ser => unserialize => $elib_xml_arr[]               */
/*        $files_links_arr[] - posted links on fulltext documents           */
/*                                                                          */
/* ************************************************************************ */
                                                   /* (1) Test posted links */
$test_tm = TIME();

IF (IS_ARRAY($_REQUEST["files_links_arr"])):
    $files_links_arr = $_REQUEST["files_links_arr"];

    IF (ISSET($_REQUEST["prefix_files_links"])):      /* Common http-prefix */
        $prefix_files_links = $_REQUEST["prefix_files_links"];
        $prefix_files_links = TRIM($prefix_files_links);
    ENDIF;

    IF (ISSET($_REQUEST["test_files_links"])):
        $test_files_links = $_REQUEST["test_files_links"];
        $test_files_links = TRIM($test_files_links);
    ENDIF;

    $files_links_err   = ARRAY();
    $files_links_arr_2 = ARRAY();          /* Only for skipping empty links */
    $files_links_arr_3 = ARRAY();     /* Full links (with prefix, if exist) */

    IF ($prefix_files_links != ""):
        IF (SUBSTR(STRTOLOWER($prefix_files_links), 0, 7) != "http://"):
            $files_links_err["prefix"] = "Ошибка: ссылка должна начинаться с http://";
            $files_links_arr_2 = $files_links_arr;
            $files_links_arr   = ARRAY();       /* => skip while-loop below */
        ELSE:
            IF (SUBSTR($prefix_files_links, STRLEN($prefix_files_links) -1) == "/"):
                $prefix_files_links = SUBSTR($prefix_files_links, 0, STRLEN($prefix_files_links) -1);
            ENDIF;                                          /* Cut last '/' */
        ENDIF;
    ENDIF;

    RESET ($files_links_arr);
    $i=0;
    WHILE ($i < COUNT($files_links_arr)):
           $i_article = KEY($files_links_arr);
                                               /* Test links for article $i */
           IF (IS_ARRAY($files_links_arr["$i_article"])): 
               $links_counter = COUNT($files_links_arr["$i_article"]);
               $k=0;
               WHILE ($k < $links_counter):
                      $file_link = TRIM($files_links_arr["$i_article"][$k]);
                      IF ($file_link != ""):             /* Skip empty link */
                          $files_links_arr_2["$i_article"][$k] = $file_link;

                          IF ($prefix_files_links == "" 
                              && SUBSTR(STRTOLOWER($file_link), 0, 7) != "http://"):
                              $files_links_err["$i_article"][$k] = "Ошибка: ссылка должна начинаться с http://";
                          ELSE:                 /* Test remote http-address */
                              IF ($prefix_files_links != ""):
                                  $file_link = $prefix_files_links . "/" . $file_link;
                              ENDIF;

                              IF ($test_files_links == "Y"):
                                  $fo = @FOPEN($file_link, "r");
                                  IF (!$fo):
                                      IF ($prefix_files_links != ""):
                                          $files_links_err["$i_article"][$k] = "Документ по указанному адресу <I>$file_link</I> недоступен";
                                      ELSE:
                                          $files_links_err["$i_article"][$k] = "Документ по указанному адресу недоступен";
                                      ENDIF;
                                  ENDIF;
                                  @FCLOSE($fo);
                              ENDIF;
                          ENDIF;

                          $files_links_arr_3["$i_article"][$k] = $file_link;
                      ENDIF;
                      $k++;
               ENDWHILE;
           ENDIF;

           IF (IS_ARRAY($files_links_arr_2["$i_article"])  
               && COUNT($files_links_arr_2["$i_article"]) < 1):
               UNSET($files_links_arr_2["$i_article"]);
               UNSET($files_links_arr_3["$i_article"]);
           ENDIF;      /* May be empty "container" after all links deletion */

           $i++;
           NEXT($files_links_arr);
    ENDWHILE;

    $files_links_arr = $files_links_arr_2;
    UNSET($files_links_arr_2);
ENDIF;

$test_tm = TIME() - $test_tm;
                                 /* (2) Posted var. -> unserialize -> arr[] */

IF (ISSET($_REQUEST["elib_xml_arr_ser"]) && TRIM($_REQUEST["elib_xml_arr_ser"]) != ""):
    $elib_xml_arr_ser = $_REQUEST["elib_xml_arr_ser"];
    $elib_xml_arr_ser = BASE64_DECODE($elib_xml_arr_ser);
    $elib_xml_arr     = UNSERIALIZE  ($elib_xml_arr_ser);
    UNSET($elib_xml_arr_ser);
ENDIF;
                              /* Intersect $files_links_arr & $elib_xml_arr */

IF (IS_ARRAY($elib_xml_arr["articles"])):
    $articles_counter = COUNT($elib_xml_arr["articles"]);
ELSE:
    $articles_counter = 0;
ENDIF;

$i=0;
WHILE ($i < $articles_counter):
       IF (IS_ARRAY($files_links_arr[$i])):   /* Files links for article $i */
           IF (IS_ARRAY($files_links_err) && COUNT($files_links_err) > 0):
               $elib_xml_arr["articles"][$i]["files"] = $files_links_arr[$i];
           ELSE:
               $elib_xml_arr["articles"][$i]["files"] = $files_links_arr_3[$i];
           ENDIF;
       ELSE:
           UNSET ($elib_xml_arr["articles"][$i]["files"]);
       ENDIF;

       $i++;
ENDWHILE;


IF (IS_ARRAY($files_links_err) && COUNT($files_links_err) > 0):
    INCLUDE ("elib2vf_confirm_form.php");   /* Return to confirm edit links */
ELSE:
    $execute_err = "";

    IF (!IS_ARRAY($elib_xml_arr["articles"]) || COUNT($elib_xml_arr["articles"]) < 1):
        $execute_err = "статьи отсутствуют, индексировать нечего";
    ENDIF;
                                       /* (3) Articles arrays -> XML files  */
                                       /* Execute XML/XSL harvest procedure */
    IF ($execute_err == ""):
        INCLUDE ("elib2vf_execute_2.php");               /* => $execute_err */
    ENDIF;

    IF ($execute_err != ""):
        ECHO "<FONT COLOR='$color_err'>Выполнение прервано, т.к. $execute_err</FONT>";
        ECHO "<BR>";
        ECHO "<FONT COLOR='$color_err'>Обратитесь к администратору системы VuFind</FONT>";
    ENDIF;
ENDIF;

/* ************************************************************************ */
?>                               
