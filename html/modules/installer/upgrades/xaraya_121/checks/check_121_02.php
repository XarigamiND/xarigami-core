<?php

function check_121_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for old Xaraya template tags");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $tagtable =  $prefix . '_template_tags';
        $tagarray = array('base-js-plugin','base-js-event','base-js-framework','base-js-plugin');
        //check for existence
        foreach ($tagarray as $tag) {
            $query = "SELECT xar_id, xar_name, xar_module
                        FROM $tagtable
                       WHERE xar_name = ? and xar_module = ?
                    ";
                    $bindvars = array($tag,'base');
                     $result =  $dbconn->Execute($query,$bindvars);

            if (!$result->EOF) { //it exists and we need to remove it
                $data['success'] = true;
                 $data['reply'] = xarML("Not done!");
                $data['test'] =$data['test'] && false;
            }
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