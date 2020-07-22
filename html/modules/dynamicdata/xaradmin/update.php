<?php
/**
 * Update current item
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
 * Update current item
 * This is a standard function that is called with the results of the
 * form supplied by xarMod::guiFunc('dynamicdata','admin','modify') to update a current item
 * @param 'exid' the id of the item to be updated
 * @param 'name' the name of the item to be updated
 * @param 'number' the number of the item to be updated
 */
function dynamicdata_admin_update($args)
{
    extract($args);

    if(!xarVarFetch('objectid',   'isset', $objectid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',      'isset', $modid,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',   'isset', $itemtype,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'isset', $itemid,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',    'isset', $preview,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',       'isset', $join,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',      'isset', $table,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule',  'isset', $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

    if (!xarSecConfirmAuthKey()) return;

    if (empty($modid)) {
        $modid = xarMod::getId('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (empty($preview)) {
        $preview = 0;
    }

    $myobject = Dynamic_Object_Master::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));

    $itemid = $myobject->getItem();

    // if we're editing a dynamic property, save its property type to cache
    // for correct processing of the validation rule (Dynamic_Validation_Property)
    if ($myobject->objectid == 2) {
        xarCoreCache::setCached('dynamicdata','currentproptype', $myobject->properties['type']);
    }
    $isvalid = $myobject->checkInput();


    $modinfo = xarMod::getInfo($myobject->moduleid);

    if (!empty($preview) || !$isvalid) {
        $args = $myobject->toArray(); //this should cover it but leave the rest for now
        $data = $args;
        $data['object'] =  $myobject;
        $data['objectid'] = $myobject->objectid;
        $data['itemid'] = $itemid;
        $data['authid'] = xarSecGenAuthKey();
        $data['preview'] = $preview;
        if (!empty($return_url)) {
            $data['return_url'] = $return_url;
        }
        if ($myobject->objectid == 1) {
                    $data['labelinfo'] = !empty($myobject->properties['label']->value) ?
                          $myobject->properties['label']->value : $myobject->properties['name']->value;
        } else {
             $data['labelinfo']  =  !empty($myobject->label) ? $myobject->label:  $myobject->name;
        }

        //for form preview
        $data['urlform'] = xarModURL('dynamicdata','admin','form',array('objectid' => $data['objectid'], 'theme' => 'print'),false);
        $item = array();
        foreach (array_keys($myobject->properties) as $name) {
            $item[$name] = $myobject->properties[$name]->value;
        }
        $item['module'] = $modinfo['name'];
        $item['itemtype'] = $myobject->itemtype;
        $item['itemid'] = $myobject->itemid;
        $hooks = array();
        $hooks = xarMod::callHooks('item', 'modify', $myobject->itemid, $item, $modinfo['name']);
        $data['hooks'] = $hooks;
        $displayhooks = array();
        $displayhooks = xarMod::callHooks('item', 'display', $myobject->itemid, $item, $modinfo['name']);
        $data['displayhooks'] = $displayhooks;
        if(!isset($template)) {
            $template = $myobject->name;
        }
        $data['template'] = $template;
        $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
        return xarTplModule('dynamicdata','admin','modify', $data,$template);

    }
    $itemid = $myobject->updateItem();
    if (!isset($itemid)) return; // throw back

    //call hooks

    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $myobject->itemtype;
    $item['itemid'] = $itemid;
    xarMod::callHooks('item', 'update', $itemid, $item, $modinfo['name']);


    // special case for dynamic objects themselves
    if ($myobject->objectid == 1) {
        // check if we need to set a module alias (or remove it) for short URLs
        $name = $myobject->properties['name']->value;
        $alias = xarModGetAlias($name);
        $isalias = $myobject->properties['isalias']->value;
        if (!empty($isalias)) {
            // no alias defined yet, so we create one
            if ($alias == $name) {
                $args = array('modName'=>'dynamicdata', 'aliasModName'=> $name);
                xarMod::apiFunc('modules', 'admin', 'add_module_alias', $args);
            }
        } else {
            // this was a defined alias, so we remove it
            if ($alias == 'dynamicdata') {
                $args = array('modName'=>'dynamicdata', 'aliasModName'=> $name);
                xarMod::apiFunc('modules', 'admin', 'delete_module_alias', $args);
            }
        }

        // resynchronise properties with object in terms of module id and itemtype (for now)
        $objectid = $myobject->properties['objectid']->value;
        $moduleid = $myobject->properties['moduleid']->value;
        $itemtype = $myobject->properties['itemtype']->value;
        if (!xarMod::apiFunc('dynamicdata','admin','syncprops',
                           array('objectid' => $objectid,
                                 'moduleid' => $moduleid,
                                 'itemtype' => $itemtype))) {
            return;
        }
    }
        $msg = xarML('The item has been successfully updated.');
    xarTplSetMessage($msg,'status');
    $confirm = array('short' => xarML('The update was successful.'));

    if (!empty($return_url)) {
        $returnurl = $return_url;

    } elseif ($myobject->objectid == 2) { // for dynamic properties, return to modifyprop
        if(xarRequestIsAjAX()) {
            $confirm['title'] = xarML('Property "#(1)" Updated', $myobject->properties['name']->value);
            return xarTplModule('base','message','confirm', $confirm);
        } else {
             $returnurl = xarModURL('dynamicdata', 'admin', 'modifyprop',
                                      array('itemid' => $myobject->properties['objectid']->value));
        }

    } elseif (!xarSecurityCheck('AddDynamicDataItem',0,'Item', $modid.':'.$itemtype.':All')) {
        $returnurl = xarModURL('dynamicdata', 'user', 'view',array('itemtype' => $itemtype));

    } elseif (!empty($table)) {
        if(xarRequestIsAJAX()) {
            $confirm['title'] = xarML('Object "#(1)" Updated', $myobject->properties['name']->value);
            return xarTplModule('base','message','confirm', $confirm);
        } else {
            $returnurl  =  xarModURL('dynamicdata', 'admin', 'view', array('table' => $table));
        }
    } else {
        if(xarRequestIsAJAX()) {
            $confirm['title'] = xarML('Object "#(1)" Updated', $myobject->properties['name']->value);
            return xarTplModule('base','message','confirm', $confirm);
        } else {
            $returnurl  =  xarModURL('dynamicdata', 'admin', 'view', array('itemid' => $myobject->objectid));
        }
    }

    xarResponseRedirect($returnurl);
    // Return
    return true;
}

?>