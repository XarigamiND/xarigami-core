<?php

function fix_117_01()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding Role Group masks");
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
        $module = 'roles';

        $newmaskarray = array (
            array('ViewGroupRoles', 'All','roles','Group','All','ACCESS_OVERVIEW',xarML('View all users in a group')),
            array('ReadGroupRoles',  'All','roles','Group','All','ACCESS_READ',xarML('Read all profile information on all users in a group')),
            array('SubmitGroupRoles',  'All','roles','Group','All','ACCESS_COMMENT',xarML('Submit a user to a group')),
            array('ModerateGroupRoles','All','roles','Group','All','ACCESS_MODERATE',xarML('Add or Remove users in a group, edit these users')),
            array('EditGroupRoles',  'All','roles','Group','All','ACCESS_EDIT',xarML('Edit users in a group')),
            array('AddGroupRoles',   'All','roles','Group','All','ACCESS_ADD',xarML('Add level access')),
            array('DeleteGroupRoles','All','roles','Group','All','ACCESS_DELETE',xarML('Delete level access for users in a group')),
            array('AdminGroupRoles', 'All','roles','Group','All','ACCESS_ADMIN',xarML('Admin level access for a group')),
            array('ModerateRole',   'All','roles','Roles','All','ACCESS_MODERATE',''),
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
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>