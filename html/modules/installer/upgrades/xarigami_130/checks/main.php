<?php

function check_main_130()
{
    $data['check']['message'] = xarML('The checks for Xarigami version 1.3.0 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_130_01', //check for index on module vars table

                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_130/checks/'.$check.'.php')) {
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