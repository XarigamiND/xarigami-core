<?php

function check_main_114()
{
    $data['check']['message'] = xarML('The checks for version 1.1.4 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                        'check_114_01', // Check Roles mask for component All
                        'check_114_02',  //check for convert module block priv, to block module privs!
                        'check_114_03',  // Check correct block privs  for this Cumulus version
                                        //note that privs/role defaults have changed since 1.1.4
                        'check_114_04',  //check for DenyBlocks priv
                    );
    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_114/checks/'.$check.'.php')) {
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