<?php
/**
 * Delete a property field
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * delete a property field
 *
 * @author the DynamicData module development team
 * @param $args['prop_id'] property id of the item field to delete
 * @todo do we want those for security check ? Yes, but the original values...
 * @param $args['modid'] module id of the item field to delete
 * @param $args['itemtype'] item type of the item field to delete
 * @param $args['name'] name of the field to delete
 * @param $args['label'] label of the field to delete
 * @param $args['type'] type of the field to delete
 * @param $args['default'] default of the field to delete
 * @param $args['source'] data source of the field to delete
 * @param $args['validation'] validation of the field to delete
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_deleteprop($args)
{
    extract($args);

    // Required arguments
    $invalid = array();
    if (!isset($prop_id) || !is_numeric($prop_id)) {
        $invalid[] = 'property id';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'deleteprop', 'DynamicData');
         throw new BadParameterException(null,$msg);
    }

    // Security check - important to do this as early on as possible to
    // avoid potential security holes or just too much wasted processing
// TODO: check based on other arguments too
    if(!xarSecurityCheck('DeleteDynamicDataField',1,'Field',"All:All:$prop_id")) return;

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    // It's good practice to name the table and column definitions you
    // are getting - $table and $column don't cut it in more complex
    // modules
    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "DELETE FROM $dynamicprop WHERE xar_prop_id = ?";
    $result = $dbconn->Execute($sql,array($prop_id));
    if (!$result) return;

// TODO: don't delete if the data source is not in dynamic_data
    // delete all data too !
    $dynamicdata = $xartable['dynamic_data'];

    $sql = "DELETE FROM $dynamicdata WHERE xar_dd_propid = ?";
    $result = $dbconn->Execute($sql,array($prop_id));
    if (!$result) return;

    return true;
}

?>