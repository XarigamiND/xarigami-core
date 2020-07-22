<?php
/**
 * Get a dynamic object
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * get a dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @param $args['itemid'] itemid type of the item field to get <optional>
 * @return object a particular Dynamic Object
 */
function dynamicdata_userapi_getobject($args)
{
    $dynamicMaster = new Dynamic_Object_Master($args);

    if (empty($args['itemtype']) && !empty($args['name'])) {
        $info = $dynamicMaster->getObjectInfo($args);
        $args['moduleid'] = $info['moduleid'];
        $args['itemtype'] = $info['itemtype'];

    }
    if (empty($args['moduleid']) && !empty($args['module'])) {
       $args['moduleid'] = xarMod::getId($args['module']);
    }
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }

    $result = $dynamicMaster->getObject($args);

    return $result;
}

?>