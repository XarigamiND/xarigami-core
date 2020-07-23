<?php
/**
 * Update the dynamic properties for a module + itemtype
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
 * Update the dynamic properties for a module + itemtype
 *
 * @param int objectid
 * @param int modid
 * @param int itemtype
 * @throws BAD_PARAM
 * @return bool true on success and redirect to modifyprop
 */
function dynamicdata_admin_updateprop()
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from xarVarFetch()
    if(!xarVarFetch('objectid',      'isset', $objectid,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',         'isset', $modid,          NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',      'isset', $itemtype,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',         'isset', $table,          NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_label',      'isset', $dd_label,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_type',       'isset', $dd_type,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_default',    'isset', $dd_default,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_source',     'isset', $dd_source,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_status',     'isset', $dd_status,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('dd_validation', 'isset', $dd_validation,  NULL, XARVAR_DONT_SET)) {return;}


    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;
    //initialise msg
    $msg = '';

    if (empty($itemtype)) {
        $itemtype = 0;
    }
    $dstatus = 'status';
    if (!xarMod::apiLoad('dynamicdata', 'user')) return; // throw back

    $object = xarMod::apiFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
    } elseif (!empty($modid)) {
        $modinfo = xarMod::getInfo($modid);
        if (!empty($modinfo['name'])) {
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            if (!xarMod::apiLoad('dynamicdata','admin')) return;
            $objectid = xarMod::apiFunc('dynamicdata','admin','createobject',
                                      array('moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        }
    }
    if (!isset($name) || empty($name)) $name = $dd_label;

    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'updateprop', 'dynamicdata');
        throw new EmptyParameterException(null,$msg);
    }

    $fields = xarMod::apiFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'allprops' => true));

    if (!xarMod::apiLoad('dynamicdata', 'admin')) return;

    $isprimary = 0;
    $i = 0;
    // update old fields
    foreach ($fields as $name => $field) {
        $id = $field['id'];
        $name = $field['name'];
        $i++;
        if (empty($dd_label[$id])) {
            // delete property (and corresponding data) in xaradminapi.php
            $delete = xarMod::apiFunc('dynamicdata','admin','deleteprop',   array('prop_id' => $id));
                $msg = xarML('Property "#(1)" was successfully deleted.', $name);
                xarTplSetMessage($msg,'status');
            if (!$delete) {
                $msg = xarML('Property "#(1)" was not deleted.', $name);
                xarTplSetMessage($msg,'warning');
                return;
            }
        } else {
        // TODO : only if necessary
            // update property in xaradminapi.php
            if (!isset($dd_default[$id])) {
                $dd_default[$id] = null;
            } elseif (!empty($dd_default[$id]) && preg_match('/\[LF\]/',$dd_default[$id])) {
                // replace [LF] with line-feed again
                $lf = chr(10);
                $dd_default[$id] = preg_replace('/\[LF\]/',$lf,$dd_default[$id]);
            }
            if (!isset($dd_validation[$id])) {
                $dd_validation[$id] = null;
            }

            if (!isset($dd_status[$id])) {
                $dd_status[$id] = (int)Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE;
            } else {
                 $dd_status[$id] = (int) $dd_status[$id] ;
            }


            $updated = xarMod::apiFunc('dynamicdata','admin','updateprop',
                              array('prop_id' => $id,
                              //      'modid' => $modid,
                              //      'itemtype' => $itemtype,
                                    'label' => $dd_label[$id],
                                    'type' => (int)$dd_type[$id],
                                    'default' => $dd_default[$id],
                              //      'source' => $dd_source[$id],
                                    'status' => $dd_status[$id],
                                    'validation' => $dd_validation[$id]));
                                    

            if (!$updated) {
                $msg = xarML('There was a problem. Property "#(1)" was not updated.', $name);
                 xarTplSetMessage($msg,'warning');
                return;
            } else {
             if (empty($dd_label[0]) || empty($dd_type[0])) {
                $msg = xarML('Property "#(1)" was successfully updated.', $name);
                xarTplSetMessage($msg,'status');
                }
            }
            if ($dd_type[$id] == 21) { // item id
                $isprimary = 1;
            }
        }
                           }

    $i++;
    // insert new field
    if (!empty($dd_label[0]) && !empty($dd_type[0])) {
        // create new property in xaradminapi.php
        $name = strtolower($dd_label[0]);
        $name = preg_replace('/[^a-z0-9_]+/','_',$name);
        $name = preg_replace('/_$/','',$name);
        if (!isset($dd_status[0])) {
            $dd_status[0] = Dynamic_Property_Master::DD_DISPLAYSTATE_ACTIVE;
        }

        $prop_id = xarMod::apiFunc('dynamicdata','admin','createproperty',
                                array('name' => $name,
                                      'label' => $dd_label[0],
                                      'objectid' => $objectid,
                                      'moduleid' => $modid,
                                      'itemtype' => $itemtype,
                                      'type' => $dd_type[0],
                                      'default' => $dd_default[0],
                                      'source' => $dd_source[0],
                                      'status' => $dd_status[0],
                                      'order' => $i,
                                      'validation' =>isset($dd_validation[0]) ? $dd_validation[0] :''));
        if (empty($prop_id)) {
            return;
        } else {
              $msg = xarML('Property "#(1)" was successfully created.', $name);
              xarTplSetMessage($msg,'status');
        }
        if ($dd_type[0] == 21) { // item id
            $isprimary = 1;
        }


    }


    if ($isprimary) {
        $modinfo = xarMod::getInfo($modid);
        xarMod::callHooks('module','updateconfig',$modinfo['name'],
                        array('module' => $modinfo['name'],
                              'itemtype' => $itemtype));

    }


    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                        array('modid'    => $modid,
                              'itemtype' => $itemtype,
                              'table'    => $table)));

    // Return
    return true;
}

?>
