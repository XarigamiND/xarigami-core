<?php
/**
 * Create a new property field for an object
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
 * create a new property field for an object
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the property to create
 * @param $args['label'] label of the property to create
 * @param $args['objectid'] object id of the property to create
 * @param $args['moduleid'] module id of the property to create
 * @param $args['itemtype'] item type of the property to create
 * @param $args['type'] type of the property to create
 * @param $args['default'] default of the property to create
 * @param $args['source'] data source for the property (dynamic_data table or other)
 * @param $args['status'] status of the property to create (disabled/active/...)
 * @param $args['order'] order of the property to create
 * @param $args['validation'] validation of the property to create
 * @return int property ID on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createproperty($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($name) || !is_string($name)) {
        $invalid[] = 'name';
    }
    if (!isset($type) || !is_numeric($type)) {
        $invalid[] = 'type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'createproperty', 'DynamicData');
        throw new BadParameterException(null,$msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AdminDynamicDataField',1,'Field',"$name:$type:All")) return;

    if (empty($moduleid)) {
        // defaults to the current module
        $moduleid = xarMod::getId(xarMod::getName());
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    $itemid = 0;

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
    if(!xarSecurityCheck('AdminDynamicDataItem',1,'Item',"$moduleid:$itemtype:All")) return;

    // get the properties of the 'properties' object
    $fields = xarMod::apiFunc('dynamicdata','user','getprop',
                            array('objectid' => 2)); // the properties

    $values = array();
    // the acceptable arguments correspond to the property names !
    foreach ($fields as $name => $field) {
        if (isset($args[$name])) {
            $values[$name] = $args[$name];
        }
    }
/* this is already done via the table definition of xar_dynamic_properties
    // fill in some defaults if necessary
    if (empty($fields['source']['value'])) {
        $fields['source']['value'] = 'dynamic_data';
    }
    if (empty($fields['validation']['value'])) {
        $fields['validation']['value'] = '';
    }
*/

    $propid = xarMod::apiFunc('dynamicdata', 'admin', 'create',
                            array('modid'    => xarMod::getId('dynamicdata'), //$moduleid,
                                  'itemtype' => 1, //$itemtype,
                                  'itemid'   => $itemid,
                                  'values'   => $values));
    if (!isset($propid)) return;
    return $propid;
}
?>