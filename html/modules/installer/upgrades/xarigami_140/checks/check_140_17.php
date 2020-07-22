<?php
// Check label for correct menu block format
function check_140_17()
{
   // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking format of menu block config");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $blocktypes = $prefix.'_block_types';
        $blockinstances = $prefix.'_block_instances';
        //get base menu type
         $query = "SELECT xar_id, xar_type, xar_module
                    FROM $blocktypes
                   WHERE xar_type = 'menu' and xar_module = 'base'";
        $result = $dbconn->Execute($query);

        if (!$result) {
             $data['success'] = false;
             $data['reply'] = xarML("Test problem");
             $data['test'] =$data['test'] && false;
             return $data ;
        }
        list ($typeid, $blocktype, $module) = $result->fields;


        //get all base menu blocks
         $query = "SELECT xar_id, xar_type_id, xar_name, xar_title, xar_content
                    FROM $blockinstances
                   WHERE xar_type_id = $typeid";

        $result = $dbconn->Execute($query);

        for (; !$result->EOF; $result->MoveNext()) {

            list ($blockid,$blocktypeid, $blockname, $blocktitle, $blockcontent)  = $result->fields;
            //check if it is has the correct content
            try {
                $check = @unserialize($blockcontent);
            } catch (Exception $e) {
                //do nothing
            }
            if (is_array($check)) {
                if (isset($check['displaymodules']) || !array_key_exists('allmods',$check) ) {
                    //there is a problem and upgraded required - exit now
                     $data['success'] = true;
                    $data['reply'] = xarML("Not done");
                    $data['test'] =$data['test'] && false;
                    break ;
                }

            }
        }

   } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
    return $data;
}


?>