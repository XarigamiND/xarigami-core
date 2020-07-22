<?php
//
function fix_112_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Load the Timesince template tag.");
    $data['reply'] = xarML("Done!");
     $dbconn = xarDB::$dbconn;
    try {
         // Ensure base timesince tag handler is added
         xarTplUnregisterTag('base-timesince');
         xarTplRegisterTag('base', 'base-timesince', array(),'base_userapi_handletimesincetag');

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>