<?php
/**
 * Update fields for an item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * update fields for an item - hook for ('item','update','API')
 * Needs $extrainfo['dd_*'] from arguments, or 'dd_*' from input
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_updatehook($args)
{
    $verbose = false;

    extract($args);

    if (!isset($dd_function) || $dd_function != 'createhook') {
        $dd_function = 'updatehook';
    }

    if (!isset($extrainfo) || !is_array($extrainfo)) {
        //return false;
        return array();
    }
    if (!isset($objectid) || !is_numeric($objectid)) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }
    // We can exit immediately if the status flag is set because we are just updating
    // the status in the articles or other content module that works on that principle
    // Bug 1960 and 3161
    if (xarCoreCache::isCached('Hooks.all','noupdate') || !empty($extrainfo['statusflag'])){
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
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    $myobject = Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                                         'itemtype' => $itemtype,
                                                         'itemid'   => $itemid));

    if (!isset($myobject)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object', 'admin', $dd_function, 'dynamicdata');
        throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    $myobject->getItem();

    // use the values passed via $extrainfo if available
    $isvalid = $myobject->checkInput($extrainfo);

    if ($isvalid === FALSE) {
         $extrainfo['invalid'] = array();
        if ($verbose) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                        'input', 'admin', $dd_function, 'dynamicdata');
            // Note : we can't use templating here
            $msg .= ' : ';
            foreach ($myobject->properties as $property) {
                if (!empty($property->invalid)) {
                $extrainfo['invalid'][$property->name] = $property->invalid . ' ';
                    $msg .= xarML('#(1) = invalid #(2)',$property->label,$property->invalid);
                    $msg .= ' - ';
                }
            }
        } else {
            $msg = '';

            foreach ($myobject->properties as $property) {
                if (!empty($property->invalid)) {

                     $extrainfo['invalid'][$property->name] = $property->invalid . ' ';
                }
            }
        }
        xarLogMessage("DYNAMIC DATA UPDATEHOOK - field with invalid data for #(1)",$msg);
       // throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if ($dd_function == 'createhook') {
        $itemid = $myobject->createItem();
    } else {
        $itemid = $myobject->updateItem();
    }

    if (empty($itemid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'create/update', 'admin', $dd_function, 'dynamicdata');
        //throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Return the extra info
    return $extrainfo;
}
?>
