<?php
/**
 * Utility function to pass individual item links
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['itemids'] array of item ids to get
 * @return array containing the itemlink(s) for the item(s).
 */
function blocks_userapi_getitemlinks($args)
{
    extract($args);

    if (empty($itemtype)) {
        $itemtype = 3; // block instances
    }
    $itemlinks = array();

    if (xarSecurityCheck('EditBlock',0)) {
        $showurl = true;
    } else {
        $showurl = false;
    }

    switch ($itemtype)
    {
        case 1: // block types
            $param = array();
            if (!empty($itemids) && count($itemids) == 1) {
                $param['tid'] = $itemids[0];
            }
            $types = xarMod::apiFunc('blocks','user','getallblocktypes',$param);
            if (empty($itemids)) {
                $itemids = array_keys($types);
            }
            foreach ($itemids as $itemid) {
                if (!isset($types[$itemid])) continue;
                $label = $types[$itemid]['module'] . '/' . $types[$itemid]['type'];
                $itemlinks[$itemid] = array('label' => xarVarPrepForDisplay($label),
                                            'title' => xarML('View Block Type'),
                                            'url'   => $showurl ? xarModURL('blocks', 'admin', 'view_types',
                                                                            array('tid' => $itemid)) : '');
            }
            break;

        case 2: // block groups
            $param = array();
            if (!empty($itemids) && count($itemids) == 1) {
                $param['gid'] = $itemids[0];
            }
            $groups = xarMod::apiFunc('blocks','user','getallgroups',$param);
            if (empty($itemids)) {
                $itemids = array_keys($groups);
            }
            foreach ($itemids as $itemid) {
                if (!isset($groups[$itemid])) continue;
                $label = $groups[$itemid]['name'];
                $itemlinks[$itemid] = array('label' => xarVarPrepForDisplay($label),
                                            'title' => xarML('View Block Group'),
                                            'url'   => $showurl ? xarModURL('blocks', 'admin', 'view_groups',
                                                                            array('gid' => $itemid)) : '');
            }
            break;

        case 3: // block instances
        default:
            $param = array();
            if (!empty($itemids) && count($itemids) == 1) {
                $param['bid'] = $itemids[0];
            }
            $instances = xarMod::apiFunc('blocks','user','getall',$param);
            if (empty($itemids)) {
                $itemids = array_keys($instances);
            }
            foreach ($itemids as $itemid) {
                if (!isset($instances[$itemid])) continue;
                $label = $instances[$itemid]['name'];
                $itemlinks[$itemid] = array('label' => xarVarPrepForDisplay($label),
                                            'title' => xarML('Modify Block Instance'),
                                            'url'   => $showurl ? xarModURL('blocks', 'admin', 'modify_instance',
                                                                            array('bid' => $itemid)) : '');
            }
            break;
    }

    return $itemlinks;
}

?>