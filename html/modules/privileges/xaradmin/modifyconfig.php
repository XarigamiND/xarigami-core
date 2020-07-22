<?php
/**
 * Modify configuration of this module
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * modify configuration
 */
function privileges_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminPrivilege',0)) return xarResponseForbidden();
    if (!xarVarFetch('phase', 'str:1:100', $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('testergroup', 'int', $testergroup, xarModGetVar('privileges', 'testergroup'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tester', 'int', $tester, xarModGetVar('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('invalid', 'array', $invalid, array(), XARVAR_NOT_REQUIRED)) return;
    $data['showrealms'] = xarModGetVar('privileges', 'showrealms');
    $opmode = xarSystemVars::get(sys::CONFIG, 'Operation.Mode',true)?xarSystemVars::get(sys::CONFIG, 'Operation.Mode',true):'developer';
    $data['opmode'] = $opmode;
    switch (strtolower($phase)) {
        case 'modify':
        default:
            if (!isset($phase)) {
                xarSession::setVar('statusmsg', '');
            }
            $data['inheritdeny'] = xarModGetVar('privileges', 'inheritdeny');
            $data['authid'] = xarSecGenAuthKey();

            switch ($data['tab']) {
                case 'general':
                    $data['exceptionredirect'] = xarModGetVar('privileges', 'exceptionredirect');
                    if (empty($opmode) || $opmode =='developer') {
                         $data['uselastresort'] = xarModGetVar('privileges', 'lastresort') ? true: false;
                    } else {
                        $data['uselastresort'] = false;
                    }
                    break;
                case 'lastresort':
                    $data['invalid'] = $invalid;
                    //Check for existence of a last resort admin for feedback to user
                    $lastresort  = xarModGetVar('privileges', 'lastresort');
                    //only show it if there is a last resort set and we're not in demo mode
                    if (($lastresort) && strlen(trim($lastresort))>1) {
                      //could just be true, we want to know if the name is set
                      $islastresort=unserialize($lastresort);
                      if (isset($islastresort['name'])){
                         $data['lastresortname']=$islastresort['name'];
                      } else{
                          $data['lastresortname']='';
                      }
                      $data['lastresortname'] = isset($islastresort['name']) ? $islastresort['name'] : '';
                      $data['setby']  = isset($islastresort['setby']) ?xarUserGetVar('name',$islastresort['setby']) : xarML('unknown');
                      $data['seton']  = isset($islastresort['seton']) ?xarLocaleGetFormattedDate('medium',$islastresort['seton']).' '.xarLocaleGetFormattedTime('medium',$islastresort['seton']) : xarML('-- unknown --');
                    }
                    break;
                case 'realms':

                    $realmvalue = xarModGetVar('privileges', 'realmvalue');
                    if (strpos($realmvalue,'string:') === 0) {
                       $textvalue = substr($realmvalue,7);
                       $realmvalue = 'string';
                    } else {
                        $textvalue = '';
                    }

                    $data['realmvalue'] = $realmvalue;
                    $data['textvalue'] = $textvalue;
                    $data['realmcomparison'] =  xarModGetVar('privileges', 'realmcomparison');

                    $data['realmoptions'] = array(
                                                array('id' => 'none', 'name' => xarML('No Value')),
                                                array('id' => 'domain', 'name' => xarML('Current Domain')),
                                                array('id' => 'theme', 'name' => xarML('Current Theme')),
                                                array('id' => 'group', 'name' => xarML('Primary Parent Group')),
                                                array('id' => 'string', 'name' => xarML('Text Value'))
                                                );
                    $data['realmmatch'] = array(
                                                array('id' => 'exact',    'name'=> xarML('Exact Match')),
                                                array('id' => 'contains', 'name'=> xarML('Privilege Contains Mask Name'))
                                                  );
                break;

                case 'testing':
                     $testmask=trim(xarModGetVar('privileges', 'testmask'));
                     if (!isset($testmask) || empty($testmask)) {
                         $testmask='All';
                     }
                     $data['testmask']=$testmask;
                     $settestergroup=xarModGetVar('privileges','testergroup');
                     if (!isset($settestergroupp) || empty($settestergroup)) {
                         $settestergrouprole=xarFindRole('Administrators');
                         $settestergroup=$settestergrouprole->uid;
                     }
                     if (!isset($testergroup) || empty($testergroup)) {
                         $testergroup=$settestergroup;
                     }

                     $data['testergroup']=$testergroup;
                     $grouplist = array();
                     $groups=xarGetGroups();
                     foreach($groups as $k=>$v) {
                        $grouplist[$v['uid']]=$v['name'];
                     }
                     $data['grouplist'] = $grouplist;

                     $testusers=xarMod::apiFunc('roles','user','getUsers',array('uid'=>$testergroup));
                     $defaultadminuid=xarModGetVar('roles','admin');
                     $testers = array();
                     foreach ($testusers as $k=>$v) {
                            $testers[$v['uid']]= $v['uname'];
                     }
                     $data['testers']=$testers; //array

                     $settester=xarModGetVar('privileges','tester'); //uid
                     if (!isset($settester) || empty($settester)) {
                         $settester=$defaultadminuid; //bug 5832 set it to the default admin, cannot assume it is Administrator
                     }
                     if (!isset($tester) || empty($tester)) {
                         $tester=$settester;
                     }
                     $data['tester']=$tester;
                     $data['test'] = xarModGetVar('privileges', 'test');
                     $data['testdeny'] = xarModGetVar('privileges', 'testdeny');
                break;
            }
            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('inheritdeny', 'checkbox', $inheritdeny, true, XARVAR_NOT_REQUIRED)) return;
                    //xarModSetVar('privileges', 'inheritdeny', $inheritdeny);
                    //jojodee - let's make sure for now as some people may have this unknowingly set at false,
                    // due to the var not being set to true by default in post 1.1 merge
                    xarModSetVar('privileges', 'inheritdeny', true);
                    if (!xarVarFetch('lastresort', 'checkbox', $lastresort, false, XARVAR_NOT_REQUIRED)) return;
                    if (empty($opmode) || $opmode =='developer') {
                        xarModSetVar('privileges', 'lastresort', $lastresort);
                        if (!$lastresort) {
                            xarModDelVar('privileges', 'lastresort',$lastresort);
                        }
                    }
                    if (!xarVarFetch('exceptionredirect', 'checkbox', $data['exceptionredirect'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'exceptionredirect', $data['exceptionredirect']);
                     xarLogMessage('PRIVILEGES: Configuration for General settings was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                    $usermsg = xarML('General settings have been updated and saved.');
                    break;
                case 'realms':
                    if (!xarVarFetch('enablerealms', 'checkbox', $data['enablerealms'], false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'showrealms', $data['enablerealms']);
                    if (!xarVarFetch('realmvalue', 'str', $realmvalue, 'none', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('realmcomparison', 'str', $realmcomparison, 'exact', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('textvalue', 'str', $textvalue, '', XARVAR_NOT_REQUIRED)) return;
                    if ($realmvalue == 'string') {
                        $realmvalue = empty($textvalue) ? 'none' : 'string:' . $textvalue;
                    }
                    xarModSetVar('privileges', 'realmvalue', $realmvalue);
                    xarModSetVar('privileges', 'realmcomparison', $realmcomparison);
                    xarLogMessage('PRIVILEGES: Configuration settings for Realms were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                    $usermsg = xarML('Realms settings have been modified and successfully saved.');
                    break;
                case 'lastresort':
                    if (!xarVarFetch('lrname', 'str', $lrname, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('password', 'str', $password, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('password2', 'str', $password2, '', XARVAR_NOT_REQUIRED)) return;

                    $invalid = array();
                    $invalid['lrname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'username', 'var'=>$lrname));
                    $pass = '';
                    $invalid['password'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass1', 'var'=>$password2));
                    if (empty($invalid['password'])) {
                        $invalid['password2'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass2', 'var'=>array($password,$password2)));
                    }

                    $a = array_count_values($invalid); // $a[''] will be the count of null values
                    if (!isset($a[''])) $a['']='';
                    $countInvalid = count($invalid) - $a[''];

                    if ($countInvalid > 0 ) {
                    // if so, return to the previous template, don't send plain text pass
                        $values = array('lrname' => $lrname,
                                        'tab' =>'lastresort',
                                        'invalid' =>$invalid,
                                        'authid' => xarSecGenAuthKey()
                                        );

                        xarResponseRedirect(xarModURL('privileges','admin','modifyconfig',$values));
                    } elseif (empty($opmode) || $opmode =='developer') {
                        $existing = xarModGetVar('privileges','lastresort')?xarModGetVar('privileges','lastresort'):'';
                        $existing = @unserialize($existing);
                        $existingname = isset($existing['name']) ? true:false;
                        $secret = array(
                                    'name' => MD5($lrname),
                                    'password' => MD5($password),
                                    'setby' => xarUserGetVar('uid'),
                                    'seton' => time()
                                    );
                        xarModSetVar('privileges','lastresort',serialize($secret));
                        if (!$existing) {
                           $usermsg = xarML('Last Resort Administrator successfully created!');
                        } else {
                            $usermsg = xarML('Last Resort Administrator has been successfully changed!');
                        }
                    }
                    xarLogMessage('PRIVILEGES: Configuration settings Last Resort Admin were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                    break;
                case 'testing':
                    if (!xarVarFetch('tester', 'int', $data['tester'], xarModGetVar('privileges', 'tester'), XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'tester', $data['tester']);
                    if (!xarVarFetch('test', 'checkbox', $test, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'test', $test);
                    if (!xarVarFetch('testdeny', 'checkbox', $testdeny, false, XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'testdeny', $testdeny);
                    if (!xarVarFetch('testmask', 'str', $testmask, 'All', XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('privileges', 'testmask', $testmask);
                    xarModSetVar('privileges', 'testergroup', $testergroup);
                    xarLogMessage('PRIVILEGES: Configuration settings for Testing was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                    $usermsg = xarML('Testing settings have been successfully changed.');

                    break;
            }

            xarTplSetMessage($usermsg,'status');
            xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
            // Return
            return true;
            break;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    return $data;
}
?>