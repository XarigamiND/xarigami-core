<?php
/**
 * Table information for privileges module
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2008-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Purpose of file:  Table information for privileges module
 * Return table name definitions to Xarigami
 * @return array
 */
function privileges_xartables()
{
    // Initialise table array
    $tables = array();
    $prefix = xarDB::$prefix;

    $privileges  =  $prefix . '_privileges';
    $privMembers =  $prefix . '_privmembers';
    $roles       =  $prefix . '_roles';
    $roleMembers =  $prefix . '_rolemembers';
    $acl         =  $prefix . '_security_acl';
    $masks       =  $prefix . '_security_masks';
    $levels       = $prefix . '_security_levels';
    $instances   =  $prefix . '_security_instances';
    $modules     =  $prefix . '_modules';
    $privsets    = $prefix . '_security_privsets';
    $realms     =  $prefix . '_security_realms';

    // Set the table names
    $tables['privileges']     = $privileges;
    $tables['privmembers']    = $privMembers;
    $tables['roles']          = $roles;
    $tables['rolemembers']    = $roleMembers;
    $tables['security_acl']   = $acl;
    $tables['security_masks'] = $masks;
    $tables['security_levels'] = $levels;
    $tables['security_instances'] = $instances;
    $tables['modules']      = $modules;
    $tables['security_privsets']      = $privsets;
    $tables['security_realms'] = $realms;
    // Return the table information
    return $tables;
}

?>