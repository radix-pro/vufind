<?php
/* ************************************************************************ */
/* CSV array => XML array                                                   */
/* Input: $wos_csv_arr                                                      */
/*                                                                          */
/* ************************************************************************ */
$wos_xml_arr = ARRAY();

$i=0;
WHILE ($i < COUNT($wos_csv_arr)):
                                                            /* (1) Articles */
       $article_row = $wos_csv_arr[$i];
       $number_row  = ARRAY();
       $journal_row = ARRAY();
                                                             /* Identifiers */
       $journal_id = "WOS";
                                                            /* "WOS" + ISSN */
       IF (!ISSET($article_row["parent_issn"]) || TRIM($article_row["parent_issn"]) == ""):
           $i++;
           CONTINUE;            /* Ignore rows without necessary parameters */
       ELSE:
           $journal_id .= STR_IREPLACE("-", "", $article_row["parent_issn"]);
       ENDIF;               

       $number_id  = $journal_id;        /* "WOS" + issn + year + vol/issue */
       $number_id2 = "";

       IF (!ISSET($article_row["parent_year"]) || TRIM($article_row["parent_year"]) == ""):
           $i++;
           CONTINUE;
       ELSE:
           $number_id  .= "-" . $article_row["parent_year"];
           $number_id2 .= " " . $article_row["parent_year"];
       ENDIF;               

       IF ((!ISSET($article_row["parent_volume"]) || TRIM($article_row["parent_volume"]) == "") &&
           (!ISSET($article_row["parent_issue"])  || TRIM($article_row["parent_issue"])  == "") ):
           $i++;
           CONTINUE;
       ELSE:
           IF (ISSET($article_row["parent_volume"]) && TRIM($article_row["parent_volume"]) != ""):
               $number_id  .= "-"            . $article_row["parent_volume"];
               $number_id2 .= " " . "том" . $article_row["parent_volume"];
           ENDIF;
           IF (ISSET($article_row["parent_issue"]) && TRIM($article_row["parent_issue"]) != ""):
               IF ($article_row["parent_issue"] != CONVERT_CYR_STRING($article_row["parent_issue"], "w","d")):
                   $article_row["parent_issue"] = DOUBLEVAL($article_row["parent_issue"]);
               ENDIF;
               $number_id  .= "-"       . $article_row["parent_issue"];
               $number_id2 .= " " . "N" . $article_row["parent_issue"];
           ENDIF;
       ENDIF;               

       $article_id  = $number_id;                 /* Number_id + WOS unique */
       $article_id2 = $number_id2;

       IF (!ISSET($article_row["wos_unique"]) && TRIM($article_row["wos_unique"]) != ""):
           $i++;
           CONTINUE;
       ELSE:
           $wos_unique  = STR_IREPLACE("WOS:", "", $article_row["wos_unique"]);
           $article_id .= "-" . $wos_unique;
       ENDIF;               

       IF ($article_row["page_start"] != "" && $article_row["page_end"] != ""):
           $article_row["pages"] = $article_row["page_start"] . "-" . $article_row["page_end"];
           /** Let it be identifier of journal number, not article **
           $article_id2 .= " " . "стр." . $article_row["pages"];
           **/
       ENDIF;

       $journal_id  = TRIM($journal_id);

       $number_id   = TRIM($number_id);
       $number_id2  = TRIM($number_id2);

       $article_id  = TRIM($article_id);
       $article_id2 = TRIM($article_id2);
                                                  /* Authors: list -> array */
       $authors_list = "";
       IF (ISSET($article_row["authors"]) && TRIM($article_row["authors"]) != ""):
           $authors_list = TRIM($article_row["authors"]);
       ELSE:
           IF (ISSET($article_row["authors2"]) && TRIM($article_row["authors2"]) != ""):
               $authors_list = TRIM($article_row["authors2"]);
               UNSET($article_row["authors2"]);
           ENDIF;
       ENDIF;
       IF ($authors_list != ""):
           $authors_arr = EXPLODE($wos2vf_list_sep, $authors_list);
           $article_row["authors"] = $authors_arr;
       ENDIF;
                                                    /* Topic: list -> array */
       $topic_list = "";
       IF (ISSET($article_row["topic"]) && TRIM($article_row["topic"]) != ""):
           $topic_list = TRIM($article_row["topic"]);
       ELSE:
           IF (ISSET($article_row["topic2"]) && TRIM($article_row["topic2"]) != ""):
               $topic_list = TRIM($article_row["topic2"]);
               UNSET($article_row["topic2"]);
           ELSE:
               IF (ISSET($article_row["keywords"]) && TRIM($article_row["keywords"]) != ""):
                   $topic_list = TRIM($article_row["keywords"]);
               ELSE:
                   IF (ISSET($article_row["keywords2"]) && TRIM($article_row["keywords2"]) != ""):
                       $topic_list = TRIM($article_row["keywords2"]);
                   ENDIF;
               ENDIF;
           ENDIF;
       ENDIF;
       IF ($topic_list != ""):
           $topic_arr = EXPLODE($wos2vf_list_sep, $topic_list);
           $article_row["topic"] = $topic_arr;
       ENDIF;
                                                 /* Keywords: list -> array */
       $keywords_list = "";
       IF (ISSET($article_row["keywords"]) && TRIM($article_row["keywords"]) != ""):
           $keywords_list = TRIM($article_row["keywords"]);
       ELSE:
           IF (ISSET($article_row["keywords2"]) && TRIM($article_row["keywords2"]) != ""):
               $keywords_list = TRIM($article_row["keywords2"]);
               UNSET($article_row["keywords2"]);
           ENDIF;
       ENDIF;
       IF ($keywords_list != ""):
           $keywords_arr = EXPLODE($wos2vf_list_sep, $keywords_list);
           $article_row["keywords"] = $keywords_arr;
       ENDIF;
                                                        /* Publisher + city */
       $parent_publ = "";
       IF ((ISSET($article_row["parent_publ"]) && TRIM($article_row["parent_publ"]) != "") &&
           (ISSET($article_row["parent_city"]) && TRIM($article_row["parent_city"]) != "") ):
           $parent_publ = $article_row["parent_publ"] . ", " . $article_row["parent_city"];
       ELSE:
           $parent_publ = $article_row["parent_publ"] .        $article_row["parent_city"];
       ENDIF;
       IF (TRIM($parent_publ) != ""):
           $article_row["parent_publ"] = TRIM($parent_publ);
       ENDIF;

       $article_row["identifier"]  = $article_id;
       $article_row["identifier2"] = $article_id2;

            /* For V.U. (also comment container_reference in "wos.xsl") !!! */
       IF (ISSET($article_row["parent_title"]) && TRIM($article_row["parent_title"]) != ""):
           $journal_row["title"] = $article_row["parent_title"];       /* ! */
           $article_row["parent_title"] .= " " . $number_id2;          /* ! */
           $number_row["parent_title"]   = $article_row["parent_title"];
           $number_row["title"]          = $number_row["parent_title"];
       ENDIF;

       IF ($wos2vf_use_hierarchy == "Y"):                      /* Hierarchy */
           $article_row["hierarchy_top_id"]    = $journal_id;
           $article_row["hierarchy_top_title"] = $journal_row["title"];
                                                            /* Number level */
           $article_row["hierarchy_parent_id"]    = $number_id;
           $article_row["hierarchy_parent_title"] = $number_id2;
                                                            /* Article level*/
           $article_row["is_hierarchy_id"]    = $article_id;
           $article_row["is_hierarchy_title"] = $article_row["title"];
       ENDIF;

       $wos_xml_arr["articles"][] = $article_row;
                                                             /* (2) Numbers */
       $number_row["identifier"]  = $number_id;
       $number_row["identifier2"] = $number_id2;

       SWITCH (STRTOUPPER($article_row["parent_type"])):
         CASE ("J"):
               $number_row["type"] = "Journal";
               BREAK;
         CASE ("B"):
               $number_row["type"] = "Book";
               BREAK;
         CASE ("S"):
               $number_row["type"] = "Series";
               BREAK;
         CASE ("P"):
               $number_row["type"] = "Patent";
               BREAK;
         DEFAULT:                                            /* Let be so ! */
               $number_row["type"] = "Journal";
               BREAK;
       ENDSWITCH;

       $number_row["parent_issn"]   = $article_row["parent_issn"];
       $number_row["parent_year"]   = $article_row["parent_year"];
       $number_row["parent_volume"] = $article_row["parent_volume"];
       $number_row["parent_issue"]  = $article_row["parent_issue"];
                                         
       IF ($wos2vf_use_hierarchy == "Y"):                      /* Hierarchy */
           $number_row["hierarchy_top_id"]    = $journal_id;
           $number_row["hierarchy_top_title"] = $journal_row["title"];
                                                            /* Number level */
           $number_row["hierarchy_parent_id"]    = $journal_id;
           $number_row["hierarchy_parent_title"] = $journal_row["title"];

           $number_row["is_hierarchy_id"]    = $number_id;
           $number_row["is_hierarchy_title"] = $number_id2;
       ENDIF;

       $wos_xml_arr["numbers"]["$number_id"] = $number_row;
                                                            /* (3) Journals */
       $journal_row["identifier"]  = $journal_id;

       $journal_row["type"]        = $number_row["type"];
       $journal_row["parent_issn"] = $article_row["parent_issn"];

       IF ($wos2vf_use_hierarchy == "Y"):                      /* Hierarchy */
           $journal_row["hierarchy_top_id"]    = $journal_id;
           $journal_row["hierarchy_top_title"] = $journal_row["title"];
                                      /* Don't use "parent_id/title" here ! */
           $journal_row["is_hierarchy_id"]    = $journal_id;
           $journal_row["is_hierarchy_title"] = $journal_row["title"];
       ENDIF;

       $wos_xml_arr["journals"]["$journal_id"] = $journal_row;

       $i++;
ENDWHILE;

$wos_csv_arr = $wos_xml_arr;
UNSET($wos_xml_arr);
/* ************************************************************************ */
?>
