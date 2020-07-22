<?php

function check_117_10()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Check for correct Realm and Privilege Masks");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';
        $newmaskarray = array (
            array('ViewPrivileges','All','privileges','All','All','ACCESS_OVERVIEW',''),
            array('ReadPrivilege','All','privileges','All','All','ACCESS_READ',''),
            array('EditPrivilege','All','privileges','All','All','ACCESS_EDIT',''),
            array('AddPrivilege','All','privileges','All','All','ACCESS_ADD',''),
            array('DeletePrivilege','All','privileges','All','All','ACCESS_DELETE',''),
            array('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN',''),
            array('ViewRealm','All','privileges','Realm','All','ACCESS_OVERVIEW',''),
            array('ReadRealm','All','privileges','Realm','All','ACCESS_READ',''),
            array('EditRealm','All','privileges','Realm','All','ACCESS_EDIT',''),
            array('AddRealm','All','privileges','Realm','All','ACCESS_ADD',''),
            array('DeleteRealm','All','privileges','Realm','All','ACCESS_DELETE',''),
            array('AdminRealm','All','privileges','Realm','All','ACCESS_ADMIN',''),
            );
        //now check each
        $module = 'privileges';
        foreach ($newmaskarray as $newmasks=>$mask) {
            $query = "SELECT xar_name, xar_module
                      FROM   $secmasktable
                      WHERE xar_name = ? and xar_module =?";
                      $bindvars = array($mask[0],$module);
                       $result = $dbconn->Execute($query,$bindvars);

            if ($result->EOF) {
                $data['success'] = true;
                $data['reply'] = xarML("Not done!");
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