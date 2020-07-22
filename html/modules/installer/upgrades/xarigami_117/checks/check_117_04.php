<?php

function check_117_04()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking Role Role and Role Relation instances");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $sitePrefix = xarDB::$prefix;
        $secinstancetable=$sitePrefix . '_security_instances';
        //try get instance we know should be updated but isn't
        //test to see if uid and uname is in the query ...

        $query = "SELECT xar_module, xar_component, xar_query
                  FROM $secinstancetable
                  WHERE xar_module = ? and xar_component =?";
                  $bindvars = array('roles','Roles');
                   $result = $dbconn->Execute($query,$bindvars);

        if ($result->EOF) { //missing .. we need to fix that
            $data['success'] = true;
            $data['reply'] = xarML("Not done!");
            $data['test'] =$data['test'] && false;
        }
        for (; !$result->EOF; $result->MoveNext()) {
               list($module,$component,$query) = $result->fields;
                $instance[] = array('module'=>$module,
                                    'component'=>$component,
                                    'query'=>$query);
            }
            $result->Close();
        $testquery = current($instance);
        $oldinstance = str_replace('xar_name','xar_uname',$testquery['query'],$count);
        if (($count)>0) {//we have a match, it's old
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