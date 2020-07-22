<?php
/**
 * Initialise the Authsystem module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami AuthLdap
 * @copyright (C) 2010-2012 2skies.com
 * @link http://xarigami.com/projects
 * @author Xarigami Team
 */

/**
 * Initialise the Authsystem module
 *
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 * @access public
 * @param none $
 * @return bool
 */
function authsystem_init()
{
    xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    xarRegisterPrivilege('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');

    /* DEFINE MASKS */
    xarRegisterMask('ViewLogin','All','blocks','Block','authsystem:login:All','ACCESS_READ');
    xarRegisterMask('ViewAuthsystemBlocks','All','blocks','Block','authsystem:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');

    /* Define Module vars */
    xarModSetVar('authsystem', 'lockouttime', 15);
    xarModSetVar('authsystem', 'lockouttries', 3);
    xarModSetVar('authsystem', 'uselockout', false);
    xarModSetVar('authsystem', 'SupportShortURLs', false);
    xarModSetVar('authsystem', 'useModuleAlias', false);
    /* This init function brings our module to version 0.91.0, run the upgrades for the rest of the initialisation */

    return authsystem_upgrade('0.91.0');
}
/*
 * We don't have all modules activated at install time
 */
function authsystem_activate()
{
  return true;
}

/**
 * Upgrade the authsystem module from an old version
 *
 * @access public
 * @param oldVersion $
 * @return bool true on success of upgrade
 */
function authsystem_upgrade($oldVersion)
{
    /* Upgrade dependent on old version number */
    switch ($oldVersion) {
        case '0.91':
        case '0.91.0':
            /* DEFINE PRIVILEGES
             * Privileges module is loaded prior to Authsystem module
             */
            xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
            xarRegisterPrivilege('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');

            /* DEFINE MASKS */
            xarRegisterMask('ViewLogin','All','blocks','Block','authsystem:login:All','ACCESS_READ');
            xarRegisterMask('ViewAuthsystemBlocks','All','blocks','Block','authsystem:All:All','ACCESS_OVERVIEW');
            xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
            xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
            xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');

            /* Define Module vars */
            xarModSetVar('authsystem', 'lockouttime', 15);
            xarModSetVar('authsystem', 'lockouttries', 3);
            xarModSetVar('authsystem', 'uselockout', false);

           //Set the default authmodule if not already set - retain for backward compat
           //we have to hard code as at install  authsystem is not installed yet ..
           // cannot use xarMod::getId('authsystem'));
           //TODO: fix this
           xarModSetVar('roles', 'defaultauthmodule', 42);

           // Get database setup
           $dbconn = xarDB::$dbconn;
           $xartable = xarDBGetTables();
           $systemPrefix = xarDB::$sysprefix;
           $modulesTable = $systemPrefix .'_modules';
           $modid=xarMod::getId('authsystem');
           // update the modversion class and admin capable
           $query = "UPDATE $modulesTable
                     SET xar_class         = 'Authentication',
                         xar_admin_capable = 1
                     WHERE xar_regid = ?";
           $bindvars = array($modid);
           $result = $dbconn->Execute($query,$bindvars);
           if (!$result) return;

           // Create the login block type
            $bid = xarMod::apiFunc('blocks','admin','register_block_type',
                   array('modName' => 'authsystem',
                         'blockType' => 'login'));
           if (!$bid) return;

        case '1.0.0':
            /* Define instances for authsystem blocks  */
           $dbconn = xarDB::$dbconn;
           $xartable = xarDBGetTables();
           $blockinstancetable =xarDB::$prefix . '_block_instances';
           $blocktypestable = xarDB::$prefix . '_block_types';

           $query = "SELECT DISTINCT xar_name FROM $blockinstancetable as instances
                    LEFT JOIN $blocktypestable as btypes
                    ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'authsystem'";
           $instances = array(
                        array('header' => 'Authsystem Block Name:',
                                'query' => $query,
                                'limit' => 20
                            )
                    );
          xarDefineInstance('authsystem','Block',$instances);

        case '1.0.1':
            //remove instances from modules where block instances are created
            //the block security rework is complete (xarigami 1.1.4)
             xarRemoveInstances('authsystem');

        case '1.0.2': 
            xarModSetVar('authsystem', 'useauthcheck', FALSE);
        case '1.0.3': // current version

        break;

    }
    // Update successful
    return true;
}

/**
 * Delete the authsystem module
 *
 * @access public
 * @param none $
 * @return bool true on success of deletion
 */
function authsystem_delete()
{
    /* Get all available block types for this module */
    $blocktypes = xarModAPIfunc(
        'blocks', 'user', 'getallblocktypes',
        array('module' => 'authsystem')
    );

    /* Delete block types. */
    if (is_array($blocktypes) && !empty($blocktypes)) {
        foreach($blocktypes as $blocktype) {
            $result = xarModAPIfunc(
                'blocks', 'admin', 'delete_type', $blocktype
            );
        }
    }

    /* Remove modvars, instances and masks */
    xarModDelAllVars('authsystem');
    xarRemoveMasks('authsystem');
    xarRemoveInstances('authsystem');

    /* Deletion successful */
    return true;
}

?>