<?php

include_once(ADODB_DIR . '/drivers/adodb-postgres7.inc.php');

class ADODB_xarpostgres7 extends ADODB_postgres7
{
    // Prefix the sequence number to make it unique
    var $_genIDSQL = "SELECT NEXTVAL('seq%s')";
    var $_genSeqSQL = "CREATE SEQUENCE seq%s START %s";
    var $_dropSeqSQL = "DROP SEQUENCE seq%s";

    function _insertid()
    {
        // return the GenID value
        return $this->genID;
    }

    function &Execute($sql,$inputarr=false)
    {
        // This statement is hard-coded into the ADODB driver, but
        // causes a failure in initialisation of the driver.
        // We want to suppress this statement.
        if ($sql == 'set datestyle=\'ISO\'') {
            $result = true;
            return $result;
        }

        // Execute the standard PGSQL driver method.
        $result =ADODB_postgres7::Execute($sql, $inputarr);
        return $result;
    }

    // Add some debug timings to the driver execute method.
    function &_Execute($sql, $inputarr = false)
    {
        if (xarCore::isDebugFlagSet(XARDBG_SQL)) {
            global $xarDebug_sqlCalls;
            $xarDebug_sqlCalls++;
            // initialise time to render by proca
            $lmtime = explode(' ', microtime());
            $lstarttime = $lmtime[1] + $lmtime[0];
        }

        // Execute the standard driver.
        $result = ADODB_postgres7::_Execute($sql, $inputarr);

        if (xarCore::isDebugFlagSet(XARDBG_SQL)) {
            $lmtime = explode(' ', microtime());
            $lendtime = $lmtime[1] + $lmtime[0];
            $ltotaltime = ($lendtime - $lstarttime);
            xarLogMessage('Query (' . $ltotaltime . ' Seconds): ' . $sql);
        }

        return $result;
    }
}

?>