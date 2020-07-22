<?php
/**
 * List some items in a template
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
 * list some items in a template
 *
 * @param $args array containing the items or fields to show
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showview($args)
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

    // we got everything via template parameters
    if (isset($items) && is_array($items)) {

        return xarTplModule('dynamicdata','user','showview',
                            array($args),
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
                    'module name', 'user', 'showview', 'dynamicdata');
        throw new BadParameterException(null,$msg);
    }

    if (empty($itemtype) || !is_numeric($itemtype)) {
        $itemtype = null;
    }
    if (!isset($sortorder))
         $sortorder = null;

// TODO: what kind of security checks do we want/need here ?
    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$modid:$itemtype:All")) return;

    // try getting the item id list via input variables if necessary
    if (!isset($itemids)) {
        if (!xarVarFetch('itemids', 'isset', $itemids,  NULL, XARVAR_DONT_SET)) {return;}
    }

    // try getting the sort via input variables if necessary
    if (!isset($sort)) {
        if (!xarVarFetch('sort', 'isset', $sort,  NULL, XARVAR_DONT_SET)) {return;}
    }
    // try getting the sortordr via input variables if necessary
    if (!isset($sortorder)) {
        if (!xarVarFetch('sortorder', 'isset', $sortorder,  NULL, XARVAR_DONT_SET)) {return;}
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
    //$numitems = !isset($numitems)?xarModGetVar('dynamicdata','useritemsperpage'):$numitems;
    $objectargs = array('moduleid'  => $modid,
                       'itemtype'  => $itemtype,
                       'itemids' => $itemids,
                       'sort' => $sort,
                       'sortorder' => $sortorder,
                       'numitems' => $numitems,
                       'startnum' => $startnum,
                       'where' => $where,
                       'fieldlist' => $myfieldlist,
                       'join' => $join,
                       'table' => $table,
                       'catid' => $catid,
                       'groupby' => $groupby,
                       'status' => $status,
                      'label'  =>$label
                       );


    $objectMaster = new Dynamic_Object_Master($objectargs);
    $object = $objectMaster ->getObjectList($objectargs);
    if (!isset($object)) return;
    // Count before numitems!
    $itemcount = 0;
    $itemcount = $object->countItems();
    $object->getItems($objectargs);
    if (!isset($numitems) || empty($numitems)) $numitems = $itemcount;
    // label to use for the display link (if you don't use linkfield)
    if (empty($linklabel)) {
        $linklabel = '';
    }
    // function to use in the display link
    if (empty($linkfunc)) {
        $linkfunc = '';
    }
    // URL parameter for the item id in the display link (e.g. exid, aid, uid, ...)
    if (empty($param)) {
        $param = '';
    }
    // field to add the display link to (otherwise it'll be in a separate column)
    if (empty($linkfield)) {
        $linkfield = '';
    }
    // current URL for the pager (defaults to current URL)
    if (empty($pagerurl)) $pagerurl = xarServer::getCurrentURL(array('startnum'=>'%%'));

    $pager= xarTplGetPager($startnum,$itemcount,$pagerurl,$numitems);

    $data = $object->showView(array('layout'    => $layout,
                                   'tplmodule' => $tplmodule,
                                   'template'  => $template,
                                   'linklabel' => $linklabel,
                                   'linkfunc'  => $linkfunc,
                                   'param'     => $param,
                                   'sort'       => $sort,
                                   'sortorder' => $sortorder,
                                   'pagerurl'  => $pagerurl,
                                   'itemcount' => $itemcount,
                                   'linkfield' => $linkfield,
                                   'pager'      => $pager));

    return $data;
}

?>
