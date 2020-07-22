<?php

function fix_130_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding modid/name index to module vars table");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
       xarDBLoadTableMaintenanceAPI();
        $prefix = xarDB::$prefix;
        $modvarstable = $prefix.'_module_vars';

        $index = array('name'   =>  'i_'.$prefix.'_module_vars_name_modid',
                       'fields' => array('xar_name', 'xar_modid'),
                       'unique' => true);

        $query = xarDBCreateIndex($modvarstable, $index);
        $result = $dbconn->Execute($query);

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>