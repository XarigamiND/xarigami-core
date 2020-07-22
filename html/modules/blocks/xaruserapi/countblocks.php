<?php
/**
 * Count the number of blocks
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Count the number of blocks
 *
 * @access public
 * @param string modName the module name
 * @param string $args['typeid'] id of the block type (optional)
 * @param string $args['state'] state of the block optional)
 * @return int count of block types that meet the required criteria
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_userapi_countblocks($args)
{
    extract($args);

    $where = array();
    $bind = array();

    if (!empty($state)) {
        $where[] = 'xar_state = ?';
        $bind[] = $state;
    }

    if (!empty($typeid)) {
        $where[] = 'xar_typeid = ?';
        $bind[] = $typeid;
    }

    if (!empty($filter)) {
        $where[] = "xar_name LIKE ?";
        $bind[] = '%'.$filter.'%';

    }
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $blockinstancestable = $xartable['blockinstances'];

    $query = 'SELECT xar_id, xar_name FROM ' . $blockinstancestable;

    if (!empty($where)) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }

    $result = $dbconn->Execute($query, $bind);
    if (!$result) {return;}

    for (; !$result->EOF; $result->MoveNext()) {

      list($gid, $name) = $result->fields;

        if(TRUE==$privcheck) {
            if (xarSecurityCheck('EditBlock',0,'Block',"All:All:{$name}")){
                $items[] = array('gid'   => $gid,'name'   => $name);
            }
        } else  {
            $items[] = array('gid'   => $gid,'name'   => $name);
        }
   }
   if (isset($items)) {
        $count = count($items);
        return (int)$count;
   } else {
        return;
   }
}

?>