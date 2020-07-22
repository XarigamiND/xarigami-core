<?php
//check for new DD modvars
function check_118_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking for missed DD Submit masks");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';

        //check SubmitDynamicDataItem mask exists
        $name = 'SubmitDynamicDataItem';
        $module = 'dynamicdata';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
        }

        $name = 'SubmitDynamicDataField';
        $module = 'dynamicdata';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
        }

    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>