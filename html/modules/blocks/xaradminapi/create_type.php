<?php
/**
 * Register block type
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Register block type
 *
 * @param args['module'] the module name
 * @param args['type'] the block type
 * @param args['info'] the info array for the block type
 * @return int ID of block type registered (even if already registered)
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_create_type($args)
{
    extract($args);

    // Legacy - we want to use lower-case 'module' and 'type' now.
    if (!empty($modName)) {$module = $modName;}
    if (!empty($blockType)) {$type = $blockType;}
    if (empty($info)) {
        $info = NULL;
    } else {
        // Prepare the info array for storage.
        $info = serialize($info);
    }

    $origtype = xarMod::apiFunc('blocks', 'user', 'getblocktype', array('module'=>$module, 'type'=>$type));

    if (!empty($origtype)) {
        // Already registered - no need to raise an error, since we are where we wanted to be.
        // Just return the type ID.
        return $origtype['tid'];
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $block_types_table = $xartable['block_types'];

    $nextID = $dbconn->GenId($block_types_table);
    $query = 'INSERT INTO ' . $block_types_table
        . ' (xar_id, xar_module, xar_type, xar_info) VALUES (?, ?, ?, ?)';
    $result = $dbconn->Execute($query, array($nextID, $module, $type, $info));
    if (!$result) {return;}

    if (empty($nextID)) {
        $nextID = $dbconn->PO_Insert_ID($block_types_table, 'xar_id');
    }

    // Update the block info details.
    xarMod::apiFunc('blocks', 'admin', 'update_type_info', array('tid' => $nextID));

    return $nextID;
}

?>