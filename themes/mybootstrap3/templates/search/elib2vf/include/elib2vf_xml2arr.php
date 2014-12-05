<?php
/* ************************************************************************ */
/* Parse given XML-file and build from it easy-to-use array                 */
/*                                                                          */
/* Input:  $elib_xml_path - path to loaded XML-file                         */
/* Output: $elib_xml_arr  - constructed array                               */
/*                                                                          */
/* ************************************************************************ */
                                                       /* XML-file => array */
$xml = simplexml_load_file($elib_xml_path);
$json_string = json_encode($xml);
$xml_arr = json_decode($json_string, TRUE);

IF (IS_ARRAY($xml_arr["journal"])):    
    $xml_arr = $xml_arr["journal"];
    INCLUDE ("elib2vf_xml2arr_2.php");        /* Old XML-file (before 2013) */
ELSE:                                 
    INCLUDE ("elib2vf_xml2arr_1.php");             /* Current eLib XML-file */
ENDIF;                                /* => $parse_xml_err, $elib_xml_arr[] */

/**
echo "<pre>";
print_r($xml_arr);
echo "</pre>";
echo "<hr>";
**/

UNSET($xml_arr);
UNSET($xml);
/* ************************************************************************ */
?>
