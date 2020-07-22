<?php
// Check label for validation property is Configuration
function check_135_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking label for validation property is Configuration");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $proptable = $prefix . '_dynamic_properties';
        //try one
        $query = "SELECT xar_prop_id, xar_prop_name, xar_prop_label
                  FROM   $proptable
                  WHERE xar_prop_name = 'validation'";
                  $result = $dbconn->Execute($query);

        if ($result->EOF) {
            $data['success'] = false;
            $data['reply'] = xarML("Bad test");
            $data['test'] =$data['test'] && false;
        }
        list($propid,$propname,$proplabel) = $result->fields;

        if ($proplabel != 'Configuration') {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }

    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}

?>