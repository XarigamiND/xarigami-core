<?php
//user time zone
function fix_112_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating in User Timezone var");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
     try {
         xarUpgradeSetModVar('roles', 'setusertimezone', FALSE);
         xarUpgradeSetModVar('roles', 'usertimezone', '');

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>