<?php

function check_main_121()
{
    $data['check']['message'] = xarML('The checks for Xaraya version 1.2.1 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_121_01', //Check for existence of Installer theme
                        'check_121_02', //check for old tags in the system
                        'check_121_03', //check for bad extensions (.xt) in mail templates
                        'check_121_04', //check for menu link modvars
                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xaraya_121/checks/'.$check.'.php')) {
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