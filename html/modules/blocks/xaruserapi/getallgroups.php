<?php
/**
 * Get a single block type.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Get a single block type.
 *
 * @param gid Group ID (optional)
 * @param name Group name (optional)
 * @param order Sort order: name or id (optional)*
 * @return array of block groups, keyed on block group ID (gid)
 * @author Jason Judge
 */

function blocks_userapi_getallgroups($args)
{
    extract($args);

    $where = array();
    $bind = array();

    if (!empty($gid)) {
        $where[] = 'xar_id = ?';
        $bind[] = $gid;
    }

    if (!empty($name)) {
        $where[] = 'xar_name = ?';
        $bind[] = $name;
    }

    if (!empty($where)) {
        $where_clause = ' WHERE ' . implode(' AND ', $where);
    } else {
        $where_clause = '';
    }

    // Can order by name and id
    if (!empty($order) && xarVarValidate('strlist:,|:pre:trim:passthru:enum:name:id', $order, true)) {
        $orderby = ' ORDER BY xar_' . implode(', xar_', explode(',', $order));
    } else {
        $orderby = '';
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $block_groups_table = $xartable['block_groups'];
    $query = 'SELECT xar_id as gid, xar_name as name, xar_template as template'
        . ' FROM ' . $block_groups_table . $where_clause . $orderby;
    $result = $dbconn->Execute($query, $bind);
    if (!$result) {return;}

    $block_groups = array();
    while(!$result->EOF) {
        $group = $result->GetRowAssoc(false);
        $block_groups[$group['gid']] = $group;
        $result->MoveNext();
    }

    return $block_groups;
}

?>