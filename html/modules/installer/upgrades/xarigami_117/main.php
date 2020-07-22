<?php
function main_117()
{
    $data['upgrade']['message'] = xarML('The upgrades for Xarigami Version 1.1.7 were successfully completed');
    $data['upgrade']['tasks'] = array();

    $upgrades = array(

                        'fix_117_01', // Check for registered Role Group masks
                        'fix_117_02', //Check Roles instances are updated
                        'fix_117_03', //check for UID instead of Name in Roles privs
                        'fix_117_04', //Check Role Role and Relation instances
                        'fix_117_05', //Check for role instances limit of min 50
                        'fix_117_06', //Check for roles group instances
                        'fix_117_07', //Check for Read Block Groups priv
                        'fix_117_08', //Check Admin lock privs
                        'fix_117_09', //update for BlockGroup Masks
                         'fix_117_10', //update for Realm Masks and Priv masks
                    );

    foreach ($upgrades as $upgrade) {
        //check and then do upgrade if required
        $checkfile = str_replace('fix','check',$upgrade);
         if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_117/checks/'.$checkfile . '.php')) {
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
            if (!xarUpgrader::loadUpgradeFile('upgrades/xarigami_117/updates/' . $upgrade . '.php')) {
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