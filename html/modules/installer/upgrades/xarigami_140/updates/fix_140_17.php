<?php
//Updating menu block

function fix_140_17()
{
    // Define parameters
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking format of menu block config");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $blocktypes = $prefix.'_block_types';
        $blockinstances = $prefix.'_block_instances';
        //get base menu type
         $query = "SELECT xar_id, xar_type, xar_module
                    FROM $blocktypes
                   WHERE xar_type = 'menu' and xar_module = 'base'";
        $result1 = $dbconn->Execute($query);

        if (!$result1) {
             $data['success'] = false;
             $data['reply'] = xarML("Test problem");
             $data['test'] =false;
             return $data ;
        }

        list ($typeid, $blocktype, $module) = $result1->fields;

        //get all base menu blocks
         $query = "SELECT xar_id, xar_type_id, xar_name, xar_title, xar_content
                    FROM $blockinstances
                   WHERE xar_type_id = $typeid";

        $result = $dbconn->Execute($query);

        if (!$result) {
            $data['success'] = false;
            $data['reply'] = xarML("Test problem");
            $data['test'] = false;
            return $data ;
        }

       while (!$result->EOF)
        {
            list ($blockid,$blocktypeid, $blockname, $blocktitle, $blockcontent)  = $result->fields;

            //check if it is has the correct content
            try {
                $check = @unserialize($blockcontent);
            } catch (Exception $e) {
                //do nothing
            }
            if (is_array($check)) {

                if (isset($check['displaymodules']) || !isset($check['allmods']) ) {

                    //there is a problem and upgrade required -
                    $check['allmods'] = $check['displaymodules'] == 'All'? true:false;
                    if (($check['displaymodules'] == 'List') && isset($check['modulelist'])){
                        $modlist = array();
                        $oldlist = explode(',',$check['modulelist']);
                        foreach ($oldlist as $modname) {
                            $modinfo =  xarUpgradeGetModInfo($modname,'module');

                            $modlist[] = $modinfo['regid'];
                        }
                        $check['modlist'] = $modlist;
                        unset($check['modulelist']);
                    }
                    unset($check['displaymodules']);
                }
            }

            $newcontent = serialize($check);

            //finally update the block instance
            $updatequery = "UPDATE $blockinstances
                      SET xar_content = ? WHERE xar_id = ?";
                $bindvars = array($newcontent,$blockid);
            try {
                $doupdate = $dbconn->Execute($updatequery,$bindvars);

            } catch (Exception$e) {
                $data['success'] = false;
                $data['reply'] = xarML("Update problem");
                return $data ;
            }
            $result->MoveNext();
        }


    } catch (Exception $e) {
        $data['success'] = false;
        $data['reply'] = xarML("Update Failed!");

    }
    return $data;
}
?>