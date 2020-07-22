<?php
/**
 * Modify the dynamic properties for a module and itemtype
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Modify the dynamic properties for a module + itemtype
 * @param int itemid
 * @param int modid
 * @param int itemtype
 * @param table
 * @param details
 * @param string layout (optional)
 * @throws BAD_PARAM
 * @return array with $data
 */
function dynamicdata_admin_modifyprop()
{
    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing

    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if(!xarVarFetch('itemid',   'isset', $itemid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $modid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('details',  'isset', $details,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('layout',   'str:1', $layout,   'default', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('moveaction', 'str:1:', $moveaction, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pname', 'str:0:', $pname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('orderarray', 'isset', $orderarray, '', XARVAR_NOT_REQUIRED)) return;

    if (!xarMod::apiLoad('dynamicdata', 'admin')) return; // throw back

    $object = xarMod::apiFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $itemid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));

    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
        $label =  !empty($object['label']) ?$object['label'] :$object['name'];
        $data['label']= $label;
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'modifyprop', 'dynamicdata');
        throw new EmptyParameterException(null,$msg);
    }
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');

    $data['modid'] = $modid;
    $data['itemtype'] = $itemtype;

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    $modinfo = xarMod::getInfo($modid);

    if (!isset($object)) {
        $data['objectid'] = 0;
        if (!empty($itemtype)) {
            $data['labelinfo'] = xarML(' #(1) Itemtype #(2)', $modinfo['displayname'], $itemtype);
        } else {
            $data['labelinfo'] = xarML(' #(1)', $modinfo['displayname']);
        }
    } else {
        $data['objectid'] = $object['objectid'];
        if (!empty($itemtype)) {
            $data['labelinfo'] = xarML('#(1)', !empty($object['label'])?$object['label'] :$object['name']);
        } else {
            $data['labelinfo'] = xarML('#(1)', !empty($object['label'])?$object['label'] :$object['name']);
        }
    }
    if (!isset( $data['label'])) {
        $data['label']= $data['labelinfo'];
    }
    $data['fields'] = xarMod::apiFunc('dynamicdata','user','getprop',
                                   array('modid' => $modid,
                                         'itemtype' => $itemtype,
                                         'allprops' => true));
    if (!isset($data['fields']) || $data['fields'] == false) {
        $data['fields'] = array();
    }

    //get the order numbers - we don't assume the order numbers are contiguous
    $ordervalues = array();
    foreach($data['fields'] as $pname=>$pdata) {
        $ordervalues[] =$pdata['order'];
    }

    $totalprops=count($data['fields']);
    $i=0;//initialise counter

    //check tos we if we have some js reordering going on and deal with it first and get out
    if (!empty($orderarray)) {
        $dbconn =  xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $propertiestable = $xartable['dynamic_properties'];

        $orderlist = str_replace('&',',', $orderarray);
        $orderlist = explode(',',$orderlist);
        $ordercounter = 0;
        foreach ($orderlist as $relorder=>$propid) {
            // Update the current object property
            $neworder = $ordervalues[$ordercounter];
            $query = 'UPDATE ' . $propertiestable
                . ' SET xar_prop_order = ?'
                . ' WHERE xar_prop_id = ?';

            $result = $dbconn->execute($query, array($neworder, $propid));
            if (!$result) return;
            $ordercounter++;
        }
        $result->close();
        return;
    }


    foreach ($data['fields'] as $propname=>$prop) {
        //we need to assign a relative location, we cant' rely on prop order field
        //to reorder we need the relative location in the list, the prop name
        if ($i <>0) {
            $data['fields'][$propname]['upurl'] = xarModURL('dynamicdata', 'admin', 'modifyprop',
                array('pname'=>$prop['name'],'modid'=>$modid,'itemtype'=>$itemtype,'objectid'=>$itemid,'moveaction' => 'up')
            );
       }elseif ($i == 0) {
            $data['fields'][$propname]['upurl'] = '';
            $data['fields'][$propname]['uptitle'] = '';
        } else {
            $data['fields'][$propname]['upurl'] = '';
        }
        $props[$propname]['uptitle'] = xarML('Move Up');
        if ($i <>$totalprops-1) {
            $data['fields'][$propname]['downurl'] = xarModURL('dynamicdata', 'admin', 'modifyprop',
                array('pname'=>$prop['name'], 'modid'=>$modid,'itemtype'=>$itemtype, 'objectid'=>$itemid, 'moveaction' => 'down')
            );
            $data['fields'][$propname]['downtitle'] = xarML('Move Down');
        } elseif ($i == $totalprops-1) {
            $data['fields'][$propname]['downurl'] = '';
            $data['fields'][$propname]['downtitle'] = '';
        } else {
            $data['fields'][$propname]['downurl'] = '';
            $data['fields'][$propname]['downtitle'] = xarML('Move Down');
        }
        $data['fields'][$propname]['relorder'] = $i;
        $i++;
    }

    if (!empty($pname) && !empty($moveaction)) {
        $domove=xarMod::apiFunc('dynamicdata', 'admin', 'reorderprops', array('pname' => $pname, 'relorder'=>$data['fields'][$pname]['relorder'], 'moveaction' => $moveaction, 'modid'=>$modid,'itemtype'=>$itemtype,'objectid'=>$itemid));
        reset($data['fields']);
        if (!$domove) {
            return;
        } else{
            $redirecturl = xarModURL('dynamicdata','admin','modifyprop',array('itemid'=>$objectid));
            xarResponseRedirect($redirecturl);
        }
    }


    // get possible data sources (with optional extra table)
// TODO: combine with static tables list below someday ?
    $params = array();
    if (!empty($table)) {
        $params['table'] = $table;
        $data['table'] = $table;
    } else {
        $data['table'] = null;
    }
    $data['sources'] = Dynamic_DataStore_Master::getDataSources($params);
    if (empty($data['sources'])) {
        $data['sources'] = array();
    }

    $isprimary = 0;
    foreach (array_keys($data['fields']) as $field) {
        // replace newlines with [LF] for textbox
        if (!empty($data['fields'][$field]['default']) && preg_match("/\n/",$data['fields'][$field]['default'])) {
            // Note : we could use addcslashes here, but that could lead to a whole bunch of other issues...
            $data['fields'][$field]['default'] = preg_replace("/\r?\n/",'[LF]',$data['fields'][$field]['default']);
        }
        if ($data['fields'][$field]['type'] == 21) { // item id
            $isprimary = 1;
        //    break;
        }
    }

    $hooks = array();
    if ($isprimary) {
        $hooks = xarMod::callHooks('module','modifyconfig',$modinfo['name'],
                                 array('module' => $modinfo['name'],
                                       'itemtype' => $itemtype));
    }
    $data['hooks'] = $hooks;

    $data['labels'] = array(
                            'id' => xarML('ID'),
                            'name' => xarML('Name'),
                            'label' => xarML('Label'),
                            'type' => xarML('Property Type'),
                            'default' => xarML('Default'),
                            'source' => xarML('Data Source'),
                            'status' => xarML('View/Display/Input State'),
                            'validation' => xarML('Configuration'),
                            'new' => xarML('New'),
                      );

    // Specify some labels and values for display
    $data['updatebutton'] = xarVarPrepForDisplay(xarML('Update Properties'));

    $data['fieldtypeprop'] = Dynamic_Property_Master::getProperty(array('type' => 'fieldtype'));
    $data['fieldstatusprop'] = Dynamic_Property_Master::getProperty(array('type' => 'fieldstatus'));

    // We have to specify this here, the js expects non xml urls and the => makes the template invalied
    $data['urlform'] = xarModURL('dynamicdata','admin','form',array('objectid' => $data['objectid']));
    $data['layout'] = $layout;

    if (empty($details)) {
        $data['static'] = array();
        $data['relations'] = array();
        if (!empty($objectid)) {
            $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                             array('itemid' => $objectid,
                                                   'details' => 1));
        } else {
            $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                             array('modid' => $modid,
                                                   'itemtype' => empty($itemtype) ? null : $itemtype,
                                                   'details' => 1));
        }
        return $data;
    }

    $data['details'] = $details;

// TODO: allow modules to specify their own properties
    // (try to) show the "static" properties, corresponding to fields in dedicated
    // tables for this module
    $data['static'] = xarMod::apiFunc('dynamicdata','util','getstatic',
                                   array('modid' => $modid,
                                         'itemtype' => $itemtype));
    if (!isset($data['static']) || $data['static'] == false) {
        $data['static'] = array();
        $data['tables'] = array();
    } else {
        $data['tables'] = array();
        foreach ($data['static'] as $field) {
            if (preg_match('/^(\w+)\.(\w+)$/', $field['source'], $matches)) {
                $table = $matches[1];
                $data['tables'][$table] = $table;
            }
        }
    }

    $data['statictitle'] = xarML('Static Properties (guessed from module table definitions for now)');

// TODO: allow other kinds of relationships than hooks
    // (try to) get the relationships between this module and others
    $data['relations'] = xarMod::apiFunc('dynamicdata','util','getrelations',
                                       array('modid' => $modid,
                                             'itemtype' => $itemtype));
    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = array();
    }

    $data['relationstitle'] = xarML('Relationships with other Modules/Properties (only item display hooks for now)');
    $data['labels']['module'] = xarML('Module');
    $data['labels']['linktype'] = xarML('Link Type');
    $data['labels']['linkfrom'] = xarML('From');
    $data['labels']['linkto'] = xarML('To');

    if (!empty($objectid)) {
        $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                         array('itemid' => $objectid));
    } else {
        $data['detailslink'] = xarModURL('dynamicdata','admin','modifyprop',
                                         array('modid' => $modid,
                                               'itemtype' => empty($itemtype) ? null : $itemtype));
    }


    // Return the template variables defined in this function

    return $data;
}

?>