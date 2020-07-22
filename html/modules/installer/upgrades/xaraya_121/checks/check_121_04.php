<?php

function check_121_04()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for menu link mod vars");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $modvartable =  $prefix . '_module_vars';
        $query = "SELECT xar_id, xar_name
                FROM $modvartable
                WHERE xar_name  = ? or xar_name = ?
                ";
                $bindvars = array('user_menu_link','admin_menu_link');
                $result =  $dbconn->Execute($query,$bindvars);
         if (!$result->EOF) { //we have vars that need to be deleted
            $data['success'] = true;
            $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
        }
        $result->close();

    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>