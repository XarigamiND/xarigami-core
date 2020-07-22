<?php

function fix_117_02()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding Roles Group Instance");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {
        $sitePrefix = xarDB::$prefix;
        $secinstancetable = $sitePrefix . '_security_instances';

        $rolestable = $sitePrefix . '_roles';
        $rolememberstable = $sitePrefix . '_rolesmembers';

        $module = 'roles';
        $component = 'Group';
        $header = 'Groups';
        $limit = 20;
        $query = "SELECT DISTINCT xar_uid, xar_name FROM $rolestable";
        $propagate = 0;
        $instancetable2 = $rolememberstable;
        $instancechildid = 'xar_uid';
        $instanceparentid = 'xar_parentid';
        $description = 'User and Group Instances of the roles module, including multilevel nesting';

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