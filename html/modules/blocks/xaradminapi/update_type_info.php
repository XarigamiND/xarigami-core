<?php
/**
 * Read the info details of a block type
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
/**
 * Read the info details of a block type into the database.
 *
 * @access public
 * @param modName the module name (deprecated)
 * @param blockType the block type (deprecated)
 * @param args['tid'] the type id
 * @param args['module'] the module name
 * @param args['type'] the block type
 * @return bool true on success, false on failure
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_update_type_info($args)
{
    extract($args);

    // Get the type details from the database.
    $type = xarMod::apiFunc('blocks', 'user', 'getblocktype', $args);

    if (empty($type)) {
        // No type registered in the database.
        return false;
    }

    // Load and execute the info function of the block.
    $block_info = xarMod::apiFunc('blocks', 'user', 'read_type_info',
        array(
            'module' => $type['module'],
            'type' => $type['type']
        )
    );
    if (empty($block_info)) {return false;}

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $block_types_table = $xartable['block_types'];

    // Update the info column for the block in the database.
    $query = 'UPDATE ' . $block_types_table . ' SET xar_info = ? WHERE xar_id = ?';
    $bind = array(serialize($block_info), $type['tid']);
    $result = $dbconn->Execute($query, $bind);
    if (!$result) {return;}
    if (!sys::isInstall()) {
      xarLogMessage('BLOCKS: Block type '.$type['type'].' for module '. $type['module']. ' was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    }
    return true;
}

?>