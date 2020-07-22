<?php
function main_140()
{
    $data['upgrade']['message'] = xarML('The upgrades for Xarigami Version 1.4.0 were successfully completed');
    $data['upgrade']['tasks'] = array();

    $upgrades = array(
                        'fix_140_01',   //Prop def validation field update
                        'fix_140_02',   //update textarea property validations
                        'fix_140_03',   //update numberbox property validations
                        'fix_140_04',   //update Email property validations
                        'fix_140_05',   //update URL and URL Title property validations
                        'fix_140_06',   //update URLIcon property validations
                        'fix_140_07',   //update Float box property validations
                        'fix_140_08',   //update Checkbox property validations
                        'fix_140_09',   //update Calendar property validations
                        'fix_140_10',   //update Select property validations
                        'fix_140_11',   //update file and image lists
                        'fix_140_12',   //roles user listing
                        'fix_140_13',   //group user listing
                        'fix_140_14',   //File upload
                        'fix_140_15',  //update textbox property validations
                        'fix_140_16',   //update image property
                        'fix_140_17',   //update menu block variables
                        'fix_140_18',   //object configuration formats
                        'fix_140_19',  //object ref property validations
                        'fix_140_20',   //Upload property formats
                        'fix_140_21',   //Debug group
                        'fix_140_22',   //Update object configuration
                    );

    foreach ($upgrades as $upgrade) {
        //check and then do upgrade if required
        $checkfile = str_replace('fix','check',$upgrade);

         if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_140/checks/'.$checkfile . '.php')) {
            $data['check']['errormessage'] = xarUpgrader::$errormessage;
            return $data;
        }
        $checkresult =  $checkfile();
        if ($checkresult['test'] == 1 && $checkresult['success'] == 1) {
            $data['upgrade']['tasks'][] = array(
                                'reply'         => $checkresult['reply'],
                                'description'   => $checkresult['task'],
                                'reference'     => $checkfile,
                                'success'       => $checkresult['success'],
                                'test'          => $checkresult['test']);
        } else { //do the upgrade
            if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_140/updates/' . $upgrade . '.php')) {
                $data['upgrade']['errormessage'] = xarUpgrader::$errormessage;
                return $data;
            }
            $result = $upgrade();
            $data['upgrade']['tasks'][] = array(
                                'reply' => $result['reply'],
                                'description' => $result['task'],
                                'reference' => $upgrade,
                                'success' => $result['success'],
                                );
            if (!$result['success']) {
                $data['upgrade']['errormessage'] = xarML('Some parts of the upgrade failed. Check the reference(s) above to determine the cause.');
            }
        }
    }
    return $data;
}
?>