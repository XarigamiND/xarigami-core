<?php
/**
 * Table information for roles module
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/* Purpose of file:  Table information for roles module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param none $
 * @return $xartable array
 * @throws no exceptions
 * @todo nothing
 */
function roles_xartables()
{
    // Initialise table array
    $xartable = array();

    $roles = xarDB::$prefix . '_roles';
    $rolemembers = xarDB::$prefix . '_rolemembers';
    // FIXME: do you still need those defined here too ?
    $privileges = xarDB::$prefix . '_privileges';
    $privmembers = xarDB::$prefix . '_privmembers';
    $acl = xarDB::$prefix . '_security_acl';
    $masks = xarDB::$prefix . '_security_masks';
    $instances = xarDB::$prefix . '_instances';

    $xartable['users_column'] = array('uid' => $roles . '.xar_uid',
        'name' => $roles . '.xar_name',
        'uname' => $roles . '.xar_uname',
        'email' => $roles . '.xar_email',
        'pass' => $roles . '.xar_pass',
        'date_reg' => $roles . '.xar_date_reg',
        'valcode' => $roles . '.xar_valcode',
        'state' => $roles . '.xar_state',
        'auth_module' => $roles . '.xar_auth_module'
        );
    // Get the name for the autolinks item table
    $user_status = xarDB::$prefix . '_user_status';
    // Set the table name
    $xartable['roles'] = $roles;
    $xartable['rolemembers'] = $rolemembers;
    $xartable['privileges'] = $privileges;
    $xartable['privmembers'] = $privmembers;
    $xartable['security_acl'] = $acl;
    $xartable['security_masks'] = $masks;
    $xartable['instances'] = $instances;
    $xartable['user_status'] = $user_status;
    // Return the table information
    return $xartable;
}

?>