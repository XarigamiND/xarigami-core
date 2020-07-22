<?php
// Check format of URL Icon DD validations
function check_140_06()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking format of URL icon DD validations");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $dynamicpropdefs = $prefix.'_dynamic_properties_def';
        $dynamicprops = $prefix.'_dynamic_properties';
        $dynamicdata = $prefix.'_dynamic_data';

        //get all properties defined with this proptype format of 27
         $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_source, xar_prop_validation
                    FROM $dynamicprops
                   WHERE xar_prop_type = 27";

        $result = $dbconn->Execute($query);

        for (; !$result->EOF; $result->MoveNext()) {
        //we have properties to possibly update validations
            list ($propid,$propname, $propsource, $propvalidation)  = $result->fields;
            //check if it is serialized
            try {
                $check = @unserialize($propvalidation);
            } catch (Exception $e) {
                //do nothing
            }
            $serialized =  ($check===false && $propvalidation != serialize(false)) ? false : true;

             if (!$serialized && !empty($propvalidation)) {
                //we only need to find one, and if it's not serialized we need to update
                $data['success'] = true;
                $data['reply'] = xarML("Not done");
                $data['test'] =$data['test'] && false;
                break ;
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