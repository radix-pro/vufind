<?php
/* ************************************************************************ */
/* Test & update header row (columns names) of uploaded CSV file            */
/* Input:  $header_row, $header_size                                        */
/* Output: $header_row (updated), $header_err                               */
/*                                                                          */
/* ************************************************************************ */
$header_err = "";

IF ($header_size <= 2):                                      /* Let be so ! */

    $header_err .= "строка-заголовок содержит мало элементов: $header_size (проверьте разделитель)<BR>";

ELSE:                                          /* Load WOS codes dictionary */
                              
    $wos2vf_columns_dict_path = DIRNAME(__FILE__) . "/" . "wos2vf_columns_dict.csv";
    $fdic = @FOPEN($wos2vf_columns_dict_path, "r");

    IF (!$fdic):
        $header_err .= "не удалось открыть справочник колонок<BR>";
    ELSE:
        $wos_codes_arr = ARRAY();
                            /* Line format = {WOS code; full name; XML tag} */
        WHILE (($line = FGETCSV($fdic, NULL, ";")) !== FALSE):
                $wos_code = TRIM($line[0]);                         /* Code */
                $wos_codes_arr["$wos_code"][0] = TRIM($line[1]);    /* Name */
                $wos_codes_arr["$wos_code"][1] = TRIM($line[2]); /* XML tag */
        ENDWHILE;
                     /* Delete 3 first spec. chars of UTF-8 file (if exist) */

        $header_1st_col = $header_row[0];
        IF ($header_1st_col != CONVERT_CYR_STRING($header_1st_col, "w","d")):
        IF (STRLEN($header_1st_col) > 3):
            $header_1st_col = SUBSTR($header_1st_col, 3);
            IF ($header_1st_col == CONVERT_CYR_STRING($header_1st_col, "w","d")):
                $header_row[0] = $header_1st_col;
            ENDIF;
        ENDIF;
        ENDIF;
                             /* Update header columns:  WOS code -> XML tag */
        $k=0;
        WHILE ($k < $header_size):
               $header_wos_code = TRIM($header_row[$k]);
               $header_xml_tag  = TRIM($wos_codes_arr["$header_wos_code"][1]);
               IF ($header_xml_tag != ""):
                   $header_row[$k] = $header_xml_tag;
               ENDIF;
               $k++;
        ENDWHILE;
                                                  /* Test necessary columns */
        IF (!IN_ARRAY("parent_issn", $header_row)):
            $header_err .= "отсутствует колонка ISSN издания<BR>";
        ENDIF;
        IF (!IN_ARRAY("parent_title", $header_row)):
            $header_err .= "отсутствует колонка названия издания<BR>";
        ENDIF;
        IF (!IN_ARRAY("parent_year", $header_row)):
            $header_err .= "отсутствует колонка года публикации<BR>";
        ENDIF;
        IF (!IN_ARRAY("parent_volume", $header_row) &&
            !IN_ARRAY("parent_issue",  $header_row) ):
            $header_err .= "отсутствуют колонки тома и номера публикации<BR>";
        ENDIF;
        IF (!IN_ARRAY("wos_unique", $header_row)):
            $header_err .= "отсутствует колонка идентификатора WOS<BR>";
        ENDIF;
        IF (!IN_ARRAY("title", $header_row)):
            $header_err .= "отсутствует колонка названия статьи<BR>";
        ENDIF;
    ENDIF;

    @FCLOSE($fdic);

ENDIF;
/* ************************************************************************ */
?>
