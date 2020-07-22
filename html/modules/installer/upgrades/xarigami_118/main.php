<?php
function main_118()
{
    $data['upgrade']['message'] = xarML('The upgrades for Xarigami Version 1.1.8 were successfully completed');
    $data['upgrade']['tasks']= array();

    $upgrades = array(
                        'fix_118_01',   //Check required modvars are set
                        'fix_118_02',   //Check for missed DD Submit masks
                        'fix_118_03',   //Check edit and moderate levels in ACLS
                        'fix_118_04',   //check all masks have been updated for edit/moderate levels
                        'fix_118_05',   //Adding Moderate Base mask
                        'fix_118_06',   //Update timezone
                    );

    foreach ($upgrades as $upgrade) {
        //check and then do upgrade if required
        $checkfile = str_replace('fix','check',$upgrade);
         if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_118/checks/'.$checkfile . '.php')) {
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
            if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_118/updates/' . $upgrade . '.php')) {
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