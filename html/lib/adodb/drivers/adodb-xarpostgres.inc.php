<?php
/*
 V4.60 24 Jan 2005  (c) 2000-2005 John Lim (jlim@natsoft.com.my). All rights reserved.
  Released under both BSD license and Lesser GPL library license.
  Whenever there is any discrepancy between the two licenses,
  the BSD license will take precedence.
  Set tabs to 4.

  NOTE: Since 3.31, this file is no longer used, and the "postgres" driver is
  remapped to "postgres9". Maintaining multiple postgres drivers is no easy
  job, so hopefully this will ensure greater consistency and fewer bugs.
*/

include_once(ADODB_DIR . '/drivers/adodb-postgres9.inc.php');

class ADODB_xarpostgres extends ADODB_postgres9
{
    // Prefix the sequence number to make it unique
    var $_genIDSQL = "SELECT NEXTVAL('seq%s')";
    var $_genSeqSQL = "CREATE SEQUENCE seq%s START %s";
    var $_dropSeqSQL = "DROP SEQUENCE seq%s";

    function _insertid($table,$column)
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
        $result =ADODB_postgres9::Execute($sql, $inputarr);
        return $result;
    }

    // Add some debug timings to the driver execute method.
    function _Execute($sql,$inputarr=false)
    {
        if (xarCore::isDebugFlagSet(XARDBG_SQL)) {
            global $xarDebug_sqlCalls;
            $xarDebug_sqlCalls++;
            // initialise time to render by proca
            $lmtime = explode(' ', microtime());
            $lstarttime = $lmtime[1] + $lmtime[0];
        }

        // Execute the standard driver.
        $result = ADODB_postgres9::_Execute($sql, $inputarr);

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