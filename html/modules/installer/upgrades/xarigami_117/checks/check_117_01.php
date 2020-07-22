<?php

function check_117_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Check for registered Role Group masks");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';
        //try one
        $name = 'ViewGroupRoles';
        $module = 'roles';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
            $data['success'] = true;
            $data['reply'] = xarML("Tested OK");
            $data['test'] =$data['test'] && false;
        }

        //try another
        $name = 'EditGroupRoles';
        $module = 'roles';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {

            $data['success'] = true; //test was successful
            $data['reply'] = xarML("Tested OK");
            $data['test'] =$data['test'] && false; //outcome was false tho
        }

    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>