<?php

function fix_118_06()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Set default timezone");
    $data['reply'] = xarML("Done!");

    try {
        //we do not have a valid one, so let's just set it
        $timezone = 'Etc/UTC';
        $settime = xarUpgradeSetVar('Site.Core.TimeZone',$timezone);

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>