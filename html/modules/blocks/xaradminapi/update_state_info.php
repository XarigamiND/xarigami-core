<?php
/**
 * Update state info
 *
 * @package modules
 * @copyright (C) 2011 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @link http://xarigami.com/projects/xarigami_core
/**
 * Update state of block in database
 *
 * @access public
 * @param args['bid] the block id
 * @return bool true on success, false on failure
 */
function blocks_adminapi_update_state_info($args)
{
    extract($args);
    if (!isset($bid) || empty($bid)) return;
    if (!isset($blockstate) || !in_array($blockstate,array(0,1,2))) return;
    $instance = xarMod::apiFunc('blocks', 'user', 'get', array('bid' => $bid));
    if (!$instance || !is_array($instance) ) return;

    // Security Check
    if(!xarSecurityCheck('EditBlock',0,'Block',"{$instance['module']}:{$instance['type']}:{$instance['name']}")) {
        return xarResponse::Forbidden();
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $block_instance_table = $xartable['block_instances'];

    // Update the info column for the block in the database.
    $query = 'UPDATE ' . $block_instance_table . ' SET xar_state = ? WHERE xar_id = ?';
    $bind = array($blockstate,$bid);
    $result = $dbconn->Execute($query, $bind);
    if (!$result) {return;}
    if (!sys::isInstall()) {
      xarLogMessage('BLOCKS: Block named '.$instance['name'].' of type '.$instance['type'].' for module '. $instance['module']. ' was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    }
    return true;
}

?>