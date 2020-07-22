<?php

function fix_117_05()
{
    // Define parameters

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding Roles Mail instance");
    $data['reply'] = xarML("Done!");
    $dbconn = xarDB::$dbconn;
    try {

        $sitePrefix = xarDB::$prefix;
        $secinstancetable=$sitePrefix . '_security_instances';
        $rolestable=$sitePrefix . '_roles';
        $rolememberstable=$sitePrefix . '_rolesmembers';
        //delete this first just in case so we don't end up with error
        $query = "DELETE FROM $secinstancetable
                   WHERE xar_module = ? and xar_component = ? ";
        $result = $dbconn->Execute($query, array('roles','Mail'));
        //now create it as we want it
        $module = 'roles';
        $query = "SELECT DISTINCT xar_uid, xar_name FROM $rolestable WHERE xar_type =1";
        $limit = 60;
        $propagate = 0;
        $instancetable2 = $rolememberstable;
        $instancechildid = 'xar_uid';
        $instanceparentid = 'xar_parentid';

        //first the Roles instance
        $component = 'Mail';
        $header = 'Mail Group';
        $description = 'Group Mail instance of the roles module';

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