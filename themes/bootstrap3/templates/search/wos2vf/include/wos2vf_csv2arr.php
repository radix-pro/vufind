<?php
/* ************************************************************************ */
/* Parse given CSV-file and build from it easy-to-use array                 */
/*                                                                          */
/* Input:  $wos_csv_path - path to loaded CSV-file,                         */
/*         $csv_sep                                                         */
/* Output: $wos_csv_arr  - constructed array,                               */
/*         $parse_csv_err                                                   */
/*                                                                          */
/* ************************************************************************ */
$parse_csv_err = "";
$parse_err_prefix = "<NOBR>Обработка исходного CSV-файла</NOBR>";

                                                       /* CSV-file => array */
$fcsv = @FOPEN($wos_csv_path, "r");

IF (!$fcsv):

    $parse_csv_err .= "{$parse_err_prefix}: не удалось прочитать загруженный файл<BR>";

ELSE:	

     $header_row = NULL;                                  /* Columns names  */
     $csv_arr    = ARRAY();                               /* CSV data array */
     $count_emp  = 0;
     $count_err  = 0;
     $count_ok   = 0;
     $count_all  = 0;

     WHILE (($row = FGETCSV($fcsv, NULL, $csv_sep)) !== FALSE):/* File loop */
             $count_all++;

             IF (TRIM(IMPLODE($row, "")) == ""):
                 $count_emp++;
                 CONTINUE;                              /* Skip empty lines */
             ENDIF;

             IF (!$header_row):               /* Header: 1-st not empty row */

                 $header_row  = $row;
                 $header_size = COUNT($header_row);

                 INCLUDE ("wos2vf_columns_test.php");     /* => $header_err */
                 IF ($header_err != ""):
                     $parse_csv_err .= "{$parse_err_prefix}: $header_err<BR>";
                     BREAK;                          /* Exit from file loop */
                 ENDIF;

             ELSE:                                         /* CSV body rows */

                 $row_size = COUNT($row);

                 IF ($row_size != $header_size):
                     IF ($row_size > $header_size):
                         $row = ARRAY_SLICE($row, 0, $header_size);
                     ELSE:
                         $row = ARRAY_PAD($row, $header_size, "");
                     ENDIF;
                 ENDIF;
                                                       /* Make assoc. array */
                 $row = ARRAY_COMBINE($header_row, $row);

                                                   /* Test necessary values */

                 IF (!ISSET($row["parent_issn"]) || $row["parent_issn"] == ""):
                     $count_err++;
                     CONTINUE;                           /* Skip wrong line */
                 ENDIF;
                 IF (!ISSET($row["parent_title"]) || $row["parent_title"] == ""):
                     $count_err++;
                     CONTINUE;
                 ENDIF;
                 IF (!ISSET($row["parent_year"]) || $row["parent_year"] == ""):
                     $count_err++;
                     CONTINUE;
                 ENDIF;
                 IF ((!ISSET($row["parent_volume"]) || $row["parent_volume"] == "") &&
                     (!ISSET($row["parent_issue"])  || $row["parent_issue"]  == "") ):
                     $count_err++;
                     CONTINUE;
                 ENDIF;
                 IF (!ISSET($row["wos_unique"]) || $row["wos_unique"] == ""):
                     $count_err++;
                     CONTINUE;
                 ENDIF;
                 IF (!ISSET($row["title"]) || $row["title"] == ""):
                     $count_err++;
                     CONTINUE;
                 ENDIF;
                                               /* Add "right" line to array */
                 $csv_arr[] = $row;
                 $count_ok++;

             ENDIF;

     ENDWHILE;

ENDIF;

@FCLOSE($fcsv);

$wos_csv_arr = $csv_arr;
UNSET($csv_arr);
                                              /* (2) CSV array => XML array */

IF ($parse_csv_err == ""):
    INCLUDE ("wos2vf_csv2arr_xml.php");              /* Update $wos_csv_arr */
ENDIF;
/* ************************************************************************ */
?>
