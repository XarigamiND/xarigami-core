<?php
//inherit deny check ensure it is set to TRUE
function check_112_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for Timesince Tag registration");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
     try {
        $prefix = xarDB::$prefix;
        $tags =  $prefix . '_template_tags';
        //check if the old installertheme is present
       $query = "SELECT xar_name
                FROM $tags
                WHERE xar_name = 'base-timesince'
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