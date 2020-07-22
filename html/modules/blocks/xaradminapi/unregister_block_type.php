<?php
/**
 * Unregister block types
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Unregister block type
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @return bool true on success, false on failure
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_unregister_block_type($args)
{
    $res = xarMod::apiFunc('blocks','admin','block_type_exists',$args);
    if (!isset($res)) return; // throw back
    if (!$res) return true; // Already unregistered

    extract($args);

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $block_types_table = $xartable['block_types'];
    $block_instances_table = $xartable['block_instances'];

    // First we need to retrieve the block ids and remove
    // the corresponding id's from the xar_block_instances
    // and xar_block_group_instances tables
    $query = "SELECT    inst.xar_id as id
              FROM      $block_instances_table as inst,
                        $block_types_table as btypes
              WHERE     btypes.xar_id = inst.xar_type_id
              AND       btypes.xar_module = ?
              AND       btypes.xar_type = ?";

    $result = $dbconn->Execute($query,array($modName,$blockType));
    if (!$result) return;

    while (!$result->EOF) {
        list($bid) = $result->fields;
        // Pass ids to API
        xarMod::apiFunc('blocks',
                      'admin',
                      'delete_instance', array('bid' => $bid));
        $result->MoveNext();
    }

    $result->Close();

    // Delete the block type
    $query = "DELETE FROM $block_types_table WHERE xar_module = ? AND xar_type = ?";
    $result = $dbconn->Execute($query,array($modName,$blockType));
    if (!$result) return;

    return true;
}

?>