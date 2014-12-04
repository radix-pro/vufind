<?php
/* ************************************************************************ */
/* eLibrary XML-file before 2013                                            */
/*                                                                          */
/* Parse given XML-file and build from it easy-to-use array                 */
/*                                                                          */
/* Input:  $elib_xml_path - path to loaded XML-file                         */
/* Output: $elib_xml_arr  - constructed array                               */
/*                                                                          */
/* ************************************************************************ */
$parse_xml_err = "";
                                                   /* (1) Test title & ISSN */

$parse_err_prefix = "<NOBR>Обработка исходного файла</NOBR>";

IF (!IS_ARRAY($xml_arr) || COUNT($xml_arr) < 1):
    $parse_xml_err .= "{$parse_err_prefix}: не удалось преобразовать в массив<BR>";
ELSE:
    IF (!ISSET($xml_arr["journalInfo"]["jrntitle"]) || 
          TRIM($xml_arr["journalInfo"]["jrntitle"]) == ""):
        $parse_xml_err .= "{$parse_err_prefix}: отсутствует название журнала<BR>";
    ENDIF;

    IF (!ISSET($xml_arr["issue"]["issn"]) || TRIM($xml_arr["issue"]["issn"]) == ""):
        $parse_xml_err .= "{$parse_err_prefix}: отсутствует реквизит ISSN<BR>";
    ELSE:
        IF (TRIM($xml_arr["issue"]["issn"]) != $elib2vf_access_arr["$vf_userlogin"]["journal_issn"]):
            $parse_xml_err .= "{$parse_err_prefix}: неправильный реквизит ISSN ({$xml_arr[issue][issn]} # {$elib2vf_access_arr[$vf_userlogin][journal_issn]})<BR>";
        ENDIF;
    ENDIF;
ENDIF;

                                                    /* (2) Parse/test array */
IF ($parse_xml_err == ""):
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
/*                                                                          */

$journal_arr = ARRAY();
$journal_id  = "ELIB";
$journal_id2 = "";
                                                 /* (3) Gerneral requisites */

$journal_arr["type"] = "Journal";                           /* It is format */

IF ($elib2vf_access_arr["$vf_userlogin"]["harvest_collection"] != ""): /* ! */
    $journal_arr["collection"] = $elib2vf_access_arr["$vf_userlogin"]["harvest_collection"];
ENDIF;

IF ($elib2vf_access_arr["$vf_userlogin"]["harvest_institution"] != ""):/* ! */
    $journal_arr["institution"] = $elib2vf_access_arr["$vf_userlogin"]["harvest_institution"];
ENDIF;

/** Comment (must be common journal title for all hierarchy elements) **
IF (ISSET($xml_arr["journalInfo"]["jrntitle"]) && TRIM($xml_arr["journalInfo"]["jrntitle"]) != ""):
    $journal_arr["journal_title"] = $xml_arr["journalInfo"]["jrntitle"];
ENDIF;               
**/
IF (ISSET($journal_arr["institution"])):                     /* Let be so ! */
    $journal_arr["journal_title"] = $journal_arr["institution"];
ENDIF;               

IF (ISSET($xml_arr["journalInfo"]["publ"]) && TRIM($xml_arr["journalInfo"]["publ"]) != ""):
    $journal_arr["journal_publ"] = $xml_arr["journalInfo"]["publ"];
ENDIF;               

IF (ISSET($xml_arr["issue"]["issn"]) && TRIM($xml_arr["issue"]["issn"]) != ""):
    $journal_arr["journal_issn"] = $xml_arr["issue"]["issn"];
    $journal_id .= STR_IREPLACE("-", "", $journal_arr["journal_issn"]);
ENDIF;               

IF (ISSET($xml_arr["issue"]["jdateUni"]) && TRIM($xml_arr["issue"]["jdateUni"]) != ""):
    $journal_arr["journal_year"] = $xml_arr["issue"]["jdateUni"];
    $journal_arr["journal_year"] = SUBSTR($journal_arr["journal_year"], 0, 4);
    $journal_id  .= "-" . $journal_arr["journal_year"];
    $journal_id2 .= " " . $journal_arr["journal_year"];
ENDIF;               

IF (ISSET($xml_arr["issue"]["volume"]) && TRIM($xml_arr["issue"]["volume"]) != ""):
    $journal_arr["journal_volume"] = $xml_arr["issue"]["volume"];
    $journal_id  .= "-"            . $journal_arr["journal_volume"];
    $journal_id2 .= " " . "том" . $journal_arr["journal_volume"];
ENDIF;               

IF (ISSET($xml_arr["issue"]["jnumUni"]) && TRIM($xml_arr["issue"]["jnumUni"]) != ""):
    $journal_arr["journal_issue"] = $xml_arr["issue"]["jnumUni"];
    $journal_id  .= "-"       . $journal_arr["journal_issue"];
    $journal_id2 .= " " . "N" . $journal_arr["journal_issue"];
ENDIF;               

IF (ISSET($xml_arr["issue"]["pages"]) && TRIM($xml_arr["issue"]["pages"]) != ""):
    $journal_arr["journal_pages"] = $xml_arr["issue"]["pages"];
ENDIF;               

$journal_id  = TRIM($journal_id);
IF ($journal_id != ""):
    $journal_arr["identifier"] = $journal_id;
ENDIF;

$journal_id2 = TRIM($journal_id2);
IF ($journal_id2 != ""):
    $journal_arr["identifier2"] = $journal_id2;
ENDIF;
           /* For V.U. (also comment container_reference in "elib.xsl") !!! */
$journal_arr["journal_title"] .= " " . $journal_arr["identifier2"];

                                                     /* Hierarchy (numbers) */
IF ($elib2vf_access_arr["$vf_userlogin"]["journal_vfid"] != ""):
    $journal_arr["hierarchy_top_id"]    = $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"];
    $journal_arr["hierarchy_top_title"] = $journal_arr["institution"];
                                                            /* Number level */
    $journal_arr["hierarchy_parent_id"]    = $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"];
    $journal_arr["hierarchy_parent_title"] = $journal_arr["institution"];

    $journal_arr["is_hierarchy_id"]    = $journal_arr["identifier"];
    $journal_arr["is_hierarchy_title"] = $journal_arr["identifier2"];
ENDIF;

                                                            /* (4) Articles */
$articles_arr = $xml_arr["issue"]["article"];

$i=0;
WHILE ($i < COUNT($articles_arr)):
       $article_arr = ARRAY();

       $article_arr["type"] = "Article";                    /* It is format */

       IF (ISSET($journal_arr["collection"])):                         /* ! */
           $article_arr["collection"] = $journal_arr["collection"];
       ENDIF;

       IF (ISSET($journal_arr["institution"])):                        /* ! */
           $article_arr["institution"] = $journal_arr["institution"];
       ENDIF;

       IF (ISSET($journal_arr["journal_title"])):
           $article_arr["journal_title"] = $journal_arr["journal_title"];
       ENDIF;

       IF (ISSET($journal_arr["journal_publ"])):
           $article_arr["journal_publ"] = $journal_arr["journal_publ"];
       ENDIF;

       IF (ISSET($journal_arr["journal_issn"])):
           $article_arr["journal_issn"] = $journal_arr["journal_issn"];
       ENDIF;

       IF (ISSET($journal_arr["journal_year"])):
           $article_arr["journal_year"] = $journal_arr["journal_year"];
       ENDIF;

       IF (ISSET($journal_arr["journal_volume"])):
           $article_arr["journal_volume"] = $journal_arr["journal_volume"];
       ENDIF;

       IF (ISSET($journal_arr["journal_issue"])):
           $article_arr["journal_issue"] = $journal_arr["journal_issue"];
       ENDIF;


       IF (IS_ARRAY($articles_arr[$i]["arttitles"]["arttitle"])):
           $titles_counter = COUNT($articles_arr[$i]["arttitles"]["arttitle"]);
           IF ($titles_counter >= 1):
               $k=0;                       /* May be two titles: rus & eng. */
               WHILE ($k < $titles_counter):
                      $article_title = $articles_arr[$i]["arttitles"]["arttitle"][$k];
                      IF ($article_title != CONVERT_CYR_STRING($article_title, "w","d")
                          || $k == $titles_counter - 1):     /* Rus or last */
                          $article_arr["title"] = $article_title;
                          BREAK;                        /* Exit from k-loop */
                      ENDIF;
                      $k++;
               ENDWHILE;
           ENDIF;
       ELSE:            
           $article_arr["title"] = $articles_arr[$i]["arttitles"]["arttitle"];
       ENDIF;


       IF (IS_ARRAY($articles_arr[$i]["abstracts"]["abstract"])):
           $abstracts_counter = COUNT($articles_arr[$i]["abstracts"]["abstract"]);
           IF ($abstracts_counter >= 1):
               $k=0;
               WHILE ($k < $abstracts_counter):
                      $article_abstract = $articles_arr[$i]["abstracts"]["abstract"][$k];
                      IF ($article_abstract != CONVERT_CYR_STRING($article_abstract, "w","d")
                          || $k == $abstracts_counter - 1):
                          $article_arr["description"] = $article_abstract;
                          BREAK;
                      ENDIF;
                      $k++;
               ENDWHILE;
           ENDIF;
       ELSE:            
           $article_arr["description"] = $articles_arr[$i]["abstracts"]["abstract"];
       ENDIF;


       IF (IS_ARRAY($articles_arr[$i]["authors"]["author"])):
           $article_arr["authors"] = ARRAY();

           $authors_arr     = $articles_arr[$i]["authors"]["author"];
           $authors_counter = COUNT($authors_arr);

           IF (IS_ARRAY($authors_arr[0])):               /* Several authors */
               $k=0;
               WHILE ($k < $authors_counter):
                      IF (IS_ARRAY($authors_arr[$k]["individInfo"][0])):
                          $author_surname  = $authors_arr[$k]["individInfo"][0]["surname"];
                          $author_initials = $authors_arr[$k]["individInfo"][0]["fname"];
                      ELSE:
                          $author_surname  = $authors_arr[$k]["individInfo"]["surname"];
                          $author_initials = $authors_arr[$k]["individInfo"]["fname"];
                      ENDIF;
                      $author_fio =  $author_surname . " " . $author_initials;

                      IF ($author_fio == CONVERT_CYR_STRING($author_fio, "w","d")
                          && ISSET($authors_arr[$k]["individInfo"][1])):
                          $author_surname  = $authors_arr[$k]["individInfo"][1]["surname"];
                          $author_initials = $authors_arr[$k]["individInfo"][1]["fname"];
                          $author_fio =  $author_surname . " " . $author_initials;
                      ENDIF;

                      $article_arr["authors"][] = $author_fio;
                      $k++;
               ENDWHILE;
           ELSE:                                            /* Alone author */
               IF (IS_ARRAY($authors_arr["individInfo"][0])):
                   $author_surname  = $authors_arr["individInfo"][0]["surname"];
                   $author_initials = $authors_arr["individInfo"][0]["fname"];
               ELSE:
                   $author_surname  = $authors_arr["individInfo"]["surname"];
                   $author_initials = $authors_arr["individInfo"]["fname"];
               ENDIF;
               $author_fio =  $author_surname . " " . $author_initials;

               IF ($author_fio == CONVERT_CYR_STRING($author_fio, "w","d")
                   && ISSET($authors_arr["individInfo"][1])):
                   $author_surname  = $authors_arr["individInfo"][1]["surname"];
                   $author_initials = $authors_arr["individInfo"][1]["fname"];
                   $author_fio =  $author_surname . " " . $author_initials;
               ENDIF;

               $article_arr["authors"][] = $author_fio;
           ENDIF;
       ENDIF;


       IF (ISSET($articles_arr[$i]["text"]["@attributes"]["lang"]) &&
           TRIM ($articles_arr[$i]["text"]["@attributes"]["lang"]) != ""):
           $article_arr["language"] = $articles_arr[$i]["text"]["@attributes"]["lang"];
         /*$article_arr["language"] = SUBSTR($article_arr["language"], 0, 2);*/
       ENDIF;

       IF (ISSET($articles_arr[$i]["udk"]) &&
           TRIM ($articles_arr[$i]["udk"]) != ""):
           $article_arr["udk"] = $articles_arr[$i]["udk"];
       ENDIF;

       IF (ISSET($articles_arr[$i]["fpageart"]) && TRIM($articles_arr[$i]["fpageart"]) != ""):
           $article_arr["page_start"] = $articles_arr[$i]["fpageart"];
       ENDIF;
       IF (ISSET($articles_arr[$i]["lpageart"]) && TRIM($articles_arr[$i]["lpageart"]) != ""):
           $article_arr["page_end"] = $articles_arr[$i]["lpageart"];
       ENDIF;

       IF ($article_arr["page_start"] != "" && $article_arr["page_end"] != ""):
           $article_arr["pages"] = $article_arr["page_start"] . "-" . $article_arr["page_end"];
           /** Let it be identifier of journal, not article **
           $article_id2 = $journal_id2 . " " . "стр." . $article_arr["pages"];
           **/
       ENDIF;
       $article_id2 = $journal_id2;


       IF (IS_ARRAY($articles_arr[$i]["keywords"]["kwdGroup"][0]["keyword"])):
           $article_arr["keywords"] = $articles_arr[$i]["keywords"]["kwdGroup"][0]["keyword"];
       ELSE:
           IF (IS_ARRAY($articles_arr[$i]["keywords"]["kwdGroup"]["keyword"])):
               $article_arr["keywords"] = $articles_arr[$i]["keywords"]["kwdGroup"]["keyword"];
           ENDIF;
       ENDIF;

       IF (IS_ARRAY($articles_arr[$i]["references"]["reference"])):
           $article_arr["references"] = $articles_arr[$i]["references"]["reference"];
       ENDIF;

       IF (IS_ARRAY($articles_arr[$i]["fpdf"])):
           $article_arr["files"] = $articles_arr[$i]["fpdf"];
       ELSE:
           IF (ISSET($articles_arr[$i]["fpdf"]) && 
               TRIM ($articles_arr[$i]["fpdf"]) != ""):
               $article_arr["files"][0] = $articles_arr[$i]["fpdf"];
           ENDIF;
       ENDIF;


       $article_id = $journal_id . "-" . STRVAL($i + 1);
       IF ($article_id != ""):
           $article_arr["identifier"] = $article_id;
       ENDIF;

       $article_id2 = TRIM($article_id2);
       IF ($article_id2 != ""):
           $article_arr["identifier2"] = $article_id2;
       ENDIF;
                                                   /* Hierarchy (articles) */

       IF ($elib2vf_access_arr["$vf_userlogin"]["journal_vfid"] != ""):
           $article_arr["hierarchy_top_id"]    = $elib2vf_access_arr["$vf_userlogin"]["journal_vfid"];
           $article_arr["hierarchy_top_title"] = $journal_arr["institution"];
                                                            /* Number level */
           $article_arr["hierarchy_parent_id"]    = $journal_arr["identifier"];
           $article_arr["hierarchy_parent_title"] = $journal_arr["identifier2"];
                                                            /* Article level*/
           $article_arr["is_hierarchy_id"]    = $article_arr["identifier"];
           $article_arr["is_hierarchy_title"] = $article_arr["title"];
       ENDIF;

       /**
       echo "<li>";
       print_r($article_arr);
       **/

       $journal_arr["articles"][] = $article_arr;

       $i++;
ENDWHILE;

IF (!IS_ARRAY($articles_arr) || COUNT($articles_arr) < 1):
    $parse_xml_err .= "{$parse_err_prefix}: статьи не обнаружены<BR>";
ENDIF;

/*                                                                          */
/* ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
ENDIF;


$elib_xml_arr = $journal_arr;
UNSET($journal_arr);
/* ************************************************************************ */
?>
