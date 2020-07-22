<?php
/**
 * Get a specific item field
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * get a specific item field
 * @todo update this with all the new stuff
 *
 * @author the DynamicData module development team
 * @param string $args['module'] module name of the item field to get, or
 * @param int $args['modid'] module id of the item field to get
 * @param int $args['itemtype'] item type of the item field to get
 * @param int $args['itemid'] item id of the item field to get
 * @param string $args['name'] name of the field to get
 * @return mixed value of the field, or false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_userapi_getfield($args)
{
    extract($args);

    if (empty($modid) && !empty($module)) {
        $modid = xarMod::getId($module);
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }

    $invalid = array();
    if (!isset($modid) || !is_numeric($modid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'field name';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'user', 'get', 'DynamicData');
        throw new BadParameterException(null,$msg);
    }

    $object = Dynamic_Object_Master::getObject(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => array($name)));
    if (!isset($object)) return;
    $object->getItem();

    if (!isset($object->properties[$name])) return;
    $property = $object->properties[$name];

    if(!xarSecurityCheck('ReadDynamicDataField',1,'Field',$property->name.':'.$property->type.':'.$property->id)) return;
    if (!isset($property->value)) {
        $value = $property->default;
    } else {
        $value = $property->value;
    }

    return $value;
}

?>