<?php
//inherit deny check ensure it is set to TRUE
function check_111_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for InheritDeny set TRUE");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
      try {
        //try and get the disallowedemails var from roles
        $value = xarUpgradeGetModVar('privileges','inheritdeny');
        if ($value == FALSE) {
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