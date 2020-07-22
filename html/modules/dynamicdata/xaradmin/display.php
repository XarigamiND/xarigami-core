<?php
/**
 * Display an item for admin
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
 * display an item in admin - allows different urls and treatment from user display
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param $args an array of arguments (if called by other modules)
 */
function dynamicdata_admin_display($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' ,$layout,    'default', XARVAR_NOT_REQUIRED)) {return;}
/*  // we could also pass along the parameters to the template, and let it retrieve the object
    // but in this case, we'd need to retrieve the object label anyway
    return array('objectid' => $objectid,
                 'modid' => $modid,
                 'itemtype' => $itemtype,
                 'itemid' => $itemid);
*/

    if (!empty($table)) {
        if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();
    }
    $myobjectargs = array('objectid' => $objectid,
                          'name' => $name,
                         'moduleid' => $modid,
                         'itemtype' => $itemtype,
                         'join'     => $join,
                         'table'    => $table,
                         'itemid'   => $itemid,
                         'tplmodule'=> $tplmodule,
                         'layout'   => $layout);

    $myobjectMaster = new Dynamic_Object_Master($myobjectargs);
    $myobject =  $myobjectMaster->getObject($myobjectargs);
    if (!isset($myobject)) return;

    $args = $myobject->toArray();
    $itemid = $myobject->getItem();

    $data = array();
    $data = $args;
    $modinfo = xarMod::getInfo($myobject->moduleid);
    $item = array();
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $itemtype;
    $item['returnurl'] = xarModURL($args['tplmodule'],'user','display',
                                   array('objectid' => $args['objectid'],
                                         'moduleid' => $args['moduleid'],
                                         'itemtype' => $args['itemtype'],
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $args['itemid'],
                                         'layout'   => $args['layout'],
                                         'tplmodule' => $args['tplmodule']));

    //jojo - CHECK - don't we already do this in the object class?
    $totransform = array();
    $totransform['transform'] = array(); // we must do this, otherwise we lose track of what got transformed
    foreach($myobject->properties as $pname => $pobj) {
        // *never* transform an ID
        // TODO: there is probably lots more to skip here.
        if($pobj->type == '21') continue;
        $totransform['transform'][] = $pname;
        $totransform[$pname] = $pobj->value;
    }

    $transformed = xarMod::callHooks('item','transform',$args['itemid'], $totransform, $modinfo['name'],$args['itemtype']);
   // Ok, we got the transformed values, now what?
    foreach($transformed as $pname => $tvalue) {
        if($pname == 'transform') continue;
        $myobject->properties[$pname]->value = $tvalue;
    }
    // *Now* we can set the data object
    $data['object'] = $myobject;

    $hooks = array();
    $hooks = xarMod::callHooks('item', 'display', $myobject->itemid, $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    if(!isset($template)) {
        $template = $myobject->name;
    }
    $data['module'] = $modinfo['name'];
    $data['referer'] = xarServer::getVar('HTTP_REFERER'); //where are we coming from to display
    $data['itemtype'] = $itemtype;
    $data['referertype'] = strpos($data['referer'],'type=admin') ? 'admin':'user'; //are we coming from admin functions?
    $data['layout'] = $layout;
    // Add the user menu to the data array
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');

    // Return the template variables defined in this function
    if (file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-display.xd') ||
        file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-display-' . $args['template'] . '.xd')) {
        return xarTplModule($args['tplmodule'],'admin','display',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','admin','display',$data,$args['template']);
    }

}
?>