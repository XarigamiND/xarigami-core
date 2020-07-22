<?php
//
function fix_113_01()
{
    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("Fixing disallwed emails and moving to roles.");
    $data['reply'] = xarML("Done!");
     $dbconn = xarDB::$dbconn;
    try {

        $existingvar = xarUpgradeGetModVar('registration','disallowedemails');
        $existingregdisallowed = isset($existingvar) ? unserialize($existingvar): '';
        //but what if this is an old install and the roles equivalent is defined and not empty?
        $rolesisallowedvar =  xarUpgradeGetModVar('roles','disallowedemails');
        $existingrolesdisallowed = isset($rolesisallowedvar) ? unserialize($rolesisallowedvar): '';
        //Always take the registraiton var as it will be most recent if it exists and is not empty
        if (!empty($existingdisallowed)) {
           $emails = $existingdisallowed;
        } elseif (!empty($existingrolesdisallowed)) {
           $emails = $existingrolesdisallowed;
        }else {
            $emails = "none@none.com\npresident@whitehouse.gov";
        }
        $disallowedemails = serialize($emails);

        xarUpgradeSetModVar('roles', 'disallowedemails', $disallowedemails);
        //remove the old one
         xarUpgradeDelModVar('registration', 'disallowedemails');
    } catch (Exception $e) {

        $data['success'] = false;
        $data['reply'] = xarML("Failed!");
    }
    return $data;
}
?>