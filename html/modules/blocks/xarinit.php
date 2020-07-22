<?php
/**
 * Initialise the blocks module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * initialise the blocks module
 * @author Xaraya Core Development Team
 */
function blocks_init()
{
    // Get database information
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $prefix = xarDB::$sysprefix;

    // Create tables

    // *_block_groups
    $query = xarDBCreateTable($prefix . '_block_groups',
                             array('xar_id'         => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_name'        => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => ''),
                                   'xar_template'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => false,
                                                             'default'     => '')));
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($prefix . '_block_groups',
                             array('name'   => 'i_' . $prefix . '_block_groups',
                                   'fields' => array('xar_name'),
                                   'unique' => 'true'));
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // *_block_instances
    $query = xarDBCreateTable($prefix . '_block_instances',
                             array('xar_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_type_id'     => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_name'       => array('type'        => 'varchar',
                                                             'size'        => 100,
                                                             'null'        => false,
                                                             'default'     => NULL),
                                   'xar_title'       => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => true,
                                                             'default'     => NULL),
                                   'xar_content'     => array('type'        => 'text',
                                                             'null'        => false),
                                   'xar_template'    => array('type'        => 'varchar',
                                                             'size'        => 255,
                                                             'null'        => true,
                                                             'default'     => NULL),
                                   'xar_state'       => array('type'        => 'integer',
                                                             'size'        => 'tiny',
                                                             'null'        => false,
                                                             'default'     => '2'),
                                   'xar_refresh'     => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_last_update' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0')));

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($prefix . '_block_instances',
                             array('name'   => 'i_' . $prefix . '_block_instances',
                                   'fields' => array('xar_type_id'),
                                   'unique' => false));
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($prefix . '_block_instances',
                             array('name'   => 'i_' . $prefix . '_block_instances_u2',
                                   'fields' => array('xar_name'),
                                   'unique' => true));
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // *_block_types
    $query = xarDBCreateTable($prefix . '_block_types',
        array(
            'xar_id' => array(
                'type'          => 'integer',
                'null'          => false,
                'increment'     => true,
                'primary_key'   => true
            ),
            'xar_type' => array(
                'type'          => 'varchar',
                'size'          => 64,
                'null'          => false,
                'default'       => ''
            ),
            'xar_module' => array(
                'type'          => 'varchar',
                'size'          => 64,
                'null'          => false,
                'default'       => ''
            ),
            'xar_info' => array(
                'type'          => 'text',
                'null'          => true
            )
        )
    );

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($prefix . '_block_types',
                             array('name'   => 'i_' . $prefix . '_block_types2',
                                   'fields' => array('xar_module', 'xar_type'),
                                   'unique' => 'false'));
    $result = $dbconn->Execute($query);
    if (!$result) return;
/*
    TODO: Find a fix for this - Postgres will not allow partial indexes
    $query = xarDBCreateIndex($prefix . '_block_types',
                             array('name'   => 'i_' . $prefix . '_block_types_2',
                                   'fields' => array('xar_type(50)', 'xar_module(50)'),
                                   'unique' => true));
    $result = $dbconn->Execute($query);
    if (!$result) return;
*/
    // *_block_group_instances
    $query = xarDBCreateTable($prefix . '_block_group_instances',
                             array('xar_id'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'increment'   => true,
                                                             'primary_key' => true),
                                   'xar_group_id'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_instance_id' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_template'    => array('type'        => 'varchar',
                                                             'size'        => 100,
                                                             'null'        => true,
                                                             'default'     => NULL),
                                   'xar_position'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0')));

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($prefix . '_block_group_instances',
                              array('name' => 'i_' . $prefix . '_block_group_instances',
                                    'fields' => array('xar_group_id'),
                                    'unique' => false));
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($prefix . '_block_group_instances',
                              array('name' => 'i_' . $prefix . '_block_group_instances_2',
                                    'fields' => array('xar_instance_id'),
                                    'unique' => false));
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // create table for block instance specific output cache configuration
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    // Cache blocks table is not in xartables
    $cacheblockstable =  xarDB::$sysprefix . '_cache_blocks';

    //There was a problem in php 5.0.3, and also sqlite and postgres with L data type do replacing datadict method

    $query = xarDBCreateTable($prefix . '_cache_blocks',
                             array('xar_bid'          => array('type'        => 'integer',
                                                             'null'        => false,
                                                            'default'     => '0'),
                                   'xar_nocache'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_page' => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_user'    => array('type'        => 'integer',
                                                             'null'        => false,
                                                             'default'     => '0'),
                                   'xar_expire'    => array('type'        => 'integer',
                                                             'null'        => true)));

    $result = $dbconn->Execute($query);
    if (!$result) return;

   /*
    // Get a data dictionary object with item create methods.
    $datadict = xarDBNewDataDict($dbconn, 'ALTERTABLE');

    $flds = "
        xar_bid             I           NotNull DEFAULT 0,
        xar_nocache         L           NotNull DEFAULT 0,
        xar_page            L           NotNull DEFAULT 0,
        xar_user            L           NotNull DEFAULT 0,
        xar_expire          I           Null
    ";

    // Create or alter the table as necessary.
    $result = $datadict->changeTable($cacheblockstable, $flds);
    if (!$result) {return;}
    */

    //There was a problem in php 5.0.3, and also sqlite and postgres with L data type do replacing datadict method

    $query = xarDBCreateIndex($prefix . '_cache_blocks',
                              array('name' => 'i_' . $prefix . '_cache_blocks_1',
                                    'fields' => array('xar_bid'),
                                    'unique' => true));
    $result = $dbconn->Execute($query);
    if (!$result) return;
    /*
    // Create a unique key on the xar_bid collumn
    $result = $datadict->createIndex('i_' . xarDB::$prefix . '_cache_blocks_1',
                                     $cacheblockstable,
                                     'xar_bid',
                                     array('UNIQUE'));
   */
    // *_userblocks
    /* Removed Collapsing blocks to see if there is a better solution.
    $query = xarDBCreateTable($prefix . '_userblocks',
                             array('xar_uid'         => array('type'    => 'integer',
                                                             'null'    => false,
                                                             'default' => '0'),
                                   'xar_bid'         => array('type'    => 'varchar',
                                                             'size'    => 32,
                                                             'null'    => false,
                                                             'default' => '0'),
                                   'xar_active'      => array('type'    => 'integer',
                                                             'size'    => 'tiny',
                                                             'null'    => false,
                                                             'default' => '1'),
                                   'xar_last_update' => array('type'    => 'timestamp',
                                                             'null'    => false)));

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $query = xarDBCreateIndex($prefix . '_userblocks',
                             array('name'   => 'i_' . $prefix . '_userblocks',
                                   'fields' => array('xar_uid', 'xar_bid'),
                                   'unique' => true));
    $result = $dbconn->Execute($query);
    if (!$result) return;


    // Register BL tags
    xarTplRegisterTag('blocks', 'blocks-stateicon',
                     array(new xarTemplateAttribute('bid', XAR_TPL_STRING|XAR_TPL_REQUIRED)),
                     'blocks_userapi_handleStateIconTag');
    */

    /* These modvars can be set here now as modules module is installed prior to blocks  if needed */
    //xarModSetVar('blocks','collapseable',1);
    //xarModSetVar('blocks','blocksuparrow','upb.gif');
    //xarModSetVar('blocks','blocksdownarrow','downb.gif');

    //xarModSetVar('blocks', 'selstyle', 'plain');
    xarModSetVar('blocks', 'itemsperpage', 20);

     /* This init function brings our module to version 1.0, run the upgrades for the rest of the initialisation */
    return blocks_upgrade('1.0');

}

/**
 * upgrade the blocks module from an old version
 * @param string $oldversion
 * @return bool true on success
 */
function blocks_upgrade($oldVersion)
{
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $systemPrefix = xarDB::$sysprefix;
    $sitePrefix = xarDB::$prefix;
    switch($oldVersion) {
    case '1.0':
    //fall through

    case '1.0.0':

         xarRemoveInstances('blocks');
        //setup the new ones
        $blockGroupsTable    = $systemPrefix . '_block_groups';
        $blockTypesTable     = $systemPrefix . '_block_types';
        $blockInstancesTable = $systemPrefix . '_block_instances';

        //Set up the block group instances for this module - these are the same as previously defined and retained
        $query1 = "SELECT DISTINCT xar_name FROM $blockGroupsTable";
        $query2 = "SELECT DISTINCT xar_id FROM $blockGroupsTable";
        $instances = array(array('header'  => 'Group Name:',
                             'query'   => $query1,
                             'limit'   => 20),
                       array('header'  => 'Group ID:',
                             'query'   => $query2,
                             'limit'   => 20));

        xarDefineInstance('blocks','BlockGroup',$instances);

        //The block instances differ and now defined on name (not title)
        //These need to be upgraded
        $query1 = "SELECT DISTINCT xar_module FROM $blockTypesTable ";
        $query2 = "SELECT xar_type FROM $blockTypesTable ";
        $query3 = "SELECT DISTINCT instances.xar_name FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id";
        $instances = array(array('header' => 'Module Name:',
                                 'query' => $query1,
                                 'limit' => 20),
                           array('header' => 'Block Type:',
                                 'query' => $query2,
                                 'limit' => 60),
                           array('header' => 'Block Name:',
                                 'query' => $query3,
                                 'limit' => 20));
        xarDefineInstance('blocks','Block',$instances);

        //Set up the security masks
         xarRemoveMasks('blocks');
         /* remove and redefine new ones. The old ones do not seem to be working in any case in installs */

        //Unsure if this  Comment is used at all but left for compatiblity with prior setup
        xarRegisterMask('CommentBlock','All','blocks','All','All','ACCESS_EDIT');

        // Blockgroups - in case people can edit block group
        xarRegisterMask('EditBlockGroup',  'All', 'blockgroup', 'Blockgroup', 'All', 'ACCESS_EDIT');
        //Blocks block? could be a use ...
        xarRegisterMask('ReadBlocksBlock', 'All', 'blocks', 'Block', 'All:All:All', 'ACCESS_OVERVIEW');
        //And standard masks for the rest - keep names the same as any prior so minimal sec checks in templates still work
        xarRegisterMask('ViewBlock',    'All', 'blocks', 'Block', 'All:All:All', 'ACCESS_OVERVIEW');
        xarRegisterMask('ReadBlock',    'All', 'blocks', 'Block', 'All:All:All', 'ACCESS_READ');
        xarRegisterMask('ModerateBlock','All', 'blocks', 'Block', 'All:All:All', 'ACCESS_MODERATE');
        xarRegisterMask('EditBlock',    'All', 'blocks', 'Block', 'All:All:All', 'ACCESS_EDIT');
        xarRegisterMask('AddBlock',     'All', 'blocks', 'Block', 'All:All:All', 'ACCESS_ADD');
        xarRegisterMask('DeleteBlock',  'All', 'blocks', 'Block', 'All:All:All', 'ACCESS_DELETE');
        xarRegisterMask('AdminBlock',   'All', 'blocks', 'Block', 'All:All:All', 'ACCESS_ADMIN');
        //fall through
    case '1.0.1': 
        /* from the Module install - can't do it there in modules module due to block api being included later in install process*/
        if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type',
            array('modName' => 'roles',
                'blockType' => 'online'))) return;

        if (!xarMod::apiFunc('blocks','admin','register_block_type',
            array('modName' => 'roles',
                'blockType' => 'user'))) return;

        if (!xarMod::apiFunc('blocks','admin','register_block_type',
            array('modName' => 'roles',
                'blockType' => 'language'))) return;
         //delete old block - now in authsystem mod
          xarModAPIfunc('blocks', 'admin', 'delete_type', array('module' => 'roles', 'type' => 'login'));

        //remove masks that are not used, incorrectly setup or incompatible
        xarUnRegisterMask('EditBlockGroup');
        xarUnRegisterMask('CommentBlock');
        //recreate correctly
        xarRegisterMask('EditBlockGroup',  'All', 'blocks', 'Blockgroup', 'All:All', 'ACCESS_EDIT');
        xarRegisterMask('ReadBlockGroup',  'All', 'blocks', 'Blockgroup', 'All:All', 'ACCESS_READ');
        xarRegisterMask('AddBlockGroup',   'All', 'blocks', 'Blockgroup', 'All:All', 'ACCESS_ADD');
        xarRegisterMask('DeleteBlockGroup','All', 'blocks', 'Blockgroup', 'All:All', 'ACCESS_DELETE');
        xarRegisterMask('AdminBlockGroup', 'All', 'blocks', 'Blockgroup', 'All:All', 'ACCESS_ADMIN');
    case '1.0.2':

    case '1.0.3':
       xarRemoveInstances('base','Block');
       xarRemoveInstances('roles','Block');
       xarRemoveInstances('themes','Block');
    case '1.0.4': /* current version */
        //we want to be able to set blockgroup privileges by name as well as all blockgroup privs
        //let us correct the existing instances by removing an SQL Line
        $securityinstances = $sitePrefix . '_security_instances';    
        $query = "DELETE FROM  $securityinstances 
                  WHERE xar_module = 'blocks' and xar_component = 'BlockGroup' and xar_header != 'Group Name:'"; 

        $result = $dbconn->Execute($query);
    case '1.1.0': /* current version */
        break;    
    }
    /* Update successful */
    return true;
}

/**
 * delete the blocks module
 * @return bool false. Module cannot be deleted
 */
function blocks_delete()
{
  //this module cannot be removed
  return false;
}

?>