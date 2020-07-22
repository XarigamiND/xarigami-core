<?php

function check_main_111()
{
    $data['check']['message'] = xarML('The checks for Xaraya version 1.1.1 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_111_01', //Check for updated priv modvar inherit deny
                        'check_111_02', //inpage admin menus

                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xaraya_111/checks/'.$check.'.php')) {
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