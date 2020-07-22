<?php

function fix_117_03()
{

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating with UID instead of Name in Roles privs");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $sitePrefix = xarDB::$prefix;
        $rolestable=$sitePrefix . '_roles';
        //get all the existing privs with component Roles and component All (in case they didn't upgrade yet)
        $privstable=$sitePrefix . '_privileges';

        $query="UPDATE $privstable
               INNER JOIN  $rolestable ON $rolestable.xar_name = $privstable.xar_instance
               SET $privstable.xar_instance = $rolestable.xar_uid
               WHERE $privstable.xar_module = 'roles'";
               $result = $dbconn->Execute($query);

    } catch (Exception $e) {
        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>