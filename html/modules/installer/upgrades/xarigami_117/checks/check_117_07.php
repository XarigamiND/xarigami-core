<?php

function check_117_07()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for Read Block Groups priv");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        //check to see if the Read Block Group priv exists and is needed
        $sitePrefix = xarDB::$prefix;
        $privtable=$sitePrefix . '_privileges';
        //try get instance we know should be updated but isn't
        //CasualAccess and ReadNonCore should have ReadBlockGroup priv

        $query = "SELECT xar_name, xar_module
                  FROM $privtable
                  WHERE (xar_name = ? or xar_name = ?) and xar_module =?";
                  $bindvars = array('ReadNonCore','CasualAccess','empty');
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
         //they do not need this priv so
        }else {
            try {
                //they have one of these so we need to check to see if they have the ReadBlockGroupPriv
                $query = "SELECT xar_name, xar_module
                      FROM $privtable
                      WHERE xar_name = ? and xar_module =?";
                      $bindvars = array('ReadBlockGroups','blocks');
                      $result = $dbconn->Execute($query,$bindvars);

                      if ($result->EOF) {
                      //they do not have it and need it
                            $data['success'] = true;
                            $data['reply'] = xarML("Not done!");
                            $data['test'] =$data['test'] && false;
                      }
            }  catch (Exception $e) {
                $data['success'] = false;
                $data['reply'] = xarML("Bad test!");
                $data['test'] =$data['test'] && false;
            }
        }
    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>