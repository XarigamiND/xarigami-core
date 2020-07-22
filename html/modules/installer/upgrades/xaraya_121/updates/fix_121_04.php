<?php

function fix_121_04()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Deleting old Xaraya menu mod vars");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $modvartable =  $prefix . '_module_vars';
        $query = "DELETE FROM $modvartable
                 WHERE xar_name  = ? or xar_name = ?
                ";
                $bindvars = array('user_menu_link','admin_menu_link');
                $result =  $dbconn->Execute($query,$bindvars);

        $result->close();

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>