<?php

function fix_121_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating Roles masks with component 'Roles'");
    $data['reply'] = xarML("Done!");
     $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $themetable =  $prefix . '_themes';
        //check if the old installertheme is present
       $query = "SELECT xar_id, xar_name, xar_regid
                FROM $themetable
                WHERE xar_name = 'Installer' and xar_regid = 996
                ";
        $result =  $dbconn->Execute($query);
        if (!$result->EOF) { //it exists and we need to remove it
            //get some values first

            $query = "DELETE FROM $themetable
                      WHERE xar_name = 'Installer' and xar_regid = 996
                     ";
                      $result =  $dbconn->Execute($query);

        }

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>