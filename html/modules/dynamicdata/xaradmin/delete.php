<?php
/**
 * Delete an item
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
 * delete item
 * @param 'itemid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete($args)
{
   extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid, NULL,                               XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'id',    $modid,    xarMod::getId('dynamicdata'), XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype, 0,                                  XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemid',   'id',    $itemid                                                           )) {return;}
    if(!xarVarFetch('confirm',  'isset', $confirm,  NULL,                               XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('noconfirm','isset', $noconfirm, NULL,                              XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}

    $myobject = Dynamic_Object_Master::getObject(array('moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    if (empty($myobject)) return;
    if (!empty($noconfirm)) {
        if (!empty($table)) {
            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array('table' => $table)));
        } else {
            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array('itemid' => $objectid)));
        }
        return true;
    }

    $myobject->getItem();
    $args = $myobject->toArray();
    $data = $args;
    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if(!xarSecurityCheck('DeleteDynamicDataItem',0,'Item',"$modid:$itemtype:$itemid")) return xarResponseForbidden();
    $modinfo = xarMod::getInfo($myobject->moduleid);
    if (empty($confirm)) {

        //common adminmenu
        $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
        $data['object'] = $myobject;
        if ($myobject->objectid == 1) {
            $mylist = Dynamic_Object_Master::getObjectList(array('objectid' => $itemid));

            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSecGenAuthKey();
         $data['objectid'] = $myobject->objectid;

        //for form preview
        $data['urlform'] = xarModURL('dynamicdata','admin','form',array('objectid' => $data['objectid'], 'theme' => 'print'),false);

        if (!empty($return_url)) {
           $data['return_url'] = $return_url;
        } else {
           $data['return_url'] = '';
        }

        if(!isset($template)) {
            $template = $myobject->name;
        }
        $data['table'] = $table;
        $data['template'] = $template;
        $data['referer'] = xarServer::getVar('HTTP_REFERER'); //where are we coming from to display
        $data['referertype'] = strpos($data['referer'],'type=admin') ? 'admin':'user'; //are we coming from admin functions?
        return xarTplModule('dynamicdata','admin','delete',$data,$template);

    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) return;

    // special case for a dynamic object : delete its properties too // TODO: and items
    if ($myobject->objectid == 1) {
        //they may or may not be items associated with this
        $mylist = Dynamic_Object_Master::getObjectList(array('objectid' => $itemid));
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = Dynamic_Property_Master::deleteProperty(array('itemid' => $propid));
        }
        //delete the object
        $itemid = xarMod::apiFunc('dynamicdata','admin','deleteobject',array('objectid' => $itemid));
    } else {
       //just delete the item
       $itemid = $myobject->deleteItem();
    }

    $msg = xarML('The item has been deleted.');
    xarTplSetMessage($msg,'status');
    $data['table'] = $table;
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $itemtype;
    $item['itemid'] = $itemid;
   $data['referer'] = xarServer::getVar('HTTP_REFERER'); //where are we coming from to display
   $data['referertype'] = strpos($data['referer'],'type=admin') ? 'admin':'user'; //are we coming from admin functions?
    xarMod::callHooks('item', 'delete', $itemid, $item, $modinfo['name']);


    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } elseif (!empty($table)) {
        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array('table' => $table)));
    } else {
            xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array('itemid' => $objectid)));
    }

    // Return
    return true;

}

?>