<?php
/**
 * Update attributes of a Block
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
 * update attributes of a block group
 *
 * @param $args['id'] the ID of the group to update (deprec)
 * @param $args['gid'] the ID of the group to update
 * @param $args['name'] the new name of the group
 * @param $args['template'] the new default template of the group
 * @param $args['instance_order'] the new instance sequence (array of bid)
 * @return bool true on success, false on failure
 */
function blocks_adminapi_update_group($args)
{
    // Get arguments from argument array
    extract($args);

    if (!empty($id)) {
        // Legacy.
        $gid = $id;
    }

    // Security -  name could have been changed but we might want this
    if(!xarSecurityCheck('EditBlockGroup', 0, 'Blockgroup',"{$name}")) {return xarResponseForbidden();}

    if (!is_numeric($id)) {return;}

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $block_groups_table = $xartable['block_groups'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = 'UPDATE ' . $block_groups_table
        . ' SET xar_name = ?, xar_template = ?'
        . ' WHERE xar_id = ?';
    $result = $dbconn->Execute($query, array($name, $template, $gid));
    if (!$result) {return;}

    if (!empty($instance_order)) {
        $position = 1;
        foreach ($instance_order as $instance_id) {
            $query = 'UPDATE ' . $block_group_instances_table
                . ' SET xar_position = ?'
                . ' WHERE xar_instance_id = ? '
                . ' AND xar_group_id = ? '
                . ' AND xar_position <> ?';
            if (is_numeric($instance_id)) {
                $result = $dbconn->Execute($query, array($position, $instance_id, $gid, $position));
                if (!$result) {return;}
            }

            $position += 1;
        }

        // Do a resequence tidy-up, in case the instance list passed in was not complete.
        // Limit the reorder to just this group to avoid updating more than is necessary.
        xarModAPIfunc('blocks', 'admin', 'resequence', array('gid' => $gid));
    }
      xarLogMessage('BLOCKS: Block group with id '.$gid.' was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>