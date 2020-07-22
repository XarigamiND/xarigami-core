<?php

function check_132_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for webDir, libDir, codeDir in config.system.php ");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $configfile = sys::varpath() . '/config.system.php';
    $testfile = file($configfile);
    $testvars = array('webDir','libDir','codeDir','siteDir');
    $checkarray = array();
    foreach ($testfile as $dataline) {
        foreach ($testvars as $var) {
            $checkvar = preg_match('/\[\''.$var.'\'\]/', $dataline, $matches);
            if (isset($matches[0]) && !empty($matches[0])) {
                $checkarray[$var] = true;
                break;
            }
        }
    }

    if (count($checkarray)< 4) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
    }
    return $data;
}

?>