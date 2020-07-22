<?php

function fix_132_01()
{
    // Define parameters
    // Define the task and result
    //This is a long shot - we don't know what's in their current config.system.php file or write state
    $data['success'] = true;
    $data['task'] = xarML("Trying to update webDir,libDir,codeDir,siteDir to config.system.php");
    $data['reply'] = xarML("Done!");
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
    $fixinfo = '
/*
Minimal configuration for this file is to tell Xarigami where the required
files are located relative to the root directory, always assumed to be one directory up from the webDir.
*/
    ';
    $fixinfo2 = "
//for layout with core and library code outside of webroot
//\$systemConfiguration['webDir']   =  'html/'; //where your domain name is pointed to and index.php resides
//\$systemConfiguration['libDir']   =  'xarigami/'; // where lib directory will be  located
//\$systemConfiguration['codeDir']  =  'html/'; // where modules and themes directories are  located
//\$systemConfiguration['siteDir']  =  'sites/'; //where your site variable data is located

/* Specific site information in the config.system.php file which maybe in any of the following (in order):
    - \$webDir/var/config.system.php
    - \$protectedvardir/config.system.php (if using .key.php)
    - \$siteDir/thissite.com/var/config.system.php
*/";
    $fixarray = array(
        'webDir'=>"\$systemConfiguration['webDir']   =  'html/'; //where your domain name is pointed to and index.php resides",
        'libDir'=>"\$systemConfiguration['libDir']   =  'html/'; //where lib directory is located",
        'codeDir'=>"\$systemConfiguration['codeDir']  =  'html/'; //where modules and themes directories are  located",
        'siteDir'=>"\$systemConfiguration['siteDir']  =  'sites/'; //where your site variable data is located"
        );

    if (count($checkarray)< 4) {
        $configlines = '';
    //ok we'll try and add the lines
        foreach ($testvars as $var) {
            if (isset($checkarray[$var]) && $checkarrag[$var] ==1) {
                break; //it's already there
            } else {
                //collect lines
                $configlines .= $fixarray[$var]."\n";
            }
        }
        if (!empty($configlines)) { //try and write to config
            try {
                $configdata = join('', $testfile);
                $newdata = '<?php'."\n".$fixinfo."\n".$configlines."\n".$fixinfo2;
                //we need to insert this at a known place .. perhaps after the <?php
                $configdata = preg_replace('/\<\?php/',$newdata,$configdata);
                $fp = fopen ($configfile, 'wb');
                fwrite ($fp,$configdata);
                fclose($fp);
            } catch (Exception $e) {
                $data['success'] = false;
                $data['reply'] = xarML("Failed!");
            }
        }
    }
    return $data;
}

?>