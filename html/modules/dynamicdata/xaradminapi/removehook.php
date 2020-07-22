<?php
/**
 * Delete all dynamicdata fields for a module
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
 * delete all dynamicdata fields for a module - hook for ('module','remove','API')
 *
 * @param $args['objectid'] ID of the object (must be the module name here !!)
 * @param $args['extrainfo'] extra information
 * @return array extrainfo
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_adminapi_removehook($args)
{
    extract($args);

    if (!isset($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, we should get the real module name from objectid
    // here, because the current module is probably going to be 'modules' !!!
    if (!isset($objectid) || !is_string($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'object ID (= module name)', 'admin', 'removehook', 'dynamicdata');
        throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    $modid = xarMod::getId($objectid);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module ID', 'admin', 'removehook', 'dynamicdata');
         throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    if(!xarSecurityCheck('DeleteDynamicDataItem',0,'Item',"$modid:All:All")) {
        // we *must* return $extrainfo for now, or the next hook will fail
        //return false;
        return $extrainfo;
    }

    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $dynamicprop = $xartable['dynamic_properties'];

    $sql = "SELECT xar_prop_id FROM $dynamicprop WHERE xar_prop_moduleid = ?";
    $result = $dbconn->Execute($sql,array($modid));
    if (!$result) {
        // CHECKME: do we need to set exception here? I thought this was taken care of
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
         throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        // MrB: why does the next hook need to run when we have a system exception
        // pending?
        return $extrainfo;
    }
    $ids = array();
    while (!$result->EOF) {
        list($id) = $result->fields;
        $result->MoveNext();
        $ids[] = $id;
    }
    $result->Close();

    if (count($ids) == 0) {
        return $extrainfo;
    }

    $dynamicdata = $xartable['dynamic_data'];

// TODO: don't delete if the data source is not in dynamic_data
    // Delete the item fields
    try {
        $bindmarkers = '?' . str_repeat(',?',count($ids)-1);
        $sql = "DELETE FROM $dynamicdata
                WHERE xar_dd_propid IN ($bindmarkers)";
        $result = $dbconn->Execute($sql,$ids);

/*    if (!$result) {
        $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                    'admin', 'removehook', 'dynamicdata');
         throw new BadParameterException(null,$msg);
        // we *must* return $extrainfo for now, or the next hook will fail
        // MrB: why does the next hook need to run when we have a system exception
        // pending?
        //return false;
        return $extrainfo;
    }
*/
    // Delete the properties
        $sql = "DELETE FROM $dynamicprop
                WHERE xar_prop_id IN ($bindmarkers)";
        $dbconn->Execute($sql,$ids);
    /*
        if ($dbconn->ErrorNo() != 0) {
            $msg = xarML('Database error for #(1) function #(2)() in module #(3)',
                        'admin', 'removehook', 'dynamicdata');
             throw new BadParameterException(null,$msg);
            // we *must* return $extrainfo for now, or the next hook will fail
            // MrB: why does the next hook need to run when we have a system exception
            // pending?
            //return false;
            return $extrainfo;
        }
    */
    } catch(Exception $e) {
        throw $e;
    }
    // Return the extra info
    return $extrainfo;
}

?>
