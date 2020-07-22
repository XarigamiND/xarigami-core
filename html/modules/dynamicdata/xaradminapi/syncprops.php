<?php
/**
 * Resynchronise properties with object
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * resynchronise properties with object (for module & itemtype)
 *
 * @author the DynamicData module development team
 * @param $args['objectid'] object id for the properties you want to update
 * @param $args['moduleid'] new module id for the properties
 * @param $args['itemtype'] new item type for the properties
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_syncprops($args)
{
    extract($args);
    // Required arguments
    $invalid = array();
    if (!isset($objectid) || !is_numeric($objectid)) {
        $invalid[] = 'object id';
    }
    if (!isset($moduleid) || !is_numeric($moduleid)) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'syncprops', 'DynamicData');
        throw new BadParameterException(null,$msg);
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "UPDATE $dynamicprop
            SET xar_prop_moduleid = ?, xar_prop_itemtype = ?
            WHERE xar_prop_objectid = ?";
    $bindvars = array($moduleid, $itemtype, $objectid);
    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    return true;
}

?>