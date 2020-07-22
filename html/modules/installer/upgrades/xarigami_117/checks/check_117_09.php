<?php

function check_117_09()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Check for registered BlockGroup masks");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $secmasktable = $prefix . '_security_masks';
        $newmaskarray = array (
            array('EditBlockGroup', 'All','blocks','Blockgroup','All:All','ACCESS_EDIT'),
            array('ReadBlockGroup',  'All','blocks','Blockgroup','All:All','ACCESS_READ'),
            array('AddBlockGroup',  'All','blocks','Blockgroup','All:All','ACCESS_ADD'),
            array('DeleteBlockGroup','All','blocks','Blockgroup','All:All','ACCESS_DELETE'),
            array('AdminBlockGroup',  'All','blocks','Blockgroup','All:All','ACCESS_ADMIN'),
            );
        //now check each
        $module = 'blocks';
        foreach ($newmaskarray as $newmasks=>$mask) {
            $query = "SELECT xar_name, xar_module
                      FROM   $secmasktable
                      WHERE xar_name = ? and xar_module =?";
                      $bindvars = array($mask[0],$module);
                       $result = $dbconn->Execute($query,$bindvars);

            if ($result->EOF) {
                $data['success'] = true;
                $data['reply'] = xarML("Not done!");
                $data['test'] =$data['test'] && false;
            }
        }

    }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}
?>