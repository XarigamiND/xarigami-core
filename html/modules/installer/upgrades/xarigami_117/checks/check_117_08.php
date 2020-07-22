<?php

function check_117_08()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Check Admin Lock privs exist");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        //check we have an AdminLock privilege if not, we'll have to make it later and it's members
        $sitePrefix = xarDB::$prefix;
        $privtable=$sitePrefix . '_privileges';
        //try get instance we know should be updated but isn't
        //CasualAccess and ReadNonCore should have ReadBlockGroup priv

        $query = "SELECT xar_name, xar_module
                  FROM $privtable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array('AdminLock','empty');
                  $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
            //it doesn't exist so we need to make it
            $data['success'] = true;
            $data['reply'] = xarML("Tested");
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