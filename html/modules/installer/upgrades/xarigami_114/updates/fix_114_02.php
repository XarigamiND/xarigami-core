<?php

function fix_114_02()
{
     // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Converting module block privileges to Block module privs");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix =  xarDB::$prefix;
        $privstable = $prefix . '_privileges';
        $blockstable = $prefix . '_block_instances';
        $blocktypestable = $prefix . '_block_types';

        //get all the privs with block instances
        $query = "SELECT xar_pid, xar_name, xar_module, xar_component, xar_instance
                     FROM $privstable
                     WHERE xar_component = 'Block' and xar_module != 'blocks'";
        $result = $dbconn->Execute($query);
        $privset = array();
        $ibits = array();
        for (; !$result->EOF; $result->MoveNext()) {
            list($pid,$name,$module,$component,$instance) = $result->fields;
               if ($instance != 'All') {
                   $ibits = explode(':',$instance);
                } else {
                    $ibits = 'All';
                }
                $privset[$pid] = array('pid'=>$pid,
                                    'name'=>$name,
                                    'module'=>$module,
                                    'component'=>$component,
                                    'instance'=>$instance,
                                    'btype' => isset($ibits[0])?$ibits[0]:'All',
                                    'btitle' =>  isset($ibits[1])?$ibits[1]:'All',
                                    'bid'   =>  isset($ibits[2])?$ibits[2]:'All'
                                    );
        }

        //now, if there is anything in the privset, change to a Block module priv
        foreach ($privset as $pid => $pinfo) {
            $newinstance = "All:All:All";
            if ($ibits != 'All') {
                if ($pinfo['btype'] != 'All') {
                    //get the block info we need ie Module, Block name, block Type
                    $query = "SELECT b.xar_id, b.xar_type_id, b.xar_name, t.xar_type, t.xar_module
                              FROM $blockstable  b
                              LEFT JOIN  $blocktypestable t
                              ON t.xar_id = b.xar_type_id
                              WHERE b.xar_type_id = ?";
                    $result = $dbconn->Execute($query,array($pinfo['btype']));
                    if(!$result->EOF) {
                        list($bid, $btypeid, $bname, $btype, $mod) = $result->fields;
                        //build instance field

                        $newinstance = "$module";
                        if (($ibits != 'All') && $pinfo['btitle'] != 'All') {
                            $newinstance .=":$btype";
                        } else {
                            $newinstance .=":All";
                        }
                        if (($ibits != 'All') && $pinfo['bid'] != 'All') {
                            $newinstance .=":$bname";
                        } else {
                            $newinstance .= ":All";
                        }
                     }
                } else {
                        $newinstance = "$module:All:All";
                }
            }
           //now update the privilege
            try {
                $query = "UPDATE $privstable
                           SET xar_module = ?, xar_instance = ?
                           WHERE xar_pid = ?";
                $result = $dbconn->Execute($query, array('blocks',$newinstance,$pid));
            } catch (Exception $e) {
                $data['success'] = false;
                $data['reply'] = xarML("Failed!").$e->getMessage();
            }
        }
        $result->close();
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();
    }
    return $data;
}
?>