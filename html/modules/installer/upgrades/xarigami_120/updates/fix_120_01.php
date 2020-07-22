<?php

function fix_120_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Update Dynamic Data systemobjects and itemsperpage mod vars ");
    $data['reply'] = xarML("Done!");
    try {
         $sysobjects = 'a:4:{i:0;s:1:"1";i:1;s:1:"2";i:2;s:1:"4";i:3;s:1:"5";}';
         xarUpgradeSetModVar('DynamicData','systemobjects', $sysobjects);
         xarUpgradeSetModVar('DynamicData','itemsperpage', 20);
    } catch (Exception $e) {
        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>