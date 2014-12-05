<?php
/* ************************************************************************ */
/* Some useful functions                                                    */
/*                                                                          */
/* ************************************************************************ */

/* Parse the browser name and version from the agent string */
/* From /module/.../Statistics/AbstractBase.php             */

function getBrowser($agent)
{
    // Try to use browscap.ini if available:
    $browser = @get_browser($agent, true);
    if (isset($browser['parent'])) {
        return $browser['parent'];
    }

    // If browscap.ini didn't work, do our best:
    if (strpos($agent, "Opera") > -1) {
        $split = explode(' ', $agent);
        return str_replace('/', ' ', $split[0]);
    }
    if (strpos($agent, "Chrome") > -1) {
        $split = explode(' ', $agent);
        return str_replace('/', ' ', $split[count($split)-2]);
    }
    if (strpos($agent, "Firefox") > -1 || strpos($agent, "Safari") > -1) {
        $split = explode(' ', $agent);
        return str_replace('/', ' ', end($split));
    }
    if (strpos($agent, "compatible;") > -1) {
        $data = explode("compatible;", $agent);
        $split = preg_split('/[;\)]/', $data[1]);
        return str_replace('/', ' ', trim($split[0]));
    }
}

/* ************************************************************************ */
?>
