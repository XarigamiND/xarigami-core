<?php
//user time zone
function fix_112_03()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Ensure users cannot send mail by default");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
     try {
         xarUpgradeSetModVar('roles', 'usersendemails', FALSE);

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>