<?php

function fix_133_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding config column to theme table");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $themevarstable = $prefix.'_theme_vars';

        //add a column to the table
         xarDBLoadTableMaintenanceAPI();
        //here we go - we can't just 'alter' the table for cross db type compatibility

        //create temp table column
        $query = xarDBAlterTable( $themevarstable,
                              array('command' => 'add',
                                    'field'   => 'xar_config',
                                    'type'    => 'text'));
        $result = $dbconn->Execute($query);
        $result->close();
   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }
    return $data;
}

?>