<?php
//check for new DD modvars
function check_120_01()
{

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for new DD 'systemobjects' and 'itemsperpage' module var");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
   try {
       $value = xarUpgradeGetModVar('dynamicdata','systemobjects');
       if (!$value) {
            $data['success'] = true;
             $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
       }
       $value = xarUpgradeGetModVar('dynamicdata','itemsperpage');
       if (!$value) {
            $data['success'] = true;
             $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
       }
   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!").$e->getMessage();
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>