<?php
/**
 * Select dynamicdata for a new item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * select dynamicdata for a new item - hook for ('item','new','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_newhook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'modifyhook', 'dynamicdata');
        throw new EmptyParameterException(null,$msg);
    }

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
        $itemtype = 0;
    }
    //se if we are coming from a create process
    if (!isset($extrainfo['checkinput'])) $extrainfo['checkinput'] = FALSE;

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } elseif (isset($objectid)) {
        $itemid = $objectid;
    } else {
        $itemid = 0;
    }
    $object = Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                       'itemtype' => $itemtype,
                                       'itemid'   => $itemid));
    if (!isset($object)) return;

    // if we are in preview mode, we need to check for any preview values
    if (!xarVarFetch('preview', 'isset', $preview,  NULL, XARVAR_DONT_SET)) {return;}
    if (!empty($preview) || $extrainfo['checkinput'] == TRUE ) {
        $object->checkInput();
    }

    if (!empty($object->template)) {
        $template = $object->template;
    } else {
        $template = $object->name;
    }
    return xarTplModule('dynamicdata','admin','newhook',
                        array('properties' => & $object->properties),
                        $template);
}

?>