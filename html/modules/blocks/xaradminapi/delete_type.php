<?php
/**
 * Delete a block type
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Delete block type
 *
 * @author Xaraya Core Development Team
 * @access public
 * @param string module the module name
 * @param int type the block type
 * @param blockType the block type (deprec)
 * @param string modName the module name (deprec)
 * @return bool true on success, false on failure
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_delete_type($args)
{
    extract($args);

    // Legacy.
    if (!empty($modName)) {$module = $modName;}
    if (!empty($blockType)) {$type = $blockType;}

    $count = xarMod::apiFunc(
        'blocks', 'user', 'countblocktypes',
        array('module' => $module, 'type' => $type)
    );

    if (!isset($count)) {return;}
    if ($count == 0) {
        // Already deleted.
        return true;
    }


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

    $result = $dbconn->Execute($query, array($module, $type));
    if (!$result) return;

    while (!$result->EOF) {
        list($bid) = $result->fields;

        // Pass ids to API
        xarMod::apiFunc('blocks', 'admin', 'delete_instance', array('bid' => $bid)
                    );

        $result->MoveNext();
    }

    $result->Close();

    // Delete the block type
    $query = "DELETE FROM $block_types_table WHERE xar_module = ? AND xar_type = ?";
    $result = $dbconn->Execute($query, array($module, $type));
    if (!$result) return;
      xarLogMessage('BLOCKS: Block type for module '.$module.' and type '.$type.' was deleted by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>