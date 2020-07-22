<?php
/**
 * Module initialization functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
// Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the modules module
 *
 * @param none $
 * @returns bool
 * @throws DATABASE_ERROR
 */
function modules_init()
{
    // Get database information
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $sitePrefix = xarDB::$prefix;
    $systemPrefix = xarDB::$sysprefix;

    $tables['modules'] = $systemPrefix . '_modules';
    $tables['module_vars'] = $sitePrefix . '_module_vars';
    $tables['module_uservars'] = $sitePrefix . '_module_uservars';
    $tables['hooks'] = $sitePrefix . '_hooks';
    // Create tables
    /**
     * Here we create all the tables for the module system
     *
     * prefix_modules       - basic module info
     * prefix_module_vars   - module variables table
     * prefix_hooks         - table for hooks
     */
    // prefix_modules
    /**
     * CREATE TABLE xar_modules (
     *   xar_id int(11) NOT NULL auto_increment,
     *   xar_name varchar(64) NOT NULL default '',
     *   xar_regid int(10) unsigned NOT NULL default '0',
     *   xar_directory varchar(64) NOT NULL default '',
     *   xar_version varchar(10) NOT NULL default '0',
     *   xar_mode int(6) NOT NULL default '1',
     *   xar_class varchar(64) NOT NULL default '',
     *   xar_category varchar(64) NOT NULL default '',
     *   xar_admin_capable tinyint(1) NOT NULL default '0',
     *   xar_user_capable tinyint(1) NOT NULL default '0',
     *   PRIMARY KEY  (xar_id)
     * )
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_regid' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
        'xar_directory' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
        'xar_mode' => array('type' => 'integer', 'size' => 'small', 'null' => false, 'default' => '1'),
        'xar_class' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_category' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_admin_capable' => array('type' => 'integer', 'size' => 'tiny', 'null' => false, 'default' => '0'),
        'xar_user_capable' => array('type' => 'integer', 'size' => 'tiny', 'null' => false, 'default' => '0'),
        'xar_state' => array('type' => 'integer', 'null' => false, 'default' => '0')
        );

    $query = xarDBCreateTable($tables['modules'], $fields);

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $modInfo = xarMod::getFileInfo('modules');
    if (!isset($modInfo)) return; // throw back
    // Use version, since that's the only info likely to change
    $modVersion = $modInfo['version'];
    // Manually Insert Modules module into modules table
    $seqId = $dbconn->GenId($tables['modules']);
    $query = "INSERT INTO " . $tables['modules'] . "
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'modules', 1, 'modules', ?, 1, 'Core Admin', 'Global', 1, 0, 3)";
    $bindvars = array($seqId,(string) $modVersion);

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
    // Save the actual insert id
    $savedmodid = $dbconn->PO_Insert_ID($tables['modules'], 'xar_id');

    // prefix_module_vars
    /**
     * CREATE TABLE xar_module_vars (
     *   xar_id int(11) NOT NULL auto_increment,
     *   xar_mod_id int(11) NOT NULL default 0,
     *   xar_name varchar(64) NOT NULL default '',
     *   xar_value longtext,
     *   PRIMARY KEY  (xar_id)
     * )
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
        'xar_modid' => array('type' => 'integer', 'null' => false),
        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_value' => array('type' => 'text', 'size' => 'long')
        );

    $query = xarDBCreateTable($tables['module_vars'], $fields);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name' => 'i_' . $sitePrefix . '_module_vars_modid',
        'fields' => array('xar_modid'));
    $query = xarDBCreateIndex($tables['module_vars'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;


    $index = array('name' => 'i_' . $sitePrefix . '_module_vars_name',
        'fields' => array('xar_name'));
    $query = xarDBCreateIndex($tables['module_vars'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'   =>  'i_'.$sitePrefix.'_module_vars_name_modid',
            'fields' => array('xar_name', 'xar_modid'),
            'unique' => true);

    $query = xarDBCreateIndex($tables['module_vars'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;


    // prefix_module_uservars
    /**
     * CREATE TABLE xar_module_uservars (
     *   xar_mvid int(11) NOT NULL auto_increment,
     *   xar_uid  int(11) NOT NULL default 0,
     *   xar_value longtext,
     *   PRIMARY KEY  (xar_mvid, xar_uid)
     * )
     */
    // CHECKME: the unsiged param for xar_uid changed from true to false in the changesdue scenario
    // * upgrade needed, this has NOT been done yet?
    // * this id will be the first in Xarigami which can receive negative values, not sure that is a good idea
    $fields = array('xar_mvid' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
        'xar_uid' => array('type' => 'integer', 'null' => false, 'unsigned' => false, 'primary_key' => true),
        'xar_value' => array('type' => 'text', 'size' => 'long')
        );

    $query = xarDBCreateTable($tables['module_uservars'], $fields);

    $result = $dbconn->Execute($query);
    if (!$result) return;
    // MrB: do we want an index on xar_value, on large sites, lots of records may exist
    // <mikespub> the only reason why you might want to use an index on value is when you're doing
    // simple queries or stats based on it. But since all values of all kinds of stuff
    // are mixed together here, and we're not querying by value anyway, this wouldn't help at all...
    // Pro: searching for values will speed up (is that used somewhere)
    // Con: setting a user mod var will become slower and slower (relatively tho)
    // prefix_hooks
    /**
     * CREATE TABLE xar_hooks (
     *   xar_id int(10) unsigned NOT NULL auto_increment,
     *   xar_object varchar(64) NOT NULL default '',
     *   xar_action varchar(64) NOT NULL default '',
     *   xar_smodule varchar(64) NOT NULL default '',
     *   xar_stype varchar(64) NOT NULL default '',
     *   xar_tarea varchar(64) NOT NULL default '',
     *   xar_tmodule varchar(64) NOT NULL default '',
     *   xar_ttype varchar(64) NOT NULL default '',
     *   xar_tfunc varchar(64) NOT NULL default '',
     *   PRIMARY KEY  (xar_id)
     * )
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
        'xar_object' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_action' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_smodule' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'default' => ''),
        // TODO: switch to integer for itemtype (see also xarMod.php)
        'xar_stype' => array('type' => 'varchar', 'size' => 64, 'null' => false, 'default' => ''),
        'xar_tarea' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_tmodule' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_ttype' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_tfunc' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_order' => array('type' => 'integer', 'null' => false, 'default' => '0')
        );

    $query = xarDBCreateTable($tables['hooks'], $fields);

    $result = $dbconn->Execute($query);
    if (!$result) return;
    // <andyv> Add module variables for default user/admin, used in modules list
    /**
     * at this stage of installer mod vars cannot be set, so we use DB calls
     * prolly need to move this closer to installer, not sure yet
     */
    // default show-hide core modules
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'hidecore','0')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // default regenerate command
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'regen','0')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // default style of module list
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'selstyle','plain')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // default filtering based on module states
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'selfilter', '0')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // default modules list sorting order
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'selsort','nameasc')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // default show-hide modules statistics
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'hidestats','0')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // default maximum number of modules listed per page
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'selmax','all')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // default start page
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'startpage','overview')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
    // expertlist
    $query = "INSERT INTO " . $tables['module_vars'] . " (xar_id, xar_modid, xar_name, xar_value)
    VALUES (?,?,'expertlist','0')";
    $result = $dbconn->Execute($query,array($dbconn->GenId($tables['module_vars']),$savedmodid));
    if (!$result) return;
     /* This init function brings our module to version 2.3.0, run the upgrades for the rest of the initialisation */
    //xarMod is already loaded
    return modules_upgrade('2.3.0');
}

/**
 * Activates the modules module
 * Called after every upgrade so we donot want to set new vars here else they will be called each time
 * @param none $
 * @returns bool
 */
function modules_activate()
{
    return true;
}

/**
 * Upgrade the modules module from an old version
 *
 * @param oldversion $ the old version to upgrade from
 * @returns bool
 * @todo include setting moduservars in next upgrade (2.1)
 */
function modules_upgrade($oldVersion)
{
    // Get database information
    $dbconn = xarDB::$dbconn;
    $tables = xarDBGetTables();

    $sitePrefix = xarDB::$prefix;
    $systemPrefix = xarDB::$sysprefix;

    //$tables['module_states'] = $sitePrefix . '_module_states';
    $tables['module_vars'] = $sitePrefix . '_module_vars';

    switch($oldVersion) {
    case '2.02':
        // compatability upgrade, nothing to be done
    case '2.2.0':
        // TODO: use adodb transactions to ensure atomicity?
        // The changes for bug 1716:
        // - add xar_id as primary key
        // - make index on xar_regid unique
        // 1. Add the primary key: save operation
        /*$changes = array('command'     => 'add',
                         'field'       => 'xar_id',
                         'type'        => 'integer',
                         'null'        => false,
                         'unsigned'    => true,
                         'increment'   => true,
                         'primary_key' => true,
                         'first'       => true);
        $query = xarDBAlterTable($tables['module_states'], $changes);
        $result = $dbconn->Execute($query);
        if (!$result) return;

        // Bug #1971 - Have to use GenId to create values for xar_id on
        // existing rows or the create unique index will fail
        $query = "SELECT xar_regid, xar_state
                  FROM " . $tables['module_states'] . "
                  WHERE xar_id IS NULL";
        $result = $dbconn->Execute($query);
        if (!$result) return;

        // Get items from result array
        while (!$result->EOF) {
            list ($regid, $state) = $result->fields;

            $seqId = $dbconn->GenId($tables['module_states']);
            $query = "UPDATE " . $tables['module_states'] . "
                      SET xar_id = $seqId
                      WHERE xar_regid = $regid
                      AND xar_state = $state";
            $updresult = $dbconn->Execute($query);
            if (!$updresult) return;

            $result->MoveNext();
        }

        // Close result set
        $result->Close();

        // 2. Drop the old index
        $indexname = 'i_' . $sitePrefix . '_module_states_regid';
        $query = xarDBDropIndex($tables['module_states'], array('name' => $indexname));
        $result = $dbconn->Execute($query);
        if (!$result) return;

        // 3. Add the new unique index reg_id
        $index = array('name' => $indexname, 'unique' => true, 'fields' => array('xar_regid'));
        $query = xarDBCreateIndex($tables['module_states'], $index);
        $result = $dbconn->Execute($query);
        if (!$result) return;
        */
    case '2.3.0':
        // make sure we dont miss empty variables (which were not passed thru)
        // move here from activate
       if (empty($selstyle)) $selstyle = 'plain';
       if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
       if (empty($hidecore)) $hidecore = 0;
       if (empty($selsort)) $selsort = 'namedesc';

       xarModSetVar('modules', 'hidecore', $hidecore);
       xarModSetVar('modules', 'selstyle', $selstyle);
       xarModSetVar('modules', 'selfilter', $selfilter);
       xarModSetVar('modules', 'selsort', $selsort);

       xarModSetVar('modules', 'disableoverview',0);
       xarModSetVar('modules', 'useicons', true);
    case '2.3.1':
       xarModSetVar('modules', 'itemsperpage', 80);
    case '2.3.2':
       xarModSetVar('modules', 'auto3ptupgrade', FALSE);
    case '2.3.3':
        //upgrade to reflect the fact of db change with index on module vars table
    case '2.3.4':       //current version

    }
    return true;
}

/**
 * Delete the modules module
 *
 * @param none $
 * @returns bool
 */
function modules_delete()
{
    // this module cannot be removed
    return false;
}

?>