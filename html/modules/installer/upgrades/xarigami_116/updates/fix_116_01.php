<?php

function fix_116_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating cookie name and paths");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        xarUpgradeSetVar('Site.Session.CookieName','XARIGAMISID');
        xarUpgradeSetVar('Site.Session.CookiePath','/');
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>