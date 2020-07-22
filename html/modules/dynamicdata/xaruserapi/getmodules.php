<?php
/**
 * Get list of modules and itemtypes with dynamic properties
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * get the list of modules + itemtypes for which dynamic properties are defined
 *
 * @author the DynamicData module development team
 * @return array of modid + itemtype + number of properties
 * @throws DATABASE_ERROR, NO_PERMISSION
 */
function dynamicdata_userapi_getmodules($args)
{
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $dynamicprop = $xartable['dynamic_properties'];

    $query = "SELECT xar_prop_moduleid,
                     xar_prop_itemtype,
                     COUNT(xar_prop_id)
              FROM $dynamicprop
              GROUP BY xar_prop_moduleid, xar_prop_itemtype
              ORDER BY xar_prop_moduleid ASC, xar_prop_itemtype ASC";

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $modules = array();

    while (!$result->EOF) {
        list($modid, $itemtype, $count) = $result->fields;
        if(xarSecurityCheck('ViewDynamicDataItems',0,'Item',"$modid:$itemtype:All")) {
            $modules[] = array('modid' => $modid,
                               'itemtype' => $itemtype,
                               'numitems' => $count);
        }
        $result->MoveNext();
    }

    $result->Close();

    return $modules;
}

?>