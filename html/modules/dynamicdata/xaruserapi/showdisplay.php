<?php
/**
 * Display an item in a template
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
// TODO: move this to some common place in Xarigami (base module ?)
 * display an item in a template
 *
 * @param $args array containing the item or fields to show
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showdisplay($args)
{
    extract($args);

    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
    }
    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }
   if (empty($tplmodule)) {
        $tplmodule = 'dynamicdata';
    }
    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($module)) {
        $modname = xarMod::getName();
    } else {
        $modname = $module;
    }

    if (is_numeric($modname)) {
        $modid = $modname;
        $modinfo = xarMod::getInfo($modid);
        $modname = $modinfo['name'];
    } else {
        $modid = xarMod::getId($modname);
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'user', 'showdisplay', 'dynamicdata');
        throw new BadParameterException(null,$msg);
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

    // try getting the item id via input variables if necessary
    if (!isset($itemid) || !is_numeric($itemid)) {
        if (!xarVarFetch('itemid', 'isset', $itemid,  NULL, XARVAR_DONT_SET)) {return;}
    }

// TODO: what kind of security checks do we want/need here ?
    if(!xarSecurityCheck('ReadDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    // we got everything via template parameters
    if (isset($fields) && is_array($fields) && count($fields) > 0) {
        return xarTplModule('dynamicdata','user','showdisplay',
                            array('fields' => $fields,
                                  'tplmodule' => $tplmodule,
                                  'layout' => $layout),
                            $template);
    }

    // check the optional field list
    if (!empty($fieldlist)) {
        // support comma-separated field list
        if (is_string($fieldlist)) {
            $myfieldlist = explode(',',$fieldlist);
        // and array of fields
        } elseif (is_array($fieldlist)) {
            $myfieldlist = $fieldlist;
        }
    } else {
        $myfieldlist = null;
    }

    // join a module table to a dynamic object
    if (empty($join)) {
        $join = '';
    }
    // make some database table available via DD
    if (empty($table)) {
        $table = '';
    }

    $object =  Dynamic_Object_Master::getObject(array('moduleid'  => $modid,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'join'      => $join,
                                       'table'     => $table,
                                       'fieldlist' => $myfieldlist));
    // we're dealing with a real item, so retrieve the property values
    if (!empty($itemid)) {
        $object->getItem();
    }

    // if we are in preview mode, we need to check for any preview values
    //if (!xarVarFetch('preview', 'isset', $preview,  NULL, XARVAR_DONT_SET)) {return;}
    if (!empty($preview)) {
        $object->checkInput();
    }

    return $object->showDisplay(array('layout'   => $layout,
                                      'tplmodule'=> $tplmodule,
                                      'template' => $template));
}

?>
