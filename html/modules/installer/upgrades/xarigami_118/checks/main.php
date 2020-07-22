<?php

function check_main_118()
{
    $data['check']['message'] = xarML('The checks for version 1.1.8 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_118_01',   //Check required modvars are set
                        'check_118_02',   //Check for missed DD Submit masks
                        'check_118_03',   //Check edit and moderate levels in ACLS
                        'check_118_04',   //check all masks have been updated for edit/moderate levels
                        'check_118_05',   //CHeck for Moderate Base mask
                        'check_118_06',   //Checking for valid timezone
                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_118/checks/'.$check.'.php')) {
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