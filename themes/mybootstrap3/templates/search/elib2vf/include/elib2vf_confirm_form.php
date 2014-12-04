<?php
/* ************************************************************************ */
/* Show parsed journal with <input> fields for fulltext hrefs               */
/* Input:  $elib_xml_arr[]                                                  */
/* Output: $elib_xml_arr[] + <input>                                        */
/*                                                                          */
/* P.S. $files_links_err[] - from "execute.php"                             */
/* ************************************************************************ */
                                                                /* Subtitle */
IF (IS_ARRAY($files_links_err) && COUNT($files_links_err) > 0):
    ECHO "<FONT COLOR='$color_err'>Обнаружены неправильные ссылки на полнотекстовые документы</FONT>";
ELSE:
    ECHO "<H4><FONT COLOR='$color_txt'>Введите ссылки на полнотекстовые источники</FONT></H4>";
ENDIF;

/**
echo "<pre>";
print_r($elib_xml_arr);
echo "</pre>";
**/
                                             /* JS: prevent double clicking */
ECHO "<SCRIPT language=\"JavaScript\">
      <!--\n
      var click_counter = 0;

      function Check_click() {  
        if (click_counter == 0)
           {
            click_counter++;
            return true;
           }
        else 
           {
            alert(\"Идет процесс индексирования. Ждите ...\");
            return false;
           }
      }
\n// -->
\n</SCRIPT>";

ECHO "<TABLE BORDER='0' WIDTH='' CELLPADDING='2' CELLSPACING='2'>";
ECHO "<FORM METHOD='POST' ACTION='$elib2vf_home_url' TARGET='_self' OnSubmit='return Check_click()'>";
ECHO "<style type='text/css'>TD {vertical-align: top}</style>";        /* ! */

                                                            /* Journal info */
ECHO "<TR ALIGN='LEFT' VALIGN='TOP' BGCOLOR='$color_ttl'>";
ECHO "<TD colspan='2'><H4>Реквизиты журнала</H4></TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD><B>Название</B></TD>";
ECHO "<TD>{$elib_xml_arr[journal_title]}&nbsp;</TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD><B>ISSN</B></TD>";
ECHO "<TD>{$elib_xml_arr[journal_issn]}&nbsp;</TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD><B>Год</B></TD>";
ECHO "<TD>{$elib_xml_arr[journal_year]}&nbsp;</TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD><B>Том</B></TD>";
ECHO "<TD>{$elib_xml_arr[journal_volume]}&nbsp;</TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD><B>Номер</B></TD>";
IF ($elib_xml_arr["journal_issue"] != "" && $elib_xml_arr["journal_number"] != ""):
    $journal_number = "{$elib_xml_arr[journal_issue]}&nbsp;($elib_xml_arr[journal_number])";
ELSE:
    $journal_number =  $elib_xml_arr["journal_issue"] . $elib_xml_arr["journal_number"];
ENDIF;
ECHO "<TD>{$journal_number}&nbsp;</TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD><B>Страницы</B></TD>";
ECHO "<TD>{$elib_xml_arr[journal_pages]}&nbsp;</TD>";
ECHO "</TR>";

ECHO "<TR align='left' valign='top'>";
ECHO "<TD><B>Статей</B></TD>";
IF (IS_ARRAY($elib_xml_arr["articles"])):
    $articles_counter = COUNT($elib_xml_arr["articles"]);
ELSE:
    $articles_counter = 0;
ENDIF;
ECHO "<TD>{$articles_counter}&nbsp;</TD>";
ECHO "</TR>";
                                                           /* Articles info */

IF ($articles_counter > 0):
    ECHO "<TR ALIGN='LEFT' VALIGN='TOP' BGCOLOR='$color_ttl'>";
    ECHO "<TD colspan='2'><NOBR>";
    ECHO "<H4>Реквизиты статей</H4>";

    IF (ISSET($files_links_err["prefix"])):               /* From "execute" */
        ECHO "<FONT COLOR='$color_err'>{$files_links_err[prefix]}</FONT><BR>";
    ENDIF;
    ECHO "<INPUT NAME='prefix_files_links' SIZE='60' MAXLENGTH='100' VALUE='$prefix_files_links'>";
    ECHO " - общий http-префикс (в случае ввода будет добавлен к именам файлов)";
    ECHO "</NOBR></TD>";
    ECHO "</TR>";
ENDIF;

$i=0;
WHILE ($i < $articles_counter):
       $article_arr = $elib_xml_arr["articles"][$i];

       $i1=$i+1;
       ECHO "<TR align='left' valign='top'>";
       ECHO "<TD colspan='2'><B>{$i1}.&nbsp;&nbsp;{$article_arr[title]}</B></TD>";
       ECHO "</TR>";

       ECHO "<TR align='left' valign='top'>";
       ECHO "<TD><B>Описание</B></TD>";
       ECHO "<TD>{$article_arr[description]}&nbsp;</TD>";
       ECHO "</TR>";

       ECHO "<TR align='left' valign='top'>";
       ECHO "<TD><B>Авторы</B></TD>";
       $authors = IMPLODE($article_arr["authors"], "; ");
       ECHO "<TD>{$authors}&nbsp;</TD>";
       ECHO "</TR>";

       /**
       ECHO "<TR align='left' valign='top'>";
       ECHO "<TD><B>Страницы</B></TD>";
       ECHO "<TD>{$article_arr[pages]}&nbsp;</TD>";
       ECHO "</TR>";

       ECHO "<TR align='left' valign='top'>";
       ECHO "<TD><B>Ключевые слова</B></TD>";
       $keywords = IMPLODE($article_arr["keywords"], "; ");
       ECHO "<TD>{$keywords}&nbsp;</TD>";
       ECHO "</TR>";

       ECHO "<TR align='left' valign='top'>";
       ECHO "<TD><B>Список литературы</B></TD>";
       IF (IS_ARRAY($article_arr["references"])):
           $references_counter = COUNT($article_arr["references"]);
       ELSE:
           $references_counter = 0;
       ENDIF;
       ECHO "<TD>{$references_counter}&nbsp;</TD>";
       ECHO "</TR>";
       **/

       ECHO "<TR align='left' valign='top'>";
       ECHO "<TD><NOBR><B>Полный текст<BR>(http-ссылка)</B></NOBR></TD>";
       ECHO "<TD>";
       IF (IS_ARRAY($article_arr["files"]) && COUNT($article_arr["files"]) > 0):
           $files_counter = COUNT($article_arr["files"]);
           $k=0;
           WHILE ($k < $files_counter):
                  IF (ISSET($files_links_err[$i][$k])):   /* From "execute" */
                      ECHO "<FONT COLOR='$color_err'>{$files_links_err[$i][$k]}</FONT><BR>";
                  ENDIF;
                  $file_name = $article_arr["files"][$k];
                  ECHO "<INPUT NAME='files_links_arr[$i][$k]' SIZE='60' MAXLENGTH='100' VALUE='$file_name'>";
                  $k++;
           ENDWHILE;
       ELSE:
           ECHO "<INPUT NAME='files_links_arr[$i][0]' SIZE='60' MAXLENGTH='100' VALUE=''>";
       ENDIF;
       ECHO "</TD>";
       ECHO "</TR>";

       $i++;
ENDWHILE;
                                                                 /* Buttons */

ECHO "<TR ALIGN='LEFT' VALIGN='TOP' BGCOLOR='$color_ttl'>";
ECHO "<TD colspan='2' style='background:$color_ttl'><NOBR>";      /* Style! */
IF ($articles_counter > 0):
    IF ($test_files_links == "Y"):
        ECHO "<INPUT NAME='test_files_links' TYPE='checkbox' VALUE='Y' CHECKED>";
    ELSE:
        ECHO "<INPUT NAME='test_files_links' TYPE='checkbox' VALUE='Y'>";
    ENDIF;
    ECHO " Проверять доступность http-ссылок";
    ECHO "<BR>";

    ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Выполнить' TITLE='Предупреждение! Процедура индексирования может занять довольно много времени (до нескольких минут)'>";
    ECHO "&nbsp;";
    ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Отказаться'>";
ELSE:
    ECHO "<INPUT NAME='v_submit' TYPE='Submit' VALUE='Отказаться'>";
    ECHO "&nbsp;&nbsp;&nbsp;";
    ECHO "<FONT COLOR='$color_err'>Статьи отсутствуют, индексировать нечего</FONT>";
ENDIF;
ECHO "</NOBR></TD>";
ECHO "</TR>";
ECHO "<INPUT NAME='select_ISSN' TYPE='hidden' VALUE='$select_ISSN'>";  /* ! */

          /* Serialize & post array (will be unserialized in "execute.php") */
$elib_xml_arr_ser = SERIALIZE    ($elib_xml_arr);
$elib_xml_arr_ser = BASE64_ENCODE($elib_xml_arr_ser);
ECHO "<INPUT NAME='elib_xml_arr_ser' TYPE='hidden' VALUE='$elib_xml_arr_ser'>";


ECHO "</FORM>";
ECHO "</TABLE>";
/* ************************************************************************ */
?>                               
