<?php
/**
 * Delete an item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * delete an item (the whole item or the dynamic data fields of it)
 *
 * @author the DynamicData module development team
 * @param int $args['itemid'] item id of the original item
 * @param int $args['modid'] module id for the original item
 * @param int $args['itemtype'] item type of the original item
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_delete($args)
{
    extract($args);

    $invalid = array();
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'delete', 'DynamicData');
        throw new BadParameterException(null,$msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $itemtype = 0;
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('DeleteDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

// TODO: test this
    $myobject = Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $myobject->getItem();
    $itemid = $myobject->deleteItem();
    return $itemid;
}
?>