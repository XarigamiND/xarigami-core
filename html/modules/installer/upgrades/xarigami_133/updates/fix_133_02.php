<?php

function fix_133_02()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding index on theme vars table");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        xarDBLoadTableMaintenanceAPI();
        $prefix = xarDB::$prefix;
        $themevarstable = $prefix.'_theme_vars';

        $index = array('name'   =>  'i_'.$prefix.'_theme_vars_name_themename',
                       'fields' => array('xar_name', 'xar_themeName'),
                       'unique' => true);

        $query = xarDBCreateIndex($themevarstable, $index);
        $result = $dbconn->Execute($query);

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>