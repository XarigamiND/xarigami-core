<?php
// Check object configuration
function check_140_22()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking object configuration");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $props = $prefix.'_dynamic_properties';
        //get the object configuration prpo
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_validation
                    FROM  $props
                      WHERE xar_prop_name = 'config' and xar_prop_objectid = 1 and xar_prop_moduleid = 182 and xar_prop_type = 999"; //jojo -ah, the object id - we can't be relying on that in the future

        $result = $dbconn->Execute($query);
        if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Test error");
            $data['test'] = false;
        }

        list($propid, $propname, $propconfig) = $result->fields;
        $result->close() ;
        if (!is_array($propconfig) && substr( $propconfig,0,2) == 'a:')
        {
                try {
                    $check = unserialize($propconfig);
                }catch (Exception $e) {
                     //maybe needs updating anyway as there is no serialize value
                    $data['success'] = true;
                    $data['reply'] = xarML("Not done");
                    $data['test'] =$data['test'] && false;
                }

                if ((isset($check['xv_columns']) && $check['xv_columns'] >1) ||  (isset($check['xv_max_rows']) && $check['xv_max_rows'] != 0))
                {
                    $data['success'] = true;
                    $data['reply'] = xarML("Not done");
                    $data['test'] =$data['test'] && false;
                }
        }


    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}


?>