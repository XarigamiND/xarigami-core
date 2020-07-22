<?php
/**
 * Set "sent" status of a message
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * Set "sent" status of a message
 *
 * @author the Mail module development team
 * @returns integer
 * @return number of items held by this module
 * @param boolean $args['time'] (optional) Timestamp to use.  Default is to get it ourselves
 * @raise DATABASE_ERROR
 */
function mail_adminapi_setsent($args)
{
    extract($args);

    $invalid = array();
    if (!isset($mid) || !is_numeric($mid)) {
        $invalid[] = 'mid';
    }
    if (isset($time) && !is_numeric($time)) {
        $invalid[] = 'time';
    }

    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            join(', ', $invalid), 'admin', 'setsent', 'Mail');
        throw new BadParameterException($msg);
    }

    if (empty($time)) {
        list($usec,$sec) = explode(' ',microtime());
        $time = (float) $sec + (float) $usec;
    }

    // prepare for database interaction
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $queuetable = $xartable['mail_queue'];

    $query = "UPDATE $queuetable SET xar_sent = ? WHERE xar_mid = ?";
    $bindvars = array($time, $mid);
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
    $result->Close();

    return true;
}

?>