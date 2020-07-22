<?php

function check_main_120()
{
    $data['check']['message'] = xarML('The checks for version 1.2.0 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_120_01', //new DD mod vars
                        'check_120_02', //new DD Moderate masks
                        'check_120_03', //has installtheme

                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_120/checks/'.$check.'.php')) {
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