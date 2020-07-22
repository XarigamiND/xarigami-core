<?php
/**
 * Delete a block instance
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * delete a block
 * @param int bid the ID of the block to delete
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Invalid parameter');
        throw new BadParameterException(null,$msg);
    }

    $blockinfo = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => (int)$bid));

        // Security
    if(!xarSecurityCheck('DeleteBlock',0,'Block',"{$blockinfo['module']}:{$blockinfo['type']}:{$blockinfo['name']}")) return xarResponseForbidden();

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "DELETE FROM $block_group_instances_table
              WHERE xar_instance_id = ?";
    $result = $dbconn->Execute($query,array($bid));
    if (!$result) {return;}

    $query = "DELETE FROM $block_instances_table
              WHERE xar_id = ?";
    $result = $dbconn->Execute($query,array($bid));
    if (!$result) {return;}

    //let's make sure the cache blocks instance as well is deleted, if it exists bug #5815
    if (!empty($xartable['cache_blocks'])) {
        $deletecacheblock = xarMod::apiFunc('blocks','admin','delete_cacheinstance',
            array('bid' => (int)$bid,
                  'blocktype'   => $blockinfo['type'],
                  'blockmodule' => $blockinfo['module'],
                  'blockname'   => $blockinfo['name']));
    }

    xarMod::apiFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarMod::callHooks('item', 'delete', $bid, $args);
    xarLogMessage('BLOCKS: Block with id '.$bid.' was deleted by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>