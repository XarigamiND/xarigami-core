<?php
/**
 * Dynamic data view items
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * view items
 */
function dynamicdata_admin_view($args)
{
    extract($args);

    $defaultitems = xarModGetVar('dynamicdata','itemsperpage');
    if(!xarVarFetch('itemid',   'int',   $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'int',   $modid,     xarMod::getId('dynamicdata'), XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype,  0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'int',   $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'int',   $numitems,  $defaultitems, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      '', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sortorder', 'pre:trim:alpha:lower:enum:asc:desc',   $sortorder, '', XARVAR_DONT_SET)) {return;} //usually 'sort' but we'll use direction
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' ,$layout,    'default', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}

    if (empty($modid)) {
        $modid = xarMod::getId('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }

    $object = Dynamic_Object_Master::getObject(
            array('objectid' => $itemid,
                  'moduleid' => $modid,
                  'itemtype' => $itemtype,
                  'join'     => $join,
                  'table'    => $table,
                  'tplmodule'=> $tplmodule,
                  'template'  => $template)
        );
    if (isset($object)) {
        $data = $object->toArray();

    } else {
        return;
    }

    $lastquery = xarSession::getVar('adminview.ddquery');
    $sort = !empty($sort) ?$sort : (isset($lastquery['sort']) ?$lastquery['sort']:'id') ;
    $sortorder = !empty($sortorder) ? $sortorder: (isset($lastquery['sortorder']) ? $lastquery['sortorder']:'asc') ;
    $orderclause = $sort.' '.strtoupper($sortorder);
    if (!isset($numitems) || empty($numitems)) $numitems = 30; //admin numitems is never 0 or unlimited
    $args = array('objectid' => $itemid,
                'moduleid' => $modid,
                'itemtype' => $itemtype,
                'join'      => $join,
                'table'     => $table,
                'tplmodule' => $tplmodule,
                'template'  => $template,
                'sort'      => $sort,
                'numitems'  => $numitems,
                'count'     => 1,
                'sortorder' => $sortorder,
                'status'   => 1);


    $data['numitems'] = $numitems;
    $startnum = isset($startnum) ? $startnum:1;
    $data['startnum'] = $startnum;
    $data['sort'] = $sort;
    $data['count'] = 1;

    //we don't really need this if we are using the list tag in the template
   // $mylist =  Dynamic_Object_Master::getObjectList($args);
   // $mylist->getItems(array('numitems' => $numitems,
    //                        'startnum' => $startnum));
   // $data['object']  = & $mylist;

    xarSession::setVar('adminview.ddquery',$args);

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
// Security Check
    if(!xarSecurityCheck('EditDynamicData',0,'Item',"$modid:$itemtype:All")) return xarResponseForbidden();
    // show other modules
    $data['modlist'] = array();
    if ($data['objectid'] == 1 && empty($table)) {
        $objects = xarMod::apiFunc('dynamicdata','user','getobjects');
        $seenmod = array();
        foreach ($objects as $object) {
            $seenmod[$object['moduleid']] = 1;
        }

        $modList = xarMod::apiFunc('modules', 'admin', 'getlist',
                          array('orderBy'     => 'category/name'));
        $oldcat = '';
        for ($i = 0, $max = count($modList); $i < $max; $i++) {
            if (!empty($seenmod[$modList[$i]['regid']])) {
                continue;
            }
            if (isset($modList[$i]['category']) && $oldcat != $modList[$i]['category']) {
                $modList[$i]['header'] = xarVarPrepForDisplay($modList[$i]['category']);
                $oldcat = $modList[$i]['category'];
            } else {
                $modList[$i]['header'] = '';
            }
            if(xarSecurityCheck('AdminDynamicDataItem',0,'Item',$modList[$i]['regid'].':All:All')) {
                $modList[$i]['link'] = xarModURL('dynamicdata','admin','modifyprop',
                                                  array('modid' => $modList[$i]['regid']));
            } else {
                $modList[$i]['link'] = '';
            }
            $data['modlist'][] = $modList[$i];
        }
    }

    if (xarSecurityCheck('AdminDynamicData',0)) {
        if (!empty($table)) {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('table' => $table));
        } elseif (!empty($join)) {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('itemid' => $data['objectid'],
                                                 'join' => $join));
        } else {
            $data['querylink'] = xarModURL('dynamicdata','admin','query',
                                           array('itemid' => $data['objectid']));
        }
    }
    $data['modid'] = $modid;
    $data['tplmodule'] = $tplmodule;
    $data['sortorder'] = $sortorder;
    $data['catid'] = $catid;

    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    // Return the template variables defined in this function
    return xarTplModule($data['tplmodule'],'admin','view',$data, $data['template']);
}

?>