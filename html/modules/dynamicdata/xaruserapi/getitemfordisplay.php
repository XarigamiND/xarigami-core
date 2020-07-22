<?php
/**
 * Return the properties for an item
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
 * return the properties for an item
 *
 * @param array $args array containing the items or fields to show
 * @return array containing a reference to the properties of the item
 */
function dynamicdata_userapi_getitemfordisplay($args)
{
    $args['getobject'] = 1;
    $object = xarMod::apiFunc('dynamicdata','user','getitem',$args);
    $properties = array();
    if (isset($object)) {
        $properties = & $object->getProperties();
    }
    $item = array(& $properties);
    return $item;
}

?>