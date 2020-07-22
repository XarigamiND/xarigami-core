<?php
//check for new DD modvars
function check_118_04()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking privs/masks are updated for edit/moderate levels");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        // Let's check and see if this site has already been upgraded for changed edit/moderate levels
        $maskstable = xarDB::$prefix . '_security_masks';
        //try get one we know should be updated
        //test for EditPrivilege - if it is not 400 then it hasn't been upgraded
        $query = "SELECT xar_name
                  FROM $maskstable
                  WHERE xar_level = ? and xar_name =?";
                  $bindvars = array('400','EditPrivilege');
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