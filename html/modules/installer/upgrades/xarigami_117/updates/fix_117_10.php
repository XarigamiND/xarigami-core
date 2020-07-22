<?php

function fix_117_10()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Update Realm and Privilege Masks");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $prefix = xarDB::$prefix;
        $secmasktable =  $prefix . '_security_masks';

         $levels = array(0=>'ACCESS_NONE',
                        100=>'ACCESS_OVERVIEW',
                        200=>'ACCESS_READ',
                        300=>'ACCESS_COMMENT',
                        400=>'ACCESS_EDIT',
                        500=>'ACCESS_MODERATE',
                        600=>'ACCESS_ADD',
                        700=>'ACCESS_DELETE',
                        800=>'ACCESS_ADMIN');
        $levelvalues =array_flip($levels);
        $module = 'privileges';

         $newmaskarray = array (
            array('ViewPrivileges','All','privileges','All','All','ACCESS_OVERVIEW',''),
            array('ReadPrivilege','All','privileges','All','All','ACCESS_READ',''),
            array('EditPrivilege','All','privileges','All','All','ACCESS_EDIT',''),
            array('AddPrivilege','All','privileges','All','All','ACCESS_ADD',''),
            array('DeletePrivilege','All','privileges','All','All','ACCESS_DELETE',''),
            array('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN',''),
            array('ViewRealm','All','privileges','Realm','All','ACCESS_OVERVIEW',''),
            array('ReadRealm','All','privileges','Realm','All','ACCESS_READ',''),
            array('EditRealm','All','privileges','Realm','All','ACCESS_EDIT',''),
            array('AddRealm','All','privileges','Realm','All','ACCESS_ADD',''),
            array('DeleteRealm','All','privileges','Realm','All','ACCESS_DELETE',''),
            array('AdminRealm','All','privileges','Realm','All','ACCESS_ADMIN',''),
            );

        foreach ($newmaskarray as $m) {
            try {
                //does it exist? double check
                $query = "SELECT xar_sid FROM $secmasktable
                         WHERE xar_module = ? AND xar_name = ?";
                        $result =  $dbconn->Execute($query, array($module, $m[0]));
                if (!$result->EOF) {
                    //update the mask
                    list($sid) = $result->fields;
                    $query = "UPDATE $secmasktable
                               SET xar_realm = ?, xar_component = ?, xar_instance = ?, xar_level = ?, xar_description = ?
                               WHERE xar_sid = ?";
                              $bindvars = array($m[1], $m[3], $m[4], $levelvalues[$m[5]], $m[6], $sid);
                } else {
                    //insert it
                    $newid = $dbconn->genID($secmasktable);
                     $query = "INSERT INTO  $secmasktable VALUES (?,?,?,?,?,?,?,?)";
                     $bindvars = array($newid ,$m[0], $m[1], $module, $m[3], $m[4], $levelvalues[$m[5]],$m[6]);
                }

                $result = $dbconn->Execute($query,$bindvars);

            } catch (Exception $e) {
                 $data['success'] = false;
                 $data['reply'] = xarML("Failed!");
            }
        }

    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!").$e->getMessage();;
    }
    return $data;
}
?>