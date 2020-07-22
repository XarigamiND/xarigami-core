<?php
/**
 * List items in a template
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * list some items in a template
 *
 * @param $args array containing the items or fields to show
 * @todo move this to some common place in Xarigami (base module ?)
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_adminapi_showlist($args)
{
    extract($args);
    // optional layout for the template
    if (empty($layout)) {
        $layout = 'default';
        $args['layout'] = $layout;
    }

    // or optional template, if you want e.g. to handle individual fields
    // differently for a specific module / item type
    if (empty($template)) {
        $template = '';
    }
    if (empty($tplmodule)) {
        $tplmodule = 'dynamicdata';
        $args['tplmodule'] = $tplmodule;
    }
    if (empty($numitems)) {
        $numitems= xarModGetVar('dynamicdata','itemsperpage');
        $args['itemsperpage'] = $numitems;
    }

    // we got everything via template parameters
    if (isset($items) && is_array($items)) {
        return xarTplModule('dynamicdata','admin','showlist',
                            array($args
                                  ),
                            $template);
    }

    if (empty($modid)) {
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
    } else {
            $modinfo = xarMod::getInfo($modid);
            $modname = $modinfo['name'];
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module name', 'admin', 'showlist', 'dynamicdata');
        throw new BadParameterException(null,$msg);
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }

// TODO: what kind of security checks do we want/need here ?
    // don't bother if you can't edit anything anyway
    if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$modid:$itemtype:All")) return;

    // try getting the item id list via input variables if necessary
    if (!isset($itemids)) {
        if (!xarVarFetch('itemids', 'isset', $itemids,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // try getting the sort via input variables if necessary
    if (!isset($sort)) {
        if (!xarVarFetch('sort', 'isset', $sort,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // try getting the numitems via input variables if necessary
    if (!isset($numitems)) {
        if (!xarVarFetch('numitems', 'isset', $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // try getting the startnum via input variables if necessary
    if (!isset($startnum)) {
        if (!xarVarFetch('startnum', 'isset', $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // don't try getting the where clause via input variables, obviously !
    if (empty($where)) {
        $where = '';
    }
    if (empty($groupby)) {
        $groupby = '';
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
        $status = null;
    } else {
        $myfieldlist = null;
        // get active properties only (+ not the display only ones)
        $status = 1;
    }

    // join a module table to a dynamic object
    if (empty($join)) {
        $join = '';
    }
    // make some database table available via DD
    if (empty($table)) {
        $table = '';
    }
    // select in some category
    if (empty($catid)) {
        $catid = '';
    }
    $label = isset($label) ? $label : '';

    // check the URL parameter for the item id used by the module (e.g. exid, aid, ...)
    if (empty($param)) {
        $param = '';
    }
    //count flag
    $count = isset($count)?$count:0;

    $sortorder =isset($sortorder)?$sortorder:'ASC';
    $argslist =  array('moduleid'  => $modid,
                       'itemtype'  => $itemtype,
                       'itemids' => $itemids,
                       'sort' => $sort,
                       'sortorder'=>$sortorder,
                       'numitems' => $numitems,
                       'startnum' => $startnum,
                       'where' => $where,
                       'fieldlist' => $myfieldlist,
                       'tplmodule' => $tplmodule,
                       'join' => $join,
                       'table' => $table,
                       'catid' => $catid,
                       'groupby' => $groupby,
                       'status' => $status,
                       'count'  => $count,
                       'label'  =>$label
                       );

    $objectMaster = new Dynamic_Object_Master($argslist);
    $object = $objectMaster->getObjectList($argslist);

    // Count before numitems!
    $itemcount = 0;
    if(isset($args['count']) && $args['count']) {
        $itemcount = $object->countItems();
    }
    $object->getItems();
    if (empty($pagerurl)) $pagerurl = xarServer::getCurrentURL(array('startnum'=>'%%'));

    $pager = xarTplGetPager($startnum,$itemcount,$pagerurl,$numitems);

    return $object->showList(array('layout'   => $layout,
                                   'template' => $template,
                                   'tplmodule' => $tplmodule,
                                   'param'  => $param,
                                   'sort'   => $sort,
                                   'sortorder' => $sortorder,
                                   'pagerurl'   =>$pagerurl,
                                   'itemcount' => $itemcount,
                                   'pager'  =>$pager
                                   ));
}
?>