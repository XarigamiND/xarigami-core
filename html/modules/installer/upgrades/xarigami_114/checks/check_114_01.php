<?php

function check_114_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking Roles mask components for 'Roles'");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';
        $module = 'roles';
        $maskarray = array('ViewRoles','ReadRole','EditRole','AddRole','DeleteRole','AdminRole');
        foreach($maskarray as $m) {
            $query = "SELECT xar_name, xar_module, xar_component
                      FROM   $secmasktable
                      WHERE xar_name = ? and xar_module =?";
                      $bindvars = array($m,$module);
                       $result = $dbconn->Execute($query,$bindvars);

            if (!$result->EOF) {
                list($name,$module,$component) = $result->fields;
                if ($component == 'All') {
                     $data['success'] = false;
                    $data['reply'] = xarML("Not done!");
                    $data['test'] =$data['test'] && false;
                }
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