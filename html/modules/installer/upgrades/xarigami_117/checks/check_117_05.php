<?php

function check_117_05()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking roles Mail instance exists");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $sitePrefix = xarDB::$prefix;
        $secinstancetable=$sitePrefix . '_security_instances';
        //try get instance we know should be updated but isn't

        $query = "SELECT xar_module, xar_component, xar_query
                  FROM $secinstancetable
                  WHERE xar_module = ? and xar_component =?";
                  $bindvars = array('roles','Mail');
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) { //missing .. we need to fix that
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