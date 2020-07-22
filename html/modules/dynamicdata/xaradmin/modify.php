<?php
/**
 * Modify an item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Modify an item
 *
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 *
 * @param int objectid the id of the item to be modified
 * @param int modid the id of the module where the item comes from
 * @param int itemtype the id of the itemtype of the item
 * @param join
 * @param table
 * @return
 */
function dynamicdata_admin_modify($args)
{
    extract($args);

    $ddmodid = xarMod::getId('dynamicdata');
    $referer = xarServerGetVar('HTTP_REFERER');
    if(!xarVarFetch('objectid', 'id',    $objectid,  NULL,     XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'id',    $modid,     $ddmodid, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'str:1', $itemtype,  0,        XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL,     XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL,     XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('itemid',   'isset', $itemid))             {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL,     XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('return_url',  'isset', $return_url, $referer, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,     NULL, XARVAR_DONT_SET)) {return;}
    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing

    $objectargs =  array('objectid' => $objectid,
                         'moduleid' => $modid,
                         'itemtype' => $itemtype,
                         'join'     => $join,
                         'table'    => $table,
                         'itemid'   => $itemid,
                         'tplmodule' => $tplmodule);
    $objectmaster = new Dynamic_Object_Master($objectargs);
    $myobject = $objectmaster->getObject($objectargs);

    $args = $myobject->toArray();
    // create the data array and assign to args
    $data = $args;
   // Security check
    if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',$args['moduleid'].":".$args['itemtype'].":".$args['itemid'])) return;

    if ($preview) {
        $isvalid = $myobject->checkInput();
    } else {
        $myobject->getItem();
    }

    //now set up other data indexes
    $data['object'] = $myobject;

    // if we're editing a dynamic property, save its property type to cache
    // for correct processing of the validation rule (Dynamic_Validation_Property)
    if ($myobject->objectid == 2) {
        xarCoreCache::setCached('dynamicdata','currentproptype', $myobject->properties['type']);
    }
    $data['objectid'] = $myobject->objectid;
    $data['itemid'] = $itemid;
    $data['authid'] = xarSecGenAuthKey();
    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    $modinfo = xarMod::getInfo($myobject->moduleid);
    $item = array();
    foreach (array_keys($myobject->properties) as $name) {
        $item[$name] = $myobject->properties[$name]->value;
    }

    if (!isset($myobject)) {
        $data['objectid'] = 0;
        $data['urlform'] = '';
    } else {
        //for form preview
        $data['urlform'] = xarModURL('dynamicdata','admin','form',array('objectid' => $data['objectid'], 'theme' => 'print'),false);
    }

    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $myobject->itemtype;
    $item['itemid'] = $myobject->itemid;
    $hooks = array();
    $hooks = xarMod::callHooks('item', 'modify', $myobject->itemid, $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    if(!isset($template)) {
        $template = $myobject->name;
    }
    $data['template'] = $template;
    $data['tplmodule'] = $tplmodule;
    $data['preview'] = $preview;
    $data['table'] = $table;
    $data['referer'] = xarServer::getVar('HTTP_REFERER'); //where are we coming from to display
    $data['referertype'] = strpos($data['referer'],'type=admin') ? 'admin':'user'; //are we coming from admin functions?

    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    if (file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-modify.xd') ||
        file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-modify-' . $args['template'] . '.xd')) {
        return xarTplModule($args['tplmodule'],'admin','modify',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','admin','modify',$data,$args['template']);
    }
}

?>
