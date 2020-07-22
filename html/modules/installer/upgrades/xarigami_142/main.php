<?php
function main_142()
{
    $data['upgrade']['message'] = xarML('The upgrades for Xarigami Version 1.4.2 were successfully completed');
    $data['upgrade']['tasks'] = array();

    $upgrades = array(
                       //'fix_142_01', //session table field definition updates - session id and ip - handled by 1.5.x upgrades
                    );

    foreach ($upgrades as $upgrade) {
        //check and then do upgrade if required
        $checkfile = str_replace('fix','check',$upgrade);

         if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_142/checks/'.$checkfile . '.php')) {
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
            if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_142/updates/' . $upgrade . '.php')) {
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