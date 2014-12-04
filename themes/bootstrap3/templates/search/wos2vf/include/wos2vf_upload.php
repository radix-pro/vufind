<?php
/* ************************************************************************ */
/* Perform uploaded file                                                    */
/*                                                                          */
/* From "test.inc":                                                         */
/* - $userfile1_{name,size,mime,type}                                       */
/* - $tempfile, $tempfile_url                                               */
/*                                                                          */
/* ************************************************************************ */

INCLUDE ("wos2vf_upload_test.php");       /* => $upload_err, $tempfile, ... */

IF ($upload_err == ""):
    $wos_csv_file = $tempfile;
    $wos_csv_path = $tempfile_path;

    INCLUDE ("wos2vf_csv2arr.php");    /* => $parse_csv_err, $wos_csv_arr[] */
    $upload_err = $parse_csv_err;
ENDIF;


IF ($upload_err != ""):
    INCLUDE ("wos2vf_upload_form.php");            /* Return to upload form */
ELSE:
    INCLUDE ("wos2vf_confirm_form.php");        /* Confirm (next step) form */
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
