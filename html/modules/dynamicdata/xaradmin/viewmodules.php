<?php
/**
 * Dynamic data view extended modules and their properties
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 */
/**
 * view extended modules
 */
function dynamicdata_admin_viewmodules($args)
{
    extract($args);

    if(!xarVarFetch('itemid',   'int',   $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'int',   $modid,     xarMod::getId('dynamicdata'), XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'int',   $itemtype,  0, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('startnum', 'int',   $startnum,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('numitems', 'int',   $numitems,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('sort',     'isset', $sort,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1' , $layout,   'default', XARVAR_NOT_REQUIRED)) {return;}

    if (empty($modid)) {
        $modid = xarMod::getId('dynamicdata');
    }
    if (!isset($itemtype)) {
        $itemtype = 0;
    }

    $object = xarMod::apiFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $itemid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype,
                                  'join'     => $join,
                                  'table'    => $table));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label = $object['label'];
        $param = $object['urlparam'];
    } else {
        return;
    }

    $data = xarMod::apiFunc('dynamicdata','admin','menu');

/*
    $mylist = Dynamic_Object_Master::getObjectList(array('objectid' => $itemid,
                                            'moduleid' => $modid,
                                            'itemtype' => $itemtype));
    $data['mylist'] = $mylist;
*/

    $data['objectid'] = $objectid;
    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;
    $data['param'] = $param;
    $data['startnum'] = $startnum;
    $data['numitems'] = $numitems;
    $data['label'] = $label;
    $data['sort'] = $sort;
    $data['join'] = $join;
    $data['table'] = $table;
    $data['catid'] = $catid;
    $data['layout'] = $layout;

    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
// Security Check
    if(!xarSecurityCheck('EditDynamicData')) return;

    // show other modules
    $data['modlist'] = array();
    if ($objectid == 1 && empty($table)) {
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

   
    // Return the template variables defined in this function
    return $data;
}

?>