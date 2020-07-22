<?php
//Return test and success True if tested OK and already updated
//test can fail, or be successful and have result false or true
function check_141_01()
{
     // Define parameters
     $prefix = xarDB::getSiteTablePrefix();
    $themetable = $prefix . '_themes';
    $dbconn = xarDB::getConn();
    $themename = 'default';
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking Default Theme is in database and active"); //in table and active
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $rowcount = 0;
    try {
       $query = "SELECT xar_id, xar_name FROM $themetable WHERE xar_name = ?";
        $result = $dbconn->Execute($query,array($themename ));

        if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Bad test!");
            $data['test'] =$data['test'] && false;
        }
        $rowcount = $result-> RowCount();
        if ($rowcount == 0) {
            $data['success'] = true;
            $data['reply'] = xarML("Not done");
            $data['test'] = $data['test'] && false;
        }
    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;

    }
    return $data;
}
?>