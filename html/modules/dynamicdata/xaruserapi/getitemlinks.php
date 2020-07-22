<?php
/**
 * Pass individual item links
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['itemids'] array of item ids to get
 * @return array containing the itemlink(s) for the item(s).
 */
function dynamicdata_userapi_getitemlinks($args)
{
    extract($args);

    $itemlinks = array();
    if (empty($itemtype)) {
        $itemtype = null;
    }
    $status = 1;
    list($properties,$items) = xarMod::apiFunc('dynamicdata','user','getitemsforview',
                                                   // for items managed by DD itself only
                                             array('modid' => xarMod::getId('dynamicdata'),
                                                   'itemtype' => $itemtype,
                                                   'itemids' => $itemids,
                                                   'status' => $status,
                                                  )
                                            );
    if (!isset($items) || !is_array($items) || count($items) == 0) {
       return $itemlinks;
    }

// TODO: make configurable
    $titlefield = '';
    foreach ($properties as $name => $property) {
        // let's use the first textbox property we find for now...
        if ($property->type == 2) {
            $titlefield = $name;
            break;
        }
    }

    // if we didn't have a list of itemids, return all the items we found
    if (empty($itemids)) {
        $itemids = array_keys($items);
    }

    foreach ($itemids as $itemid) {
        if (!empty($titlefield) && isset($items[$itemid][$titlefield])) {
            $label = $items[$itemid][$titlefield];
        } else {
            $label = xarML('Item #(1)',$itemid);
        }
        $itemlinks[$itemid] = array('url'   => xarModURL('dynamicdata', 'user', 'display',
                                                         array('itemtype' => $itemtype,
                                                               'itemid' => $itemid)),
                                    'title' => xarML('Display Item'),
                                    'label' => $label);
    }
    return $itemlinks;
}

?>
