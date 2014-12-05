<?php
/* ************************************************************************ */
/* Perform uploaded file                                                    */
/*                                                                          */
/* From "test.inc":                                                         */
/* - $userfile1_{name,size,mime,type}                                       */
/* - $tempfile, $tempfile_url                                               */
/*                                                                          */
/* ************************************************************************ */

INCLUDE ("elib2vf_upload_test.php");      /* => $upload_err, $tempfile, ... */

IF ($upload_err == ""):
    $elib_xml_file = $tempfile;
    $elib_xml_path = $tempfile_path;

    INCLUDE ("elib2vf_xml2arr.php");  /* => $parse_xml_err, $elib_xml_arr[] */
    $upload_err = $parse_xml_err;
ENDIF;


IF ($upload_err != ""):
    INCLUDE ("elib2vf_upload_form.php");           /* Return to upload form */
ELSE:
    INCLUDE ("elib2vf_confirm_form.php");       /* Confirm (next step) form */
ENDIF;

                               /* Delete uploaded file (we need only array) */
IF ($tempfile_path != ""):
    IF (IS_FILE($tempfile_path)):
        $fd = @UNLINK ($tempfile_path);
        @CLEARSTATCACHE();
    ENDIF;
ENDIF;

/* ************************************************************************ */
?>
