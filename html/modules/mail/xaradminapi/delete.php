<?php
/**
 * Delete a message
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail Module
 * @link http://xaraya.com/index.php/release/36.html
 * @author Mail Module Development Team
 */
/**
 * Delete a message
 *
 * Standard function to delete a module item
 *
 * @author the Mail module development team
 * @param  $args ['mid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function mail_adminapi_delete($args)
{
    extract($args);

    // security check
    if (!xarSecurityCheck('DeleteMail', 0)) return;

    if (!isset($mid) || !is_numeric($mid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'item ID', 'admin', 'viewq', 'Mail');
        throw new BadParameterException(null,$msg);
    }

    // prepare for database interaction
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $queuetable = $xartable['mail_queue'];

    // delete the record
    $query = "DELETE FROM $queuetable WHERE xar_mid = ?";
    $result = $dbconn->Execute($query,array($mid));

    // check errors
    if (!$result) return;
      xarLogMessage('MAIL: Mail with id '.$mid.' was deleted from the queue by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}
?>