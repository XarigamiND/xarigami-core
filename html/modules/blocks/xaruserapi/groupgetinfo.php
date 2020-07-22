<?php
/**
 * Get block group information
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Get block group information
 *
 * @access public
 * @param integer blockGroupId the block group id
 * @param string blockGroupName the block group name
 * @return array lock information
 * @throws EmptyParameterException
 */
function blocks_userapi_groupgetinfo($args)
{
    extract($args);

    if (empty($gid) || !is_numeric($gid)) {$gid = 0;}

    if (empty($name)) {$name = '';}

    if (empty($name) && empty($gid)) {
         throw new EmptyParameterException('name or gid');
    }

    if (xarCoreCache::isCached('Block.Group.Infos', $gid)) {
        return xarCoreCache::getCached('Block.Group.Infos', $gid);
    }

    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $blockInstancesTable      = $tables['block_instances'];
    $blockTypesTable          = $tables['block_types'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockGroupInstancesTable = $tables['block_group_instances'];

    $query = 'SELECT    xar_id as id,
                        xar_name as name,
                        xar_template as template
              FROM      ' . $blockGroupsTable;

    if (!empty($gid)) {
        $query .= ' WHERE xar_id = ?';
        $bindvars = array($gid);
    } elseif (!empty($name)) {
        $query .= ' WHERE xar_name = ?';
        $bindvars = array($name);
    }

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) {return;}

    // Return if we don't get exactly one result.
    if ($result->PO_RecordCount() != 1) {
        return;
    }

    $group = $result->GetRowAssoc(false);
    $result->Close();

    // If the name was used to find the group, then get the GID from the fetched group.
    if (empty($gid)) {
        $gid = $group['id'];
    }

    // Query for instances in this group
    $query = "SELECT    inst.xar_id as id,
                        btypes.xar_type as type,
                        btypes.xar_module as module,
                        inst.xar_title as title,
                        inst.xar_name as name,
                        group_inst.xar_position as position
              FROM      $blockGroupInstancesTable as group_inst
              LEFT JOIN $blockGroupsTable as bgroups
              ON        group_inst.xar_group_id = bgroups.xar_id
              LEFT JOIN $blockInstancesTable as inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $blockTypesTable as btypes
              ON        btypes.xar_id = inst.xar_type_id
              WHERE     bgroups.xar_id = ?
              ORDER BY  group_inst.xar_position ASC";

    $result = $dbconn->Execute($query,array($gid));
    if (!$result) {return;}

    // Load up list of group's instances
    $instances = array();
     while(!$result->EOF) {
        $instances[] = $result->GetRowAssoc(false);
        $result->MoveNext();
    }
    $result->close();

    $group['instances'] = $instances;

    xarCoreCache::setCached('Block.Group.Infos', $gid, $group);

    return $group;
}

?>