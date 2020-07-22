<?php
// Check object for correct configuration
function check_140_18()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking object format");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $props = $prefix.'_dynamic_properties';
        $objects = $prefix.'_dynamic_objects';
        //get base menu type
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_objectid, xar_prop_moduleid, xar_prop_itemtype, xar_prop_type,  xar_prop_validation
                    FROM $props
                   WHERE xar_prop_name = 'config' and xar_prop_objectid=1 and xar_prop_moduleid = 182 and xar_prop_itemtype = 0";
        $result = $dbconn->Execute($query);

        if (!$result) {
             $data['success'] = false;
             $data['reply'] = xarML("Test problem");
             $data['test'] =$data['test'] && false;
             return $data ;
        }

        list ($propid, $propname, $objectid, $moduleid, $itemtype, $proptype, $propvalidation) = $result->fields;
       if (($proptype != 999) || (substr($propvalidation,0,2) != 'a:')) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
            return $data ;
       }

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}


?>