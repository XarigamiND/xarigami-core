<?php

if (! defined("_ADODB_XARMYSQLI_LAYER")) {
 define("_ADODB_XARMYSQLI_LAYER", 1 );

include_once(ADODB_DIR . '/drivers/adodb-mysqli.inc.php');

class ADODB_xarmysqli extends ADODB_mysqli
{
    // Override this.
    var $hasGenID = false;

    function GenID($seqname = 'adodbseq', $startID = 1)
    {
        // Xaraya expects a zero (i.e. numeric).
        if (!$this->hasGenID) {
            return 0;
        }

        // Continue with the standard driver.
        return ADODB_mysqli::GenID($seqname, $startID);
    }

    // Add some debug timings to the driver execute method.
    function _Execute($sql, $inputarr = false) {
        if (xarCore::isDebugFlagSet(XARDBG_SQL)) {
            global $xarDebug_sqlCalls;
            $xarDebug_sqlCalls++;
            // initialise time to render by proca
            $lmtime = explode(' ', microtime());
            $lstarttime = $lmtime[1] + $lmtime[0];
        }

        // Execute the standard driver.
        $result = ADODB_mysqli::_Execute($sql, $inputarr);

        if (xarCore::isDebugFlagSet(XARDBG_SQL)) {
            $lmtime = explode(' ', microtime());
            $lendtime = $lmtime[1] + $lmtime[0];
            $ltotaltime = ($lendtime - $lstarttime);
            xarLogMessage('Query (' . $ltotaltime . ' Seconds): ' . $sql);
        }

        return $result;
    }

}

}

?>