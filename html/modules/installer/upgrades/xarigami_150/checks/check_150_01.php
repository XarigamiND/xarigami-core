<?php

function check_150_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking state tables removed and state column added to themes/modules.");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDBGetConn();
    $updaterequired = FALSE;
    try {
        $prefix = xarDBGetSiteTablePrefix();
        $modulesTable = $prefix.'_modules';
        $moduleStatesTable = $prefix.'_module_states';
        $themesTable = $prefix.'_themes';
        $themeStatesTable = $prefix.'_theme_states';
        //check for modules states table

        $query = "SELECT * FROM $moduleStatesTable";
        try {
            $result = $dbconn->Execute($query);
            if ($result)  $updaterequired = TRUE;
        } catch (Exception $e) {
            //table doesn't exist
            //no need for anything here - at least we cannot do anything
        }
        //test for themes state
        $query2 = "SELECT * FROM $themeStatesTable";
        try {
            $result = $dbconn->Execute($query2);
            if ($result)  $updaterequired = TRUE;
        } catch (Exception $e) {
            //table doesn't exist
            //no need for anything here
        }

        //check we have a modules state column
        $sql1 = "SELECT xar_state FROM $modulesTable";
        try {
            $result = $dbconn->Execute($sql1);
        } catch (Exception $e) {
            $updaterequired = TRUE;
        }
        //check we have a themes state column
        $sql2 = "SELECT xar_state FROM $themesTable";
        try {
            $result = $dbconn->Execute($sql2);
        } catch (Exception $e) {
            $updaterequired = TRUE;
        }

        if ($updaterequired === TRUE) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Test Failed!").$e->getMessage();;
        $data['test'] =$data['test'] && false;
    }

    return $data;
}

?>