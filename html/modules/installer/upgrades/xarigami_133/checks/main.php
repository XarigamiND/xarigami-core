<?php

function check_main_133()
{
    $data['check']['message'] = xarML('The checks for Xarigami version 1.3.3 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_133_01', //Check theme table has config column
                        'check_133_02'  //Check for index on theme table
                    );

    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_133/checks/'.$check.'.php')) {
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
           // break;
        }
    }
    return $data;
}
?>