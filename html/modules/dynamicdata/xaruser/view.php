<?php
/**
 * View a list of items
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2013 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * view a list of items
 * This is a standard function to provide an overview of all of the items
 * available from the module.
 *
 * @return array
 */
function dynamicdata_user_view($args)
{

    if(!xarVarFetch('objectid', 'int',   $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'int',   $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'int',   $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'int',   $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort',     'isset',  $sort,     '', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('count',     'isset',  $count,   0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sortorder', 'pre:trim:alpha:lower:enum:asc:desc',   $sortorder, '', XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' , $layout,   'default', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, 'dynamicdata', XARVAR_NOT_REQUIRED)) {return;}
    // Override if needed from argument array
    extract($args);

    // Security measure for table browsing
    if (!empty($table)) {
        if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();
    }

    if (empty($modid)) {
        $modid = xarMod::getId('dynamicdata');
    }
    if (empty($itemtype)) {
        $itemtype = 0;
    }
    if (!xarMod::apiLoad('dynamicdata','user')) return;
    // if itemsperpage passed in from tag then use it otherwise use the modvar setting
    $usernumperpage = xarModGetVar('dynamicdata','useritemsperpage')?xarModGetVar('dynamicdata','useritemsperpage'): 0;
    $numitems= isset($numitems) ?$numitems: $usernumperpage;

    $sysobs= xarModGetVar('dynamicdata', 'systemobjects');
    try {
            $sysobs = @unserialize($sysobs);
        } catch (Exception $e) {
            $sysobs = array();
        }


    $object = Dynamic_Object_Master::getObjectList(
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype,
                                  'join'     => $join,
                                  'table'    => $table,
                                  'tplmodule'=> $tplmodule,
                                  'numitems' => $numitems,
                                  'sort'     => $sort,
                                  'table'   => $table,
                                  'catid'   => $catid,
                                  'layout'   => $layout,
                                  'template' => $template)
    );

    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;
    if ($modid == 182) {
        $checkid = isset($objectid) ?$objectid:'';
    } else {
        $checkid = $itemtype;
    }

    if (in_array($checkid,$sysobs) && !xarSecurityCheck('AdminDynamicDataItem',0,'Item',"$modid:$checkid:All"))  return;
        $data['objectid'] = 0;
        $data['label'] = xarML('Dynamic Data Objects');
        $param = isset($param)?$param:'';
        $data['param'] = $param;
    if (isset($object)) {
        $data = $object->toArray();

    }
    $lastquery = xarSession::getVar('view.ddquery');

    $sort = !empty($sort) ?$sort : (isset($lastquery['sort']) ?$lastquery['sort']:'id') ;
    $sortorder = !empty($sortorder) ? $sortorder: (isset($lastquery['sortorder']) ? $lastquery['sortorder']:'asc') ;
    //jojo - if we use the tag in the template we really don't need to bother with these values here
    $args = array('objectid' => $objectid,
                'moduleid' => $modid,
                'itemtype' => $itemtype,
                'join'      => $join,
                'table'     => $table,
                'tplmodule' => $tplmodule,
                'template'  => $template,
                'sort'      => $sort,
                'startnum'  => $startnum,
                'numitems'  => $numitems,
                'layout'    => $layout,
                'count'     => $count,
                'sortorder' => $sortorder,
                'param'     => $param,
                'status'   => 1);

    // Count before numitems!
    $itemcount = 0;
    $itemcount = $object->countItems();
    $args['itemcount'] = $itemcount;

    $startnum = isset($startnum)?$startnum:1;
    $args['startnum'] = $startnum;
    $object->getItems($args);

    $data['object'] = $object;
    xarSession::getVar('view.ddquery',$args);

    // Add the user menu to the data array
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','user','getmenulinks');
    //code to add an 'add' link
    $data['newlink'] = '';
    xarTplSetPageTitle(xarML('View #(1)', $object->label));
    if (xarSecurityCheck('SubmitDynamicDataItem',0,'Item',$modid.':'.$itemtype.':All')) {
            $data['newlink'] = xarModURL($data['tplmodule'],'admin','new',
                                         array('itemtype' => $itemtype,
                                               'table'    => $table));
    }
    $data['modid'] = $modid;//do we need this?
    $data['catid'] = $catid;//do we need this ....might be passed in
    if (empty($pagerurl)) $pagerurl = xarServer::getCurrentURL(array('startnum'=>'%%'));

    $data['pager'] = xarTplGetPager($startnum,$itemcount,$pagerurl,$numitems);

    return xarTplModule($data['tplmodule'],'user','view',$data, $data['template']);
}

?>
