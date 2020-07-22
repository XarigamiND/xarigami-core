<?php
/**
 * Delete fields for an item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * delete fields for an item - hook for ('item','delete','API')
 *
 * @param int $args['objectid'] ID of the object
 * @param array $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_deletehook($args)
{
    extract($args);

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object id', 'admin', 'createhook', 'dynamicdata');
        throw new BadParameterException(null,$msg);
        return $extrainfo;
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'extrainfo', 'admin', 'createhook', 'dynamicdata');
         throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //hmm - what do we do here now with new exceptions? check
        return $extrainfo;
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
                    'module name', 'admin', 'createhook', 'dynamicdata');
        throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!empty($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }
    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'deletehook', 'dynamicdata');
        throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if (!xarMod::apiFunc('dynamicdata', 'admin', 'delete',
                      array('modid'    => $modid,
                            'itemtype' => $itemtype,
                            'itemid'   => $itemid))) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    return $extrainfo;
}
?>