<?php

function fix_117_04()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Update Role Role and Role Relation instances");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        //remove the roles Roles and roles Relations instances and redefine them
        $sitePrefix = xarDB::$prefix;
        $secinstancetable=$sitePrefix . '_security_instances';
        $rolestable=$sitePrefix . '_roles';
        $rolememberstable=$sitePrefix . '_rolesmembers';

        $query = "DELETE FROM $secinstancetable
                   WHERE xar_module = ? and (xar_component = ? or xar_component = ?)";
        $result = $dbconn->Execute($query, array('roles','Roles','Relation'));

        //now recreate them
        $module = 'roles';
        $query = "SELECT DISTINCT xar_uid, xar_uname FROM $rolestable";
        $limit = 20;
         $propagate = 0;
        $instancetable2 = $rolememberstable;
        $instancechildid = 'xar_uid';
        $instanceparentid = 'xar_parentid';

        //first the Roles instance
        $component = 'Roles';
        $header = 'Users and Groups';
        $description = 'User and Group Instances of the roles module, including multilevel nesting';

        $seqId = $dbconn->GenId($secinstancetable);
        $query = "INSERT INTO $secinstancetable
                    (xar_iid, xar_module, xar_component, xar_header, xar_query, xar_limit, xar_propagate, xar_instancetable2, xar_instancechildid, xar_instanceparentid, xar_description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              $result = $dbconn->Execute($query,array($seqId, $module, $component, $header, $query, $limit, $propagate, $instancetable2, $instancechildid, $instanceparentid, $description));

        //Now the Relations instance queries
        $component = 'Relation';
        $description = 'Instances of the roles module, including multilevel nesting';

        $header = 'Parent';
        $seqId = $dbconn->GenId($secinstancetable);
        $query = "INSERT INTO $secinstancetable
                    (xar_iid, xar_module, xar_component, xar_header, xar_query, xar_limit, xar_propagate, xar_instancetable2, xar_instancechildid, xar_instanceparentid, xar_description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              $result = $dbconn->Execute($query,array($seqId, $module, $component, $header, $query, $limit, $propagate, $instancetable2, $instancechildid, $instanceparentid, $description));

        $header = 'Child';
        $seqId = $dbconn->GenId($secinstancetable);
        $query = "INSERT INTO $secinstancetable
                    (xar_iid, xar_module, xar_component, xar_header, xar_query, xar_limit, xar_propagate, xar_instancetable2, xar_instancechildid, xar_instanceparentid, xar_description)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
              $result = $dbconn->Execute($query,array($seqId, $module, $component, $header, $query, $limit, $propagate, $instancetable2, $instancechildid, $instanceparentid, $description));


    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>