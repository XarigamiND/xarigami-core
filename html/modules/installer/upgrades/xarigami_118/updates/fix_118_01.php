<?php

function fix_118_01()
{

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding theme 'selpreview'  mod vars ");
    $data['reply'] = xarML("Done!");

    try {
         xarUpgradeSetModVar('themes','selpreview',FALSE);

    } catch (Exception $e) {
        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>