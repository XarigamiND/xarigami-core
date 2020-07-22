<?php
/**
 * Manage definition of instances for privileges
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
 * Manage definition of instances for privileges (unfinished)
 */
function dynamicdata_admin_privileges($args)
{
    extract($args);

    // Security Check
    if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarVarFetch('objectid', 'id' , $objectid, NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('moduleid', 'str', $moduleid, 0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric or modulename
    if (!xarVarFetch('itemtype', 'str', $itemtype, 0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric
    if (!xarVarFetch('itemid', 'str', $itemid, 0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric
    if (!xarVarFetch('propname', 'str', $propname,'', XARVAR_NOT_REQUIRED)) return; // empty, 'All', string
    if (!xarVarFetch('proptype', 'str', $proptype,0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric
    if (!xarVarFetch('propid', 'str', $propid, 0, XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric
    if (!xarVarFetch('apply', 'str' , $apply , false, XARVAR_NOT_REQUIRED)) return; // boolean?
    if (!xarVarFetch('extpid', 'str', $extpid, '', XARVAR_NOT_REQUIRED)) return; // empty, 'All', numeric ?
    if (!xarVarFetch('extname', 'str', $extname, '', XARVAR_NOT_REQUIRED)) return; // ?
    if (!xarVarFetch('extrealm', 'str', $extrealm, '', XARVAR_NOT_REQUIRED)) return; // ?
    if (!xarVarFetch('extmodule','str', $extmodule, '', XARVAR_NOT_REQUIRED)) return; // ?
    if (!xarVarFetch('extcomponent', 'enum:All:Item:Field:Type', $extcomponent)) return; // FIXME: is 'Type' needed?
    if (!xarVarFetch('extinstance', 'str:1', $extinstance, '', XARVAR_NOT_REQUIRED)) return; // somthing:somthing:somthing or empty
    if (!xarVarFetch('extlevel', 'str:1', $extlevel)) return;
    if (!xarVarFetch('pparentid',    'isset', $pparentid,    NULL, XARVAR_DONT_SET)) return;

// TODO: combine 'Item' and 'Type' instances someday ?

    if (!empty($extinstance)) {
        $parts = explode(':',$extinstance);
        if ($extcomponent == 'Item') {
            if (count($parts) > 0 && !empty($parts[0])) $moduleid = $parts[0];
            if (count($parts) > 1 && !empty($parts[1])) $itemtype = $parts[1];
            if (count($parts) > 2 && !empty($parts[2])) $itemid = $parts[2];
        } else {
            if (count($parts) > 0 && !empty($parts[0])) $propname = $parts[0];
            if (count($parts) > 1 && !empty($parts[1])) $proptype = $parts[1];
            if (count($parts) > 2 && !empty($parts[2])) $propid = $parts[2];
        }
    }

    if ($extcomponent == 'Item') {

        if (empty($moduleid) || $moduleid == 'All') {
            $moduleid = 0;
        } elseif (!is_numeric($moduleid)) { // for pre-wizard instances
            $modid = xarMod::getId($moduleid);
            if (!empty($modid)) {
                $moduleid = $modid;
            } else {
                $moduleid = 0;
            }
        }
        if (empty($itemtype) || $itemtype == 'All' || !is_numeric($itemtype)) {
            $itemtype = 0;
        }
        if (empty($itemid) || $itemid == 'All' || !is_numeric($itemid)) {
            $itemid = 0;
        }

        // define the new instance
        $newinstance = array();
        $newinstance[] = empty($moduleid) ? 'All' : $moduleid;
        $newinstance[] = empty($itemtype) ? 'All' : $itemtype;
        $newinstance[] = empty($itemid) ? 'All' : $itemid;

    } else {

        if (empty($propname) || $propname == 'All' || !is_string($propname)) {
            $propname = '';
        }
        if (empty($proptype) || $proptype == 'All' || !is_numeric($proptype)) {
            $proptype = 0;
        }
        if (empty($propid) || $propid == 'All' || !is_numeric($propid)) {
            $propid = 0;
        }

        // define the new instance
        $newinstance = array();
        $newinstance[] = empty($propname) ? 'All' : $propname;
        $newinstance[] = empty($proptype) ? 'All' : $proptype;
        $newinstance[] = empty($propid) ? 'All' : $propid;

    }

    if (!empty($apply)) {
        // create/update the privilege
        $pid = xarReturnPrivilege($extpid,$extname,$extrealm,$extmodule,$extcomponent,
                                  $newinstance,$extlevel,$pparentid);
        if (empty($pid)) {
            return; // throw back
        }

        // redirect to the privilege
        xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyprivilege',
                                      array('pid' => $pid)));
        return true;
    }

    // Get objects
    $objects = xarMod::apiFunc('dynamicdata','user','getobjects');

    // TODO: use object list instead of (or in addition to) module + itemtype

    // Get module list
    $objectlist = array();
    $modlist = array();
    // Get a list of all modules - we just want their IDs
    $all_modules = xarMod::apiFunc('modules', 'admin', 'getlist');
    $all_module_ids = array();
    foreach($all_modules as $this_module) {
        $all_module_ids[] = $this_module['regid'];
    }
    foreach ($objects as $id => $object) {
        $objectlist[$id] = $object['label'];
        $modid = $object['moduleid'];
        // Check whether the module exists before trying to fetch the details.
        if (in_array($modid, $all_module_ids)) {
            $modinfo = xarMod::getInfo($modid);
            $modlist[$modid] = $modinfo['displayname'];
        }
    }

    // Get property types
    $proptypes = xarMod::apiFunc('dynamicdata','user','getproptypes');

    // Get properties
    $properties = xarMod::apiFunc('dynamicdata','user','getitems',
                                array('module' => 'dynamicdata',
                                      'itemtype' => 1));
    $propnames = array();
    $propids = array();
    foreach ($properties as $property) {
        $propnames[$property['name']] = 1;
        if (!isset($objectlist[$property['objectid']])) continue;
        $objectname = $objectlist[$property['objectid']];
        if (!isset($propids[$objectname])) {
            $propids[$objectname] = array();
        }
        $propids[$objectname][$property['id']] = $property['label'];
    }
    ksort($propnames);

    if ($extcomponent == 'Item') {
        if (!empty($itemid)) {
            $numitems = xarML('probably');
        } elseif (!empty($objectid) || !empty($moduleid)) {
            $numitems = xarMod::apiFunc('dynamicdata','user','countitems',
                                      array('objectid' => $objectid,
                                            'moduleid' => $moduleid,
                                            'itemtype' => $itemtype));
            if (empty($numitems)) {
                $numitems = 0;
            }
        } else {
            $numitems = xarML('probably');
        }

    } else { // 'Type'

        $numitems = xarML('probably');

    }

    $data = array(
                  'objectid'     => $objectid,
                  'moduleid'     => $moduleid,
                  'itemtype'     => $itemtype,
                  'itemid'       => $itemid,
                  'propname'     => $propname,
                  'proptype'     => $proptype,
                  'propid'       => $propid,
                  'objectlist'   => $objectlist,
                  'modlist'      => $modlist,
                  'propnames'    => $propnames,
                  'proptypes'    => $proptypes,
                  'propids'      => $propids,
                  'numitems'     => $numitems,
                  'extpid'       => $extpid,
                  'extname'      => $extname,
                  'extrealm'     => $extrealm,
                  'extmodule'    => $extmodule,
                  'extcomponent' => $extcomponent,
                  'extlevel'     => $extlevel,
                  'extinstance'  => xarVarPrepForDisplay(join(':',$newinstance)),
                  'pparentid'    => $pparentid,
                 );

    $data['refreshlabel'] = xarML('Refresh');
    $data['applylabel'] = xarML('Finish and Apply to Privilege');

    return $data;
}

?>