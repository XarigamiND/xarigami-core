<?php
/**
 * Update a users status
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Update a users status
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uname'] is the users system name
 * @param $args['state'] is the new state for the user
 * returns bool
 */
function roles_userapi_updatestatus($args)
{
    extract($args);

    if ((!isset($uname)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null,$msg);
    }

    if (!xarSecurityCheck('ViewRoles')) return;

    // Get DB Set-up
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolesTable = $xartable['roles'];

    // Update the status
    $query = "UPDATE $rolesTable
              SET xar_valcode = '', xar_state = ?
              WHERE xar_uname = ?";
    $bindvars = array($state,$uname);

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    return true;
}

?>
