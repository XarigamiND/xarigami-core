<?php
/**
 * Count the number of block types
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Count the number of block types [of a given name or module]
 *
 * @author Jason Judge
 * @access public
 * @param string modName the module name
 * @param string $args['type'] name of the block type (optional)
 * @param string $args['module'] name of the module (optional)
 * @return int count of block types that meet the required criteria
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_userapi_countblocktypes($args)
{
    extract($args);

    $where = array();
    $bind = array();

    if (!empty($module)) {
        $where[] = 'xar_module = ?';
        $bind[] = $module;
    }

    if (!empty($type)) {
        $where[] = 'xar_type = ?';
        $bind[] = $type;
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $block_types_table = $xartable['block_types'];

    $query = 'SELECT count(xar_id) FROM ' . $block_types_table;

    if (!empty($where)) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }

    $result = $dbconn->Execute($query, $bind);
    if (!$result) {return;}

    list ($count) = $result->fields;
    return (int)$count;
}

?>