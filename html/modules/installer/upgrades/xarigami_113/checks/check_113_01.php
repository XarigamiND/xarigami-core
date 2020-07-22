<?php

function check_113_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking 'disallowed emails' is Roles module");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;

    try {
        //try and get the disallowedemails var from roles
        $value = xarUpgradeGetModVar('roles','disallowedemails');
        if (!$value) {
            $data['success'] = true;
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