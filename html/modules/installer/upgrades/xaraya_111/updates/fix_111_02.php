<?php

function fix_111_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating in page admin menu var");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
         xarUpgradeSetModVar('themes', 'adminpagemenu', TRUE);

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>