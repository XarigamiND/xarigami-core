<?php

function check_main_140()
{
    $data['check']['message'] = xarML('The checks for Xarigami version 1.4.0 were successful');
    $data['check']['tasks'] = array();

    $checks = array(
                       'check_140_01',  //Prop def validation field update
                       'check_140_02', //Check for textarea validations in correct format
                       'check_140_03', //Check for numberbox validations in correct format
                       'check_140_04', //Check for Email validations in correct format
                       'check_140_05', //Check for URL validations in correct format
                       'check_140_06', //Check for URL Icon validations in correct format
                       'check_140_07', //Check for Float box validations in correct format
                       'check_140_08', //Check Checkbox validations in correct format
                       'check_140_09', //Check Calendar validations in correct format
                       'check_140_10', //Check Select/dropdown validations in correct format
                       'check_140_11', //Check  image and file list validations in correct format
                       'check_140_12', //Check  roles user validation
                       'check_140_13', //Check group user validation
                       'check_140_14',   //File upload
                       'check_140_15',  //Check for textbox validations in correct format
                       'check_140_16', //image property checks
                       'check_140_17', //menu block checks
                       'check_140_18', //object configuration format
                       'check_140_19', //objectref validation format
                       'check_140_20', //upload property validation format
                       'check_140_21', //debug group
                       'check_140_22' //object configuration
                      );


    foreach ($checks as $check) {
        if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_140/checks/'.$check.'.php')) {
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