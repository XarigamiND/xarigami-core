<?php

function fix_150_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Dropping module and theme state table and adding state column to those tables.");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDBGetConn();
   try {
        $prefix = xarDBGetSiteTablePrefix();
        $modulesTable = $prefix.'_modules';
        $moduleStatesTable = $prefix.'_module_states';
        $themesTable = $prefix.'_themes';
        $themeStatesTable = $prefix.'_theme_states';
        //check for modules states table
        $dropmod= FALSE;
        $droptheme = FALSE;
        xarDBLoadTableMaintenanceAPI();

        $query = "SELECT * FROM $moduleStatesTable";

        try {
            $result = $dbconn->Execute($query);
            if ($result)  $dropmod= TRUE;
        } catch (Exception $e) {
            //table doesn't exist
            //no need for anything here
        }
        //test for themes state table
        $query2 = "SELECT * FROM $themeStatesTable";
        try {
            $result = $dbconn->Execute($query2);
            if ($result)  $droptheme=  TRUE;
        } catch (Exception $e) {
            //table doesn't exist
            //no need for anything here
        }

        if ($dropmod == TRUE) { //we can only add a state column if the state table still exists otherwise we don't know the values to copy
            //check we have a modules state column
            $sql = "SELECT xar_state FROM $modulesTable";
            try {
                $result = $dbconn->Execute($sql);
            } catch (Exception $e) {
                //we need to add the module state column
                 //create new table column
                $query = xarDBAlterTable($modulesTable,
                              array('command' => 'add',
                                    'field'   => 'xar_state',
                                    'type'    => 'integer',
                                    'default' => '0'));
                $result = $dbconn->Execute($query);
                //copy data from old to new column
                $query2 = "SELECT xar_id, xar_state
                             FROM $moduleStatesTable"; //get all the state values
                $stateresult = $dbconn->Execute($query2);

                for (; ! $stateresult->EOF;  $stateresult->MoveNext()) {
                  list($modid, $modstate) = $stateresult->fields;
                  // Covert the first field data
                     //Copy to temp fields
                  $query3 = "UPDATE $modulesTable
                              SET xar_state= '".$modstate."'
                             WHERE xar_id   = '".$modid."'";
                    $doit = $dbconn->Execute($query3);
                }

            }
            //finally drop the module state table
            $query4 = xarDBDropTable($moduleStatesTable);
            $result = $dbconn->Execute($query4);
        }

        if ($droptheme == TRUE) { //we can only add a state column if the state table still exists otherwise we don't know the values to copy
            //check we have a themes state column
            $sql2 = "SELECT xar_state FROM $themesTable";
            try {
                $result = $dbconn->Execute($sql2);
            } catch (Exception $e) {
                   //create new table column
                $query = xarDBAlterTable($themesTable,
                              array('command' => 'add',
                                    'field'   => 'xar_state',
                                    'type'    => 'integer',
                                    'default' => '0'));
                $result = $dbconn->Execute($query);
                //copy data from old to new column
                $query5 = "SELECT xar_regid, xar_state
                             FROM $themeStatesTable"; //get all the state values
                 $themeresult = $dbconn->Execute($query5);

                for (; !$themeresult->EOF;  $themeresult->MoveNext()) {
                  list($regid, $themestate) =  $themeresult->fields;
                  // Covert the first field data
                     //Copy to temp fields
                  $query6 = "UPDATE $themesTable
                              SET xar_state= '".$themestate."'
                             WHERE xar_regid  = '".$regid."'";
                    $result = $dbconn->Execute($query6);

                }

            }
            //finally drop the theme state table
            $query7= xarDBDropTable($themeStatesTable);
            $result = $dbconn->Execute($query7);
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }
    return $data;
}
?>