<?php

function check_121_03()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for old xaraya .xt extensions in mail templates");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $tagtable =  $prefix . '_template_tags';
        $tagarray = array('base-js-plugin','base-js-event','base-js-framework','base-js-plugin');
        //check for existence of .xt files in /messaging dir
        $varpath = sys::varpath();
        $regext = '#\.xt$#';
        $msgdir =$varpath . '/messaging';
        $xtfiles     = xarInstallAPIFunc('browse_files',
                            array('modName'=>'base',
                                 'modType'=>'user',
                                 'basedir'=>$msgdir,
                                 'match-re'=>$regext,
                                 'strip_re' => $regext,
                                 'levels' => 3,
                                 'retpath' => 'rel'));
        if (!empty($xtfiles) && is_array($xtfiles)) {
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