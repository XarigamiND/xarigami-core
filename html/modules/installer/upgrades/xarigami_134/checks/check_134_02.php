<?php
// This checks to see if there is a styles cache directory
function check_134_02()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for style cache directory ");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $styledir = sys::web() . 'var/cache/styles';
    $found= is_dir($styledir);

    if ($found === false) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
    }
    return $data;
}

?>