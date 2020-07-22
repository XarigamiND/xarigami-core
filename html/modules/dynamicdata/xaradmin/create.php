<?php
/**
 * Standard function to create a new item
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * This is a standard function that is called with the results of the
 * form supplied by xarMod::guiFunc('dynamicdata','admin','new') to create a new item
 * @param int objectid
 * @param int modid
 * @param int itemtype
 * @param int itemid
 * @param preview
 * @param string return_url
 * @param join
 * @param table
 * @param template
 * @return bool
 */
function dynamicdata_admin_create($args)
{

    extract($args);
// FIXME: whatever, as long as it doesn't generate Variable "0" should not be empty exceptions
//        or relies on $myobject or other stuff like that...

    if (!xarVarFetch('objectid',    'id',    $objectid,   NULL,                               XARVAR_DONT_SET)) return;
    if (!xarVarFetch('modid',       'isset', $modid,      xarMod::getId('dynamicdata'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'isset', $itemtype,   0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemid',      'isset', $itemid,     0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('preview',     'isset', $preview,    0,                                  XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('join',        'isset', $join,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('table',       'isset', $table,      NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('template',    'isset', $template,   NULL, XARVAR_DONT_SET)) {return;}

    if (!xarSecConfirmAuthKey()) return;

    $myobject = Dynamic_Object_Master::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    $isvalid = $myobject->checkInput();

    $modinfo = xarMod::getInfo($myobject->moduleid);
    //check the name doesn't have spaces
    //xgami-000820 space in DD object names
    if ($modid == 182 &&  ($objectid ==1) && $itemtype == 0) {
        $myproperties = $myobject->getProperties();
        $propname = $myproperties['name']->getValue();
        $propname  = strtolower($propname );
        $propname  = preg_replace('/[^a-z0-9_]+/','_',$propname);
        $propname = preg_replace('/_$/','',$propname);
        $myproperties['name']->setValue($propname);
    }

    $antibotinvalid =0;//initialize
    $hookinfo = xarModCallHooks('item', 'submit',  $itemid, array('itemtype'=>$itemtype,'itemid'=>$itemid,'module'=>'dynamicdata'));

    $antibotinvalid = isset($hookinfo['antibotinvalid']) ? $hookinfo['antibotinvalid'] : 0;

    if (!empty($preview) || !$isvalid || $antibotinvalid == TRUE) {
        $data = xarMod::apiFunc('dynamicdata','admin','menu');
        $data['object'] = $myobject;
        $data['antibotinvalid'] = $antibotinvalid;
        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;
        if (!empty($return_url)) {
            $data['return_url'] = $return_url;
        }
        $item = array();
        foreach (array_keys($myobject->properties) as $name) {
            $item[$name] = $myobject->properties[$name]->value;
        }
        $item['antibotinvalid'] = $data['antibotinvalid'];
        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $myobject->itemtype;
        $item['itemid'] = $myobject->itemid;
        $hooks = array();
        $hooks = xarMod::callHooks('item', 'new', $myobject->itemid, $item, $modinfo['name']);
        $data['hooks'] = $hooks;
        $displayhooks = array();
        $displayhooks = xarMod::callHooks('item', 'display', $myobject->itemid, $item, $modinfo['name']);


        if(!isset($template)) {
            $template = $myobject->name;
        }
        $data['template'] = $template;

        return xarTplModule('dynamicdata','admin','new',$data,$template);
    }

    $itemid = $myobject->createItem();
    if (empty($itemid)) return; // throw back

    $msg = xarML('The new item has been successfully created.');
    xarTplSetMessage($msg,'status');

    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $myobject->itemtype;
    $item['itemid'] = $itemid;
    xarMod::callHooks('item', 'create', $itemid, $item, $modinfo['name']);

    if (empty($return_url)) {
        if (!xarSecurityCheck('AddDynamicDataItem',0,'Item',$modid.':'.$itemtype.':All') && ($myobject->objectid <= 2)) {
            $return_url = xarModURL('dynamicdata', 'user', 'view',
                                          array('itemtype' => $itemtype));
        } elseif (!empty($table)) {
           $return_url = xarModURL('dynamicdata', 'admin', 'view',
                                          array('table' => $table));
        } else {
            $return_url = xarModURL('dynamicdata', 'admin', 'view',
                                          array('itemid' => $myobject->objectid));
        }
    }

    //one exit point
    xarResponseRedirect($return_url);
    // Return
    return true;
}
?>