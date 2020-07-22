<?php

function fix_120_03()
{
    // Define parameters
    $themetable = xarDB::$prefix . '_themes';
    $themestates = xarDB::$prefix . '_theme_states';
    $dbconn = xarDB::$dbconn;

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Adding installer theme to the theme table and activating");
    $data['reply'] = xarML("Done!");
    try {
         $seqId = $dbconn->GenId($themetable);
         $query = "INSERT INTO $themetable
                    (xar_id, xar_name, xar_regid, xar_directory, xar_mode, xar_version, xar_class, xar_state)
                    VALUES (?, 'installtheme', 996, 'installtheme', '1','3.0.0',1, 3)";
              $result = $dbconn->Execute($query,array($seqId));

    } catch (Exception $e) {
        $data['success'] = false;
       $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>