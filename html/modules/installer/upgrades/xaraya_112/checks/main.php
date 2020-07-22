<?php

function check_main_112()
{
    $data['check']['message'] = xarML('The checks for Xaraya version 1.1.2 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_112_01', //Timesince tag is registered?
                        'check_112_02', //User time zone var
                         'check_112_03', //User sendmail set to false

                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xaraya_112/checks/'.$check.'.php')) {
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