<?php
//check for new DD modvars
function check_120_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking DD moderate masks exist");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';
        $module = 'dynamicdata';

        $name = 'ModerateDynamicData';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }
        $name = 'ModerateDynamicDataItem';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }
       $name = 'ModerateDynamicDataField';
        $query = "SELECT xar_name, xar_module
                  FROM   $secmasktable
                  WHERE xar_name = ? and xar_module =?";
                  $bindvars = array($name,$module);
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] =$data['test'] && false;
        }
    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =false;
    }
    return $data;
}
?>