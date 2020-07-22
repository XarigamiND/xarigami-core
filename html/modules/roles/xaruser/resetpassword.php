<?php
/**
 * Prepare to reset a password
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Check and prepare to reset password
 *
 */
function roles_user_resetpassword($args)
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;
    extract($args);
    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
        xarResponseRedirect(xarModURL('roles', 'user', 'account'));
       return true;
    }

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Reset Lost Password')));

    //check site is not locked for update
    $lockvars = unserialize(xarModGetVar('roles','lockdata'));
    if ($lockvars['locked'] ==1) {
        xarTplSetPageTemplateName('locked');
       return xarTplModule('authsystem','user','errors',array('errortype' => 'site_locked', 'var1'  => $lockvars['message']));
        
    }
    
    if (!xarVarFetch('phase','str:1:100',$phase,'valreset',XARVAR_NOT_REQUIRED)) return;
    // some variables related to advpassword reset
    $reqemail = xarModGetVar('roles', 'advresetemailreq')? xarModGetVar('roles', 'advresetemailreq'):0;
    $reqname = xarModGetVar('roles', 'advresetnamereq')?xarModGetVar('roles', 'advresetnamereq'):0; 
    switch(strtolower($phase)) {

         case 'valreset': //prepare form
         default:
        
            $authid = xarSecGenAuthKey('roles');
            $data   = xarTplModule('roles','user', 'resetrequest',
                             array('authid'     => $authid, 'reqemail'=>$reqemail,'reqname'=>$reqname));
                             
        break;
        
        case 'resetpw': //reset password using advanced method

            if (!xarVarFetch('uname', 'pre:trim:str:1:255', $uname, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email', 'pre:trim:str:1:255', $email, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('resetcode', 'pre:trim:str:1:32', $resetcode, '', XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;
            $data = array();
            $invalid=array();
            $tplvars  =array();
            $tplvars['readytoreset']  =false;

            if ($reqemail ==1 && $reqname ==1 && empty($email) && empty($uname) && empty($resetcode)) {
                $invalid['resetpass'] = xarML('You must enter a valid username, email address and reset code to proceed.');
            }
            
            $resetexpiry = xarModGetVar('roles','resetexpiry'); //hours to expiry of password reset
            if (isset($resetexpiry) && $resetexpiry > 0) {
               $timetoreset = $resetexpiry * 60 * 60; //seconds
            } else {
               $timetoreset = 0;
            }


            //check for invalids
            $countInvalid = count($invalid);

            if ($countInvalid > 0) {
                        $authid = xarSecGenAuthKey('roles');
                        return xarTplModule('roles','user', 'resetrequest',
                                                array('authid'     => $authid,
                                                      'invalid'   => $invalid,
                                                      'reqemail'   =>$reqemail,
                                                      'reqname'    =>$reqname,
                                                      'phase'     => 'valreset'));
            }
            //now if we are here reset invalid
            //test for existing user
            $invalid=array();
            $userargs = array();
            $resetpwcode = md5($resetcode);
            $userstate= 3; //only active user
            //we use uname if it's available to increase probabilty of uniqueness
            //resetcode may not be unique .....although unlikely
            $userargs = array('resetcode' => $resetpwcode, 'userstate' =>$userstate);
            if (!empty($uname)) {
                $userargs['uname'] = $uname;
            }
            
            // we only want users that are active
            // check for user and grab uid if exists

            $user= xarMod::apiFunc('roles',  'user', 'getstrict', $userargs);

            if (is_array($user) && isset($user['uid']) && $user['uid'] != 0 && isset($user['state']) && ($user['state'] ==3)) {
                $uid = $user['uid'];
                $usercheck = xarMod::apiFunc('roles',  'user', 'get', array('uid'=>$uid));
                $uname = $usercheck['uname'];  
                $email = $usercheck['email'];               
            } else {
                $usercheck = 0;
            }
            $resetlink = '<a href="'.xarModURL('roles','user','lostpassword').'">'.xarML('request a password reset').'</a>';
            if (!empty($usercheck) && is_array($usercheck)) {
                if ($usercheck['valcode'] != $resetpwcode) {
                    $invalid['resetpass'] = xarML('You must enter a valid reset code to proceed. If the reset code has expired, you may have to #(1) again. ', $resetlink);
                } elseif ($reqemail ==1 && trim($email) =='') {
                    $invalid['resetpass'] = xarML('You must supply your registered email address as well as reset code to proceed.');
                } elseif ($reqname ==1 && trim($uname) =='') {
                    $invalid['resetpass'] = xarML('You must supply your username as well as reset code to proceed.');
                }
 
                $userrequesttime =0;
                $timenow = time();
                $timediff = 0;
                if ($resetexpiry > 0 ) { //we need to check expiry time
                    //has the reset code expired ?
                    $userrequesttime = xarModUserVars::get('roles','resetexpiry',$usercheck['uid']);
                    $timediff = ($timenow - $userrequesttime);
                    if (($timediff)> $timetoreset) {
                       $resetlink = '<a href="'.xarModURL('roles','user','lostpassword').'">'.xarML('request a password reset').'</a>';
                       $invalid['resetpass'] = xarML('Your password reset code has expired and cannot be used. You can still use your old password, or you can #(1) again. ', $resetlink);
                    }
                }
            } else {
                $invalid['resetpass'] = xarML('You must enter a valid reset code to proceed. If the reset code has expired, you may have to #(1) again. ', $resetlink);
            }

            $countInvalid= count($invalid);
 
            if ($countInvalid > 0) {
                        $authid = xarSecGenAuthKey('roles');
                        return xarTplModule('roles','user', 'resetrequest',
                                                  array('authid'     => $authid,
                                                      'invalid'   => $invalid,
                                                      'reqemail'   =>$reqemail,
                                                      'reqname'    =>$reqname,
                                                      'phase'     => 'valreset'));
            }

            //if we got to here the data is ok and we need to put in place a temp pass to login
            //let's do a new one
            $user['pass'] = xarMod::apiFunc('roles', 'user', 'makepass');
            if (empty($user['pass'])) {
                $msg = xarML('Problem generating temporary password');
                throw new BadParameterException(null,$msg);
            }
            //don't update with hooks at this stage
            xarCoreCache::setCached('Hooks.all','noupdate',1);
            //Update user password to their account
            // check for user and grab uid if exists
            if (!xarMod::apiFunc('roles','admin','update',$user)) {
                $msg = xarML('Problem updating user record. The password has not been reset.');
                throw new BadParameterException(null,$msg);
            }

           $res = xarMod::apiFunc(
                'authsystem','user','login',
                array('uname' => $uname, 'pass' => $user['pass'])
            );
            
            if (($res === null) || ($res==false)) {
               $invalid['resetpass'] = xarML('There was a problem reseting your password, please try again.');
               $authid = xarSecGenAuthKey('roles');
                        return xarTplModule('roles','user', 'resetrequest',
                                                array('authid'     => $authid,
                                                      'invalid'   => $invalid,
                                                      'reqemail'   =>$reqemail,
                                                      'reqname'    =>$reqname,
                                                      'phase'     => 'valreset'));
            } 

            //must have logged in ok
            if (xarUserIsLoggedIn()) {
                   $userid = xarUserGetVar('uid');
                   if ($userid != $user['uid']) {
                    $invalid['resetpass'] = xarML('Insufficient privileges to update that account.');
                    xarUserLogOut();
                    $authid = xarSecGenAuthKey('roles');
                    return xarTplModule('roles','user', 'resetrequest',
                                                array('authid'     => $authid,
                                                      'invalid'   => $invalid,
                                                      'reqemail'   =>$reqemail,
                                                      'reqname'    =>$reqname,
                                                      'phase'     => 'valreset'));
                }
            }

            $thislastlogin =xarModUserVars::get('roles','userlastlogin');
            if (!empty($thislastlogin)) {
                //move this to a session var for this user
                xarSession::setVar('roles_firstlogin',FALSE);
                xarSession::setVar('roles_thislastlogin',$thislastlogin);
            } else {
                xarSession::setVar('roles_firstlogin',TRUE);
            }
            xarModSetUserVar('roles','userlastlogin',time()); //this is what everyone else will see
            $passwordredirect =xarModURL('roles','user','account',array('moduleload'=>'roles','readytoreset'=>true));
            
            $tplvars['resetlink'] =  $passwordredirect;
            $tplvars['readytoreset'] = true;
            xarResponseRedirect($passwordredirect);
           
        break;
    }
    return $data;
}
?>