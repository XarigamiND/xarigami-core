<?php

function check_118_06()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for valid timezone");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;

    try {
        $timezone = xarUpgradeGetVar('Site.Core.TimeZone');

        $test =  xarInstallAPIFunc('timezones',
                            array('modName'=>'base',
                                 'modType'=>'user',
                                 'timezone'=>$timezone)
                                  );
        if (!isset($test) ||empty($test) || empty($timezone)) {
        //we do not have a valid timeone
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