<?php
/**
 * Get information about a defined dynamic object
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * get information about a defined dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] id of the object you're looking for, or
 * @param $args['moduleid'] module id of the item field to get
 * @param $args['itemtype'] item type of the item field to get
 * @return array of object definitions
 * @throws DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getobjectinfo($args)
{
    if (empty($args['moduleid']) && !empty($args['modid'])) {
       $args['moduleid'] = $args['modid'];
    }
    $objectMaster = new Dynamic_Object_Master($args);
    return  $objectMaster->getObjectInfo($args);
}

?>
