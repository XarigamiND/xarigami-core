<?php

function fix_114_01()
{
     // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating Roles masks with component 'Roles'");
    $data['reply'] = xarML("Done!");
     $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';
        $module = 'roles';
        $maskarray = array('ViewRoles','ReadRole','EditRole','AddRole','DeleteRole','AdminRole');
        foreach($maskarray as $m) {
            $query = "UPDATE $secmasktable
                       SET  xar_component = ?
                       WHERE xar_name = ? and xar_module = ?";
                      $bindvars = array('Roles',$m,$module);
                      $result = $dbconn->Execute($query,$bindvars);
        }
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>