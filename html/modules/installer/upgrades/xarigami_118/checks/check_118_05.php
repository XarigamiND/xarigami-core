<?php

function check_118_05()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for Moderate Base mask");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';

        //check SubmitDynamicDataItem mask exists
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';

        $name = 'ModerateBase';
        $module = 'base';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
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