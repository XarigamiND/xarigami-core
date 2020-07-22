<?php
function main_130()
{
    $data['upgrade']['message'] = xarML('The upgrades for Xarigami Version 1.3.0 were successfully completed');
    $data['upgrade']['tasks'] = array();

    $upgrades = array(
                        'fix_130_01',   //Add index on module vars table
                       // 'fix_130_02',   //Add new DD mod vars

                    );
    foreach ($upgrades as $upgrade) {
        //check and then do upgrade if required
        $checkfile = str_replace('fix','check',$upgrade);
         if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_130/checks/'.$checkfile . '.php')) {
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
            if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_130/updates/' . $upgrade . '.php')) {
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