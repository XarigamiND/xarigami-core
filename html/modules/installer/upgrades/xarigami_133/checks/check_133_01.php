<?php

function check_133_01()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking theme table has config column");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
     $dbconn = xarDB::$dbconn;
   try {
        $prefix = xarDB::$prefix;
        $themevarstable = $prefix.'_theme_vars';

        $query = "SELECT *
                   FROM $themevarstable";

        $result = $dbconn->Execute($query);
        $fieldinfo = $result->FieldTypesArray();
        $found=0;
        foreach ($fieldinfo as $fieldtype) {
            if ($fieldtype->name == 'xar_config') {
                $found=1;
                break;
            }
        }

        if (!$found || $found ==0) {
           //we have a table to update
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Test Failed!");
        $data['test'] =$data['test'] && false;
    }

    return $data;
}

?>