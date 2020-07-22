<?php

function check_114_02()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Checking module block privs are Block module privs");
    $data['reply'] = xarML("Tested OK!");
    $data['test'] =true;
    $dbconn = xarDB::$dbconn;
   // try {
        $prefix =  xarDB::$prefix;
        $privstable = $prefix . '_privileges';
        $blockstable = $prefix . '_blocks_instances';
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
                $privset[$pid] = array('pid'    => $pid,
                                    'name'      => $name,
                                    'module'    => $module,
                                    'component' => $component,
                                    'instance'  => $instance,
                                    'btype'     => isset($ibits[0])?$ibits[0]:'All',
                                    'btitle'    => isset($ibits[1])?$ibits[1]:'All',
                                    'bid'       => isset($ibits[2])?$ibits[2]:'All'
                                    );
        }
        //now we have a list of block privs
        //check do any have component Block and module != Block ...
        foreach ($privset as $priv) {
            if ($priv['component'] == 'Block' && $priv['module'] != 'Block') {
                //the site has not been update for Block privs
                $data['success'] = true;
                $data['reply'] = xarML("Not done!");
                $data['test'] =$data['test'] && false;
            break; //no need to go on
            }
        }
 /*   }  catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Bad test!");
        $data['test'] =$data['test'] && false;
    }
  */
    return $data;
}
?>