<?php
//check for security level addition of access moderate and access edit swap
function check_118_03()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking ACLs for correct Moderate levels");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        // Let's check and see if this site has already been upgraded for changed edit/moderate levels
        $levelstable = xarDB::$prefix . '_security_levels';
        $query = "SELECT xar_leveltext
                  FROM $levelstable
                  WHERE xar_level = ? and xar_leveltext =?";
                  $bindvars = array('400','ACCESS_EDIT');
                  $result = $dbconn->Execute($query,$bindvars);
        if ($result->EOF) { //it's not upgraded
            $data['success'] = true;
            $data['reply'] = xarML("Not done!");
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