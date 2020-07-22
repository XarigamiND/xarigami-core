<?php
/**
 * Utility function to create the native objects of this module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * utility function to create the native objects of this module
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns boolean
 */
function privileges_adminapi_createobjects($args)
{
    $moduleid = 1098;

# --------------------------------------------------------
#
# Create the privilege object
#
    $prefix = xarDB::$prefix;
    $itemtype = 1;
    $objectid = xarMod::apiFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'baseprivilege',
                                    'label'    => 'Base Privilege',
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'parent'    => 0,
                                    ));
    if (!$objectid) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'id',
                                    'label'    => 'ID',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 21,
                                    'source'   =>  $prefix . '_privileges.xar_pid',
                                    'status'   => 1,
                                    'order'    => 1,
                                    ))) return;

# --------------------------------------------------------
#
# Create the privilege object
#
    $itemtype = 2;
    $objectid = xarMod::apiFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'privilege',
                                    'label'    => 'Privilege',
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'parent'    => 0,
                                    ));
    if (!$objectid) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'id',
                                    'label'    => 'ID',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 21,
                                    'source'   =>  $prefix . '_privileges.xar_pid',
                                    'status'   => 1,
                                    'order'    => 1,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'name',
                                    'label'    => 'Name',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_name',
                                    'status'   => 1,
                                    'order'    => 2,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'realm',
                                    'label'    => 'Realm',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_realm',
                                    'status'   => 1,
                                    'order'    => 3,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'module',
                                    'label'    => 'Module',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_module',
                                    'status'   => 1,
                                    'order'    => 4,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'component',
                                    'label'    => 'Component',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_component',
                                    'status'   => 1,
                                    'order'    => 5,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'instance',
                                    'label'    => 'Instance',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_instance',
                                    'status'   => 1,
                                    'order'    => 6,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'level',
                                    'label'    => 'Level',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 15,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_level',
                                    'status'   => 1,
                                    'order'    => 7,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'description',
                                    'label'    => 'Description',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_privileges.xar_description',
                                    'status'   => 1,
                                    'order'    => 8,
                                    ))) return;

# --------------------------------------------------------
#
# Create the mask object
#
    $itemtype = 3;
    $objectid = xarMod::apiFunc('dynamicdata','admin','createobject',array(
                                    'name'     => 'mask',
                                    'label'    => 'Mask',
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'parent'    => 0,
                                    ));
    if (!$objectid) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'id',
                                    'label'    => 'ID',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 21,
                                    'source'   =>  $prefix . '_security_masks.xar_sid',
                                    'status'   => 1,
                                    'order'    => 1,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'name',
                                    'label'    => 'Name',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_name',
                                    'status'   => 1,
                                    'order'    => 2,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'realm',
                                    'label'    => 'Realm',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_realm',
                                    'status'   => 1,
                                    'order'    => 3,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'module',
                                    'label'    => 'Module',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_module',
                                    'status'   => 1,
                                    'order'    => 4,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'component',
                                    'label'    => 'Component',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_component',
                                    'status'   => 1,
                                    'order'    => 5,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'instance',
                                    'label'    => 'Instance',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_instance',
                                    'status'   => 1,
                                    'order'    => 6,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'level',
                                    'label'    => 'Level',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 15,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_level',
                                    'status'   => 1,
                                    'order'    => 7,
                                    ))) return;
    if (!xarMod::apiFunc('dynamicdata','admin','createproperty',array(
                                    'name'     => 'description',
                                    'label'    => 'Description',
                                    'objectid' => $objectid,
                                    'moduleid' => $moduleid,
                                    'itemtype' => $itemtype,
                                    'type'     => 2,
//                                    'default'  => '',
                                    'source'   =>  $prefix . '_security_masks.xar_description',
                                    'status'   => 1,
                                    'order'    => 8,
                                    ))) return;

    return true;
}

?>
