<?php

function check_main_117()
{
    $data['check']['message'] = xarML('The checks for version 1.1.7 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_117_01', // Check for registered Role Group masks
                        'check_117_02', //Check Roles instances are updated
                        'check_117_03', //check for UID instead of Name in Roles privs
                        'check_117_04', //Check Role Role and Relation instances
                        'check_117_05', //Check for role Mail instances and limit of 50 min
                        'check_117_06', //Check for roles group instances
                        'check_117_07', //Check for Read Block Groups priv
                        'check_117_08', //Check Admin lock privs
                        'check_117_09', //Check for BlockGroup Masks
                        'check_117_10', //Check for Realm and Priv masks
                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_117/checks/'.$check.'.php')) {
            $data['check']['errormessage'] = xarUpgrader::$errormessage;
            return $data;
        }
        $result = $check();
        $data['check']['tasks'][] = array(
                            'reply' => $result['reply'],
                            'description' => $result['task'],
                            'reference' => $check,
                            'success' => $result['success'],
                            'test'  => $result['test']

                            );
        if ($result['test'] == 0 || $result['success'] == 0) {
            $data['upgrade']['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
        }
    }
    return $data;
}
?>