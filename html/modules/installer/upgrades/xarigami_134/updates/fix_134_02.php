<?php
// This checks to see if there is a styles cache directory
function fix_134_02()
{
    // Define parameters
    // Define the task and result
    //This is a long shot - we don't know what's in their current config.system.php file or write state
    $data['success'] = true;
    $data['task'] = xarML("Trying to create a style cache file in your var/cache directory");
    $data['reply'] = xarML("Done!");
    $cachedirpath= sys::web() . 'var/cache/styles';
    $found= is_dir($cachedirpath);

    if ($found == FALSE) { //we need to make the directory
        try{
            mkdir($cachedirpath , 0777);
           } catch (Exception $e) {
              $data['success'] = false;
              $data['reply'] = xarML("Failed!");
            }
    }
    return $data;
}

?>