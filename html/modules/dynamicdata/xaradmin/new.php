<?php
/**
 * Add a new item
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
 * add new item
 * This is a standard function that is called whenever an administrator
 * wishes to create a new module item
 * @return
 */
function dynamicdata_admin_new($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('antibotinvalid','int:0:1', $antibotinvalid, NULL,    XARVAR_NOT_REQUIRED)) {return;}

    if (empty($modid)) {
        $modid = xarMod::getId('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }
    if (!isset($itemid)) {
        $itemid = 0;
    }

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if(!xarSecurityCheck('SubmitDynamicDataItem',0,'Item',"$modid:$itemtype:All")) return xarResponseForbidden();


    $myobjectargs = array('objectid' => $objectid,
                         'moduleid' => $modid,
                         'itemtype' => $itemtype,
                         'join'     => $join,
                         'table'    => $table,
                         'itemid'   => $itemid);

    $myobjectMaster = new Dynamic_Object_Master($myobjectargs);
    $myobject =$myobjectMaster->getObject($myobjectargs);

    $data['object'] = $myobject;

    if(!isset($template)) {
        $template = $myobject->name;
    }
    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();
    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    $modinfo = xarMod::getInfo($myobject->moduleid);
    $item = array();
    foreach (array_keys($myobject->properties) as $name) {
        $item[$name] = $myobject->properties[$name]->value;
    }
    $data['antibotinvalid'] =isset($antibotinvalid)? $antibotinvalid : 0;
     $item['antibotinvalid'] = $data['antibotinvalid'];
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $myobject->itemtype;
    $item['itemid'] = $myobject->itemid;
    $hooks = array();
    $hooks = xarMod::callHooks('item', 'new', $myobject->itemid, $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    $data['template'] = $template;
    $data['table'] = $table;
    return xarTplModule('dynamicdata','admin','new',$data,$template);
}

?>