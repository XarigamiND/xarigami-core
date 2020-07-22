<?php

function check_112_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("User time zone var check");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
     try {
        $prefix = xarDB::$prefix;
        $vars =  $prefix . '_module_vars';
        //check if the old installertheme is present
       $query = "SELECT xar_name
                FROM $vars
                WHERE xar_name = 'setusertimezone'
                ";
        $result =  $dbconn->Execute($query);
        if ($result->EOF) { //it does not exist and we need to make it
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