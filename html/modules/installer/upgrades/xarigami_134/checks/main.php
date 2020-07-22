<?php

function check_main_134()
{
    $data['check']['message'] = xarML('The checks for Xarigami version 1.3.4 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_134_01', //Check charset in the config.system.php file
                        'check_134_02'  //Check for styles caching directory
                    );

    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_134/checks/'.$check.'.php')) {
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