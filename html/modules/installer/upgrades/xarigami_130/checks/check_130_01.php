<?php

function check_130_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for index on module vars table");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
   try {
        $prefix = xarDB::$prefix;
        $modvarstable = $prefix.'_module_vars';
        $indexnames = array('xar_modid','xar_name');
        $hasindex = $dbconn->MetaIndexes($modvarstable);
        //check to see if there is an index with both modid and name
        //assume not and we need to make one
        $data['success'] = true;
        $data['reply'] = xarML("Not Done");
        $data['test'] = false;
        if (!empty($hasindex)) { //there is no index
           //check to see if the index is on modid and name
            foreach ($hasindex as $name) {
                if (count($name['columns']) == 2) {
                    if (in_array($name['columns'][0],$indexnames) &&
                        in_array($name['columns'][1],$indexnames))  {
                        //we already have an index, don't need to make one
                        $data['success'] = true;
                        $data['reply'] = xarML("Tested OK!");
                        $data['test'] = true;
                    }
                 }
            }
        }
   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Test Failed!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>