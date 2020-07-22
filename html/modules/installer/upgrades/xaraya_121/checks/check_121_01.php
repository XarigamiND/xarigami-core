<?php

function check_121_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for old Xaraya Installer theme");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $themetable =  $prefix . '_themes';
        //check if the old installertheme is present
       $query = "SELECT xar_id, xar_name, xar_regid
                FROM $themetable
                WHERE xar_name = 'Installer' and xar_regid = 996
                ";
        $result =  $dbconn->Execute($query);
        if (!$result->EOF) { //it exists and we need to remove it
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