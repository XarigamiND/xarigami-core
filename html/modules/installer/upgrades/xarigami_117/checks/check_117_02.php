<?php
//check for new DD modvars
function check_117_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Check Roles Group instance exists");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $sitePrefix = xarDB::$prefix;
        $secinstancetable=$sitePrefix . '_security_instances';

        $query = "SELECT xar_module, xar_component
                  FROM $secinstancetable
                  WHERE xar_module = ? and xar_component =?";
                  $bindvars = array('roles','Group');
                  $result = $dbconn->Execute($query,$bindvars);
        if ($result->EOF) { //it's not added
            $data['success'] = true;
            $data['reply'] = xarML("Tested OK");
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