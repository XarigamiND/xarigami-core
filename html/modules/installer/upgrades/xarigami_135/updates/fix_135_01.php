<?php
// Checking label for validation property is Configuration
//
function fix_135_01()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating label for validation property to Configuration");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $proptable = $prefix . '_dynamic_properties';
        //try one
        $query = "UPDATE $proptable
                  SET xar_prop_label = 'Configuration' WHERE xar_prop_name = 'validation'";
                  $result = $dbconn->Execute($query);

        if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Problem");
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
