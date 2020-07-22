<?php
//
function fix_111_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating Roles InheritDeny var to TRUE");
    $data['reply'] = xarML("Done!");
     $dbconn = xarDB::$dbconn;
    try {
         xarUpgradeSetModVar('privileges', 'inheritdeny', TRUE);

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>