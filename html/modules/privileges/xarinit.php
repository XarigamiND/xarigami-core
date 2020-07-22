<?php
/**
 * Initialisation functions for the security module
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

 /**
 * Purpose of file:  Initialisation functions for the security module
 * Initialise the privileges module
 * @param none
 * @returns bool
 * @throws DATABASE_ERROR
 */
function privileges_init()
{

 // Get database information
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    xarDBLoadTableMaintenanceAPI();

    $sitePrefix = xarDB::$prefix;
    $tables['privileges'] = $sitePrefix . '_privileges';
    $tables['privmembers'] = $sitePrefix . '_privmembers';
    $tables['security_acl'] = $sitePrefix . '_security_acl';
    $tables['security_masks'] = $sitePrefix . '_security_masks';
    $tables['security_instances'] = $sitePrefix . '_security_instances';
    $tables['security_realms']      = $sitePrefix . '_security_realms';
    $tables['security_levels']      = $sitePrefix . '_security_levels';
    $tables['security_privsets']      = $sitePrefix . '_security_privsets';

    // Create tables
    /*********************************************************************
     * Here we create all the tables for the privileges module
     *
     * prefix_privileges       - holds privileges info
     * prefix_privmembers      - holds info on privileges group membership
     * prefix_security_acl     - holds info on privileges assignments to roles
     * prefix_security_masks   - holds info on masks for security checks
     * prefix_security_instances       - holds module instance definitions
     * prefix_security_realms  - holds realsm info
     ********************************************************************/

    // prefix_security_realms
    /*********************************************************************
    * CREATE TABLE xar_security_realms (
    *  xar_rid int(11) NOT NULL auto_increment,
    *  xar_name varchar(255) NOT NULL default '',
    *  PRIMARY KEY  (xar_rid)
    * )
    *********************************************************************/
    $query = xarDBCreateTable($tables['security_realms'],
             array('xar_rid'  => array('type'        => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'        => 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // prefix_privileges
    /*********************************************************************
    * CREATE TABLE xar_privileges (
    *   xar_pid int(11) NOT NULL auto_increment,
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_realm varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_component varchar(100) NOT NULL default '',
    *   xar_instance varchar(100) NOT NULL default '',
    *   xar_level int(11) NOT NULL default '0',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_pid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['privileges'],
             array('xar_pid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'       => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_realm' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'  => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instance' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_level' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

   if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_privileges_realm',
                   'fields'    => array('xar_realm'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['privileges'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_privileges_module',
                   'fields'    => array('xar_module'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['privileges'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_privileges_level',
                   'fields'    => array('xar_level'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['privileges'],$index);
    if (!$dbconn->Execute($query)) return;

    xarDB::importTables(array('privileges' => xarDB::$prefix . '_privileges'));

    // prefix_privmembers
    /*********************************************************************
    * CREATE TABLE xar_privmembers (
    *   xar_pid int(11) NOT NULL default '0',
    *   xar_parentid int(11) NOT NULL default '0',
    *   KEY xar_pid (xar_pid,xar_parentid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['privmembers'],
             array('xar_pid'       => array('type'       => 'integer',
                                           'null'        => false,
                                           'default'     => '0'),
                   'xar_parentid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0')));
    if (!$dbconn->Execute($query)) return;

    xarDB::importTables(array('privmembers' => xarDB::$prefix . '_privmembers'));

    $index = array('name'      => 'i_'.$sitePrefix.'_privmembers_id',
                   'fields'    => array('xar_pid','xar_parentid'),
                   'unique'    => TRUE);
    $query = xarDBCreateIndex($tables['privmembers'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_privmembers_parentid',
                   'fields'    => array('xar_parentid'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['privmembers'],$index);
    if (!$dbconn->Execute($query)) return;

    // prefix_security_acl
    /*********************************************************************
    * CREATE TABLE xar_security_acl (
    *   xar_partmember int(11) NOT NULL default '0',
    *   xar_permmember int(11) NOT NULL default '0',
    *   KEY xar_pid (xar_pid,xar_parentid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['security_acl'],
             array('xar_partid'       => array('type'  => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true),
                   'xar_permid'      => array('type'   => 'integer',
                                           'null'        => false,
                                           'default'     => '0',
                                           'key'         => true)));
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_security_acl_id',
                   'fields'    => array('xar_partid','xar_permid'),
                   'unique'    => TRUE);
    $query = xarDBCreateIndex($tables['security_acl'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_security_acl_permid',
                   'fields'    => array('xar_permid'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['security_acl'],$index);

    if (!$dbconn->Execute($query)) return;
    xarDB::importTables(array('security_acl' => xarDB::$prefix . '_security_acl'));

    // prefix_security_masks
    /*********************************************************************
    * CREATE TABLE xar_security_masks (
    *   xar_sid int(11) NOT NULL default '0',
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_realm varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_component varchar(100) NOT NULL default '',
    *   xar_instance varchar(100) NOT NULL default '',
    *   xar_instancetable1 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield1 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield1 varchar(100) NOT NULL default '',
    *   xar_instanceapplication int(11) NOT NULL default '0',
    *   xar_instancetable2 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield2 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield2 varchar(100) NOT NULL default '',
    *   xar_level int(11) NOT NULL default '0',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_sid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['security_masks'],
             array('xar_sid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_name' => array('type'       => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_realm' => array('type'      => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'  => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instance' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_level' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_security_masks',
                   'fields'    => array('xar_name'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['security_masks'],$index);

    if (!$dbconn->Execute($query)) return;
    $index = array('name'      => 'i_'.$sitePrefix.'_security_masks_realm',
                   'fields'    => array('xar_realm'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['security_masks'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_security_masks_module',
                   'fields'    => array('xar_module'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['security_masks'],$index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_security_masks_level',
                   'fields'    => array('xar_level'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($tables['security_masks'],$index);
    if (!$dbconn->Execute($query)) return;

    xarDB::importTables(array('security_masks' => xarDB::$prefix . '_security_masks'));

    // prefix_security_instances
    /*********************************************************************
    * CREATE TABLE xar_security_instances (
    *   xar_iid int(11) NOT NULL default '0',
    *   xar_name varchar(100) NOT NULL default '',
    *   xar_module varchar(100) NOT NULL default '',
    *   xar_type varchar(100) NOT NULL default '',
    *   xar_instancetable1 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield1 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield1 varchar(100) NOT NULL default '',
    *   xar_instanceapplication int(11) NOT NULL default '0',
    *   xar_instancetable2 varchar(100) NOT NULL default '',
    *   xar_instancevaluefield2 varchar(100) NOT NULL default '',
    *   xar_instancedisplayfield2 varchar(100) NOT NULL default '',
    *   xar_description varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_sid)
    * )
    *********************************************************************/

    $query = xarDBCreateTable($tables['security_instances'],
             array('xar_iid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_module' => array('type'     => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_component' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_header' => array('type'   => 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_query' => array('type'   => 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_limit' => array('type'  => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_propagate' => array('type'  => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_instancetable2' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instancechildid' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_instanceparentid' => array('type'   => 'varchar',
                                      'size'        => 100,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_description' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

    if (!$dbconn->Execute($query)) return;

    xarDB::importTables(array('security_instances' => xarDB::$prefix . '_security_instances'));

    // prefix_security_levels
    /*********************************************************************
    * CREATE TABLE xar_security_levels (
    *   xar_lid int(11) NOT NULL auto_increment,
    *   xar_level int(3) NOT NULL default '0',
    *   xar_sdescription varchar(255) NOT NULL default '',
    *   xar_ldescription varchar(255) NOT NULL default '',
    *   PRIMARY KEY  (xar_lid)
    * )
    *********************************************************************/

    $leveltable = $tables['security_levels'];
    $query = xarDBCreateTable($leveltable,
             array('xar_lid'  => array('type'       => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_level' => array('type'      => 'integer',
                                      'null'        => false,
                                      'default'     => '0'),
                   'xar_leveltext' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_sdescription' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => ''),
                   'xar_ldescription' => array('type'=> 'varchar',
                                      'size'        => 255,
                                      'null'        => false,
                                      'default'     => '')));

    if (!$dbconn->Execute($query)) return;

    $index = array('name'      => 'i_'.$sitePrefix.'_security_levels_level',
                   'fields'    => array('xar_level'),
                   'unique'    => FALSE);
    $query = xarDBCreateIndex($leveltable,$index);
    if (!$dbconn->Execute($query)) return;

    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, -1, 'ACCESS_INVALID', 'Access Invalid', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 0, 'ACCESS_NONE', 'No Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 100, 'ACCESS_OVERVIEW', 'Overview Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 200, 'ACCESS_READ', 'Read Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 300, 'ACCESS_COMMENT', 'Comment Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 400, 'ACCESS_EDIT', 'Edit Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 500, 'ACCESS_MODERATE', 'Moderate Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 600, 'ACCESS_ADD', 'Add Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 700, 'ACCESS_DELETE', 'Delete Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;
    $nextId = $dbconn->GenId($leveltable);
    $query = "INSERT INTO $leveltable (xar_lid, xar_level, xar_leveltext, xar_sdescription, xar_ldescription)
              VALUES (?, 800, 'ACCESS_ADMIN', 'Admin Access', '')";
    if (!$dbconn->Execute($query,array($nextId))) return;


    // prefix_security_privsets
    /*********************************************************************
    * CREATE TABLE xar_security_privsets (
    *  xar_uid int(11) NOT NULL auto_increment,
    *  xar_set varchar(255) NOT NULL default '',
    *  PRIMARY KEY  (xar_rid)
    * )
    *********************************************************************/
    $query = xarDBCreateTable($tables['security_privsets'],
             array('xar_uid'  => array('type'        => 'integer',
                                      'null'        => false,
                                      'default'     => '0',
                                      'increment'   => true,
                                      'primary_key' => true),
                   'xar_set' => array('type'        => 'text')));

/*  TO BE IMPLEMENTED LATER
    $result = $dbconn->Execute($query);
    if (!$result) return;

    xarDB::importTables(array('security_instances' => xarDB::$prefix . '_security_instances'));

*/

    /* This init function brings our module to version 0.1.0, run the upgrades for the rest of the initialisation */

    return privileges_upgrade('0.1.0');
}

function privileges_activate()
{

    return true;
}

/**
 * Upgrade the privileges module from an old version
 *
 * @param oldVersion the old version to upgrade from
 * @returns bool
 */
function privileges_upgrade($oldVersion)
{
     xarDBLoadTableMaintenanceAPI();
    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();
    //$datadict = xarDB::newDataDict($dbconn, 'ALTERTABLE');
    switch($oldVersion) {
    case '0.1.0':

        //fall through
    case '1.0.1': //current version
       // On activation, set our variables
       //moved out of activation - maybe redundant calling here but
       $testrealms = xarModGetVar('privileges', 'showrealms');
       if (!isset($testrealms)) {
          xarModSetVar('privileges', 'showrealms', false);
       }
       xarModSetVar('privileges', 'inheritdeny', true); //make sure this is set else it causes big probs
       xarModSetVar('privileges', 'tester', 0);
       xarModSetVar('privileges', 'test', false);
       xarModSetVar('privileges', 'testdeny', false);
       xarModSetVar('privileges', 'testmask', 'All');
       xarModSetVar('privileges', 'realmvalue', 'none');
       xarModSetVar('privileges', 'realmcomparison','exact');

    case '1.0.3':
         //update the privilege masks - duplicate names with realms overwrote original in install
        xarRegisterMask('ViewPrivileges','All','privileges','All','All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadPrivilege','All','privileges','All','All','ACCESS_READ');
        xarRegisterMask('EditPrivilege','All','privileges','All','All','ACCESS_EDIT');
        xarRegisterMask('AddPrivilege','All','privileges','All','All','ACCESS_ADD');
        xarRegisterMask('DeletePrivilege','All','privileges','All','All','ACCESS_DELETE');
        xarRegisterMask('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN');
    case '1.0.4':
        xarRegisterMask('ViewRealm','All','privileges','Realm','All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadRealm','All','privileges','Realm','All','ACCESS_READ');
        xarRegisterMask('EditRealm','All','privileges','Realm','All','ACCESS_EDIT');
        xarRegisterMask('AddRealm','All','privileges','Realm','All','ACCESS_ADD');
        xarRegisterMask('DeleteRealm','All','privileges','Realm','All','ACCESS_DELETE');
        xarRegisterMask('AdminRealm','All','privileges','Realm','All','ACCESS_ADMIN');
    case '1.0.5': //updates in upgrade.php only to keep things neat here and allow db changes in the init function
    case '1.0.6': //current version

    // Make sure any upgrade is mindful of load order during install as this function is called at install time

        break;
    }
    /* Update successful */
    return true;
}

/**
 * Delete the privileges module
 *
 * @param none
 * @returns boolean
 */
function privileges_delete()
{
    // this module cannot be removed
    return false;

    /*********************************************************************
    * Drop the tables
    *********************************************************************/

 // Get database information
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    xarDBLoadTableMaintenanceAPI();

    $query = xarDBDropTable($tables['privileges']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['privmembers']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_realms']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_acl']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_masks']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['security_instances']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    return true;
}

?>