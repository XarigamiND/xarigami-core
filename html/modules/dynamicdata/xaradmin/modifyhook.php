<?php
/**
 * Modify Dynamic data for an Item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * modify dynamicdata for an item - hook for ('item','modify','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_modifyhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'dynamicdata');
        //throw new BadParameterException(null,$msg);
        return array();
    }

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID', 'admin', 'modifyhook', 'dynamicdata');
        //throw new EmptyParameterException(null,$msg);'
        return $extrainfo;
    }
    //used when returning from manual modify check on invalid in DD
    //we want to ensure checkinfo is called and info passed into invalids
    if (!isset($extrainfo['checkinput'])) $extrainfo['checkinput'] = FALSE;

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarMod::getName();
    } else {
        $modname = $extrainfo['module'];
    }

    $modid = xarMod::getId($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'modifyhook', 'dynamicdata');
        throw new EmptyParameterException(null,$msg);
    }

    if (!empty($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }

    $objectargs = array('moduleid' => $modid,
                       'itemtype' => $itemtype,
                       'itemid'   => $itemid);

    $myobjectmaster = new Dynamic_Object_Master($objectargs);
    $object = $myobjectmaster->getObject($objectargs);
    if (!isset($object)) return;

    $object->getItem();

    // if we are in preview mode, we need to check for any preview values
    if (!xarVarFetch('preview', 'isset', $preview,  NULL, XARVAR_DONT_SET)) {return;}

    if (!empty($preview) || $extrainfo['checkinput'] == TRUE ) {
        $invalid = $object->checkInput();
    }

    if (!empty($object->template)) {
        $template = $object->template;
    } else {
        $template = $object->name;
    }

    return xarTplModule('dynamicdata','admin','modifyhook',
                        array('properties' => & $object->properties),
                        $template);
}

?>