<?php
// This checks to see if there is a system configuration line with DB.Charset eg
//$systemConfiguration['DB.Charset']   =  'utf8'; //the default character set of your database
function check_134_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for character set in config.system.php ");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $configfile = sys::varpath() . '/config.system.php';
    $testfile = file($configfile);

    $found = false;
    foreach ($testfile as $dataline) {
        $checkvar = preg_match('/\[\'DB\.Charset\'\]/', $dataline, $matches);

        if (isset($matches[0]) && !empty($matches[0])) {
            $found = true;
            break;
        }
    }

    if ($found == false) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
    }
    return $data;
}

?>