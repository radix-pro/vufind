<?php
/* ************************************************************************ */
/* Scheme:                                                                  */
/* $wos_csv_arr[] => articles XML-files => XSLT => VF XML/harvest engine    */
/*                                                                          */
/* Input: $wos_csv_arr_ser => unserialize => $wos_csv_arr[]                 */
/*                                                                          */
/* ************************************************************************ */
                                     /* Posted var. -> unserialize -> arr[] */

IF (ISSET($_REQUEST["wos_csv_arr_ser"]) && TRIM($_REQUEST["wos_csv_arr_ser"]) != ""):
    $wos_csv_arr_ser = $_REQUEST["wos_csv_arr_ser"];
    $wos_csv_arr_ser = BASE64_DECODE($wos_csv_arr_ser);
    IF (FUNCTION_EXISTS("GZCOMPRESS") && FUNCTION_EXISTS("GZUNCOMPRESS")):
        $wos_csv_arr_ser = GZUNCOMPRESS($wos_csv_arr_ser);
    ENDIF;
    $wos_csv_arr = UNSERIALIZE($wos_csv_arr_ser);
    UNSET($wos_csv_arr_ser);
ENDIF;
                                       /* Execute XML/XSL harvest procedure */

$execute_err = "";

IF (!IS_ARRAY($wos_csv_arr["articles"]) || COUNT($wos_csv_arr["articles"]) < 1):
    $execute_err = "статьи отсутствуют, индексировать нечего";
ENDIF;
                                /* 1) Articles,journals arrays -> XML files */
IF ($execute_err == ""):        /* 2) Execute indexing  (or save for cron)  */
    INCLUDE ("wos2vf_execute_2.php");                    /* => $execute_err */
ENDIF;

IF ($execute_err != ""):
    ECHO "<FONT COLOR='$color_err'>Выполнение прервано, т.к. $execute_err</FONT>";
    ECHO "<BR>";
    ECHO "<FONT COLOR='$color_err'>Обратитесь к администратору системы VuFind</FONT>";
ENDIF;

/* ************************************************************************ */
?>                               
