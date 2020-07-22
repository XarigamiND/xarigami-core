<?php
 //Check required modvars are set
function check_118_01()
{

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for themes modvar 'selpreview'");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
   try {
       $value = xarUpgradeGetModVar('themes','selpreview');
       if (!$value || $value ===NULL) {
           $data['success'] = true;
           $data['reply'] = xarML("Tested OK!");
           $data['test'] =$data['test'] && false;
       }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>