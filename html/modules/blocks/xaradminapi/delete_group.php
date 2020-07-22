<?php
/**
 * Delete a block group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * delete a group
 * @author Jim McDonald, Paul Rosania
 * @param int $args['gid'] the ID of the block group to delete
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete_group($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($gid) || !is_numeric($gid)) {
        $msg = xarML('Invalid parameter');
        throw new BadParameterException(null,$msg);
    }

    // Security
     if(!xarSecurityCheck('DeleteBlockGroup', 0, 'Blockgroup', "{$name}")) {return xarResponseForbidden();}

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $block_groups_table = $xartable['block_groups'];
    $block_group_instances_table = $xartable['block_group_instances'];

    // Delete group-instance links
    $query = "DELETE FROM $block_group_instances_table
              WHERE xar_group_id = " . $gid;
    $result = $dbconn->Execute($query);
    if (!$result) {return;}

    // Delete block group definition
    $query = "DELETE FROM $block_groups_table
              WHERE xar_id = ?";
    $result = $dbconn->Execute($query,array($gid));
    if (!$result) {return;}
     xarLogMessage('BLOCKS: Block group with id '.$gid.' was deleted by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>