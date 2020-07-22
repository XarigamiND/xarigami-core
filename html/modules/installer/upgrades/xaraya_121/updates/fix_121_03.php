<?php

function fix_121_03()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Updating mail templates with correct .xd extension");
    $data['reply'] = xarML("Done!");
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
        if (!empty($xtfiles)&& is_array($xtfiles)) {
           //we need to rename them
           foreach($xtfiles as $xtfile) {
                $templatepath = $msgdir . '/'.$xtfile;
                if (file_exists($templatepath . '.xt')) {
                    try {
                        rename($templatepath . '.xt', $templatepath . '.xd');
                    } catch (Exception $e) {
                        $data['success'] = false;
                        $data['reply'] = xarML("Failed!");
                    }
                }
           }
        }

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>