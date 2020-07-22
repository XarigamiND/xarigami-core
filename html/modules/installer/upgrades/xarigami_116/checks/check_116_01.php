<?php

function check_116_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking cookie name and path");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
         $cookiename = xarUpgradeGetVar('Site.Session.CookieName');
         if (empty($cookiename)) {
            $data['success'] = false;
             $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
         }
        $cookiename = xarUpgradeGetVar('Site.Session.CookiePath');
         if (empty($cookiename)) {
            $data['success'] = false;
             $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
         }

    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>