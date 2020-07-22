<?php

function check_114_04()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking Deny Block privileges");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        //block privs have changed since 1.1.4
        $sitePrefix = xarDB::$prefix;
        $privtable=$sitePrefix . '_privileges';
        try {
                $query = "SELECT xar_name, xar_module
                      FROM $privtable
                      WHERE xar_name = ? and xar_module =?";
                      $bindvars = array('DenyBlocks','blocks');
                      $result = $dbconn->Execute($query,$bindvars);
                      if (!$result->EOF) {
                      //they have it and need to delete it
                            $data['success'] = true;
                            $data['reply'] = xarML("Not done!");
                            $data['test'] = $data['test'] && false;
                      }
            }  catch (Exception $e) {
                $data['success'] = false;
                $data['reply'] = xarML("Bad test!");
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