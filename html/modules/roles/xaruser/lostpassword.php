<?php
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2008-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Sends a new password to the user if they have forgotten theirs.
 * Or and email to confirm resetting of a user password if advanced reset is chosen
 * (advanced passowrd reset is an admin setting in Roles)
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  jojodee  - added advanced password reset
 */
function roles_user_lostpassword()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
        xarResponseRedirect(xarModURL('roles', 'user', 'account'));
       return true;
    }

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Lost Password')));

    if (!xarVarFetch('phase','str:1:100',$phase,'request',XARVAR_NOT_REQUIRED)) return;

    //check to see if we are using advanced password reset
    $useadvancedreset = xarModGetVar('roles','advpasswordreset')?true:false;
    if ($useadvancedreset) {
      $maillabel = xarML('Request Password Reset');
    } else {
      $maillabel = xarML('E-Mail New Password');
    }
    switch(strtolower($phase)) {

        case 'request':
        default:
            //check site is not locked
            $lockvars = unserialize(xarModGetVar('roles','lockdata'));
            if ($lockvars['locked'] ==1) {
               xarTplSetPageTemplateName('locked');
                return xarTplModule('authsystem','user','errors',array('errortype' => 'site_locked', 'var1'  => $lockvars['message']));

            }
            $authid = xarSecGenAuthKey('roles');
            $data   = xarTplModule('roles','user', 'requestpw',
                             array('authid'     => $authid,
                                   'emaillabel' => $maillabel));

            break;

        case 'send':

            if (!xarVarFetch('uname', 'pre:trim:str:1:255', $uname, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email', 'pre:trim:str:1:255', $email, '', XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            $invalid = array();
            $resetlink = ''; //initialize for later use

            if ((empty($uname)) && (empty($email))) {
                $invalid['getpassword'] = xarML('You must enter either a valid username or email to proceed.');
                $msg = xarML('You must enter either your username or your email address to continue with password retrieval.');
                return xarTplModule('roles','user','errors',array('errortype' => 'baddata','var1'=>$msg));
            }

            //check for invalids
            $countInvalid = count($invalid);

            $userargs = array();
            //what should take precedence - even a config to force both  uname and email?
            $matchemail = xarModGetVar('roles','matchemailforpw') ? xarModGetVar('roles','matchemailforpw'):false;
            //let's continue check
            if ($countInvalid <= 0) { // we can check database now
                if ($matchemail) {
                    $userargs = array('uname'=>$uname,'email' => $email);
                    $invalid['getpassword'] =  xarML('That email address and username combination is not valid or registered on this site.');
                } elseif (!empty($uname) && (empty($email))) {
                    $userargs = array('uname'=>$uname);
                    $invalid['uname'] =  xarML('That username has an invalid format or is not registered on this site.');
                } elseif (!empty($email) && empty($uname)){
                    $userargs= array('email'=>$email);
                    $invalid['email'] =  xarML('That email has an invalid format or is not registered as active on this site.');
                } elseif (!empty($email) && !empty($uname)) { //just use the email
                    $userargs = array('uname'=>$uname,'email' => $email);
                    $invalid['getpassword'] =  xarML('Either the email address or username is not valid or registered on this site. You can try username or email address if you have forgotten the combination.');
                }
            }
               // we only want users that are active
               // check for user and grab uid if exists
                $user = xarMod::apiFunc('roles',  'user', 'get', $userargs);

                if (!empty($user)) {
                 switch($user['state']) {
                  case '2': //not validated
                     $validateurl ='<a href="'.xarModURL('roles','user','getvalidation').'">'.xarML('validation request').'</a>.';
                     $invalid['uname'] =xarML('This account is not active and  needs to be validated with the validation code previously sent.
                     If you need a new validation code please go to #(1)', $validateurl);
                     break;
                  case '4': //pending
                     $invalid['uname'] =xarML('This account is marked pending. Please contact the site admin for further information.');
                     break;
                  case '3': //active
                    //they are active we have what we want, so reset all these
                    $invalid =array();
                    break;
                  case '1':
                  default:
                   $invalid['uname'] = xarML('This is not an active account on this site.');
                   break;
                }
            }

            // Check for invalid content and return to get correct input
            $countInvalid = count($invalid);
            if ($countInvalid > 0) {
                        $authid = xarSecGenAuthKey('roles');
                        return xarTplModule('roles','user', 'requestpw',
                                 array('authid'     => $authid,
                                       'email'     => $email,
                                       'uname'     => $uname,
                                       'invalid'   => $invalid,
                                       'emaillabel' => $maillabel));
            }

            // We must have found a user if we got here


            //so make new password - this will be used as the reset code in advanced password reset method
            $user['pass'] = xarMod::apiFunc('roles', 'user', 'makepass');

            if (empty($user['pass'])) {
                throw new DataNotFoundException(array(),'Problem generating new password');
            }

            // We need to tell some hooks that we are coming from the lost password screen
            // and not the update the actual roles screen.  Right now, the keywords vanish
            // into thin air.  Bug 1960 and 3161
            xarCoreCache::setCached('Hooks.all','noupdate',1);

            $resetexpiry =xarModGetVar('roles','resetexpiry');
            $usetimereset = isset($resetexpiry)?$resetexpiry:0;
            if (TRUE == $useadvancedreset) {
               // we don't want to reset the password here until the user confirms
               // we need to save the generated reset code.
               // For now - instead of new columns or other table use xar_valcode validation code column: review
                $user['valcode'] = $user['pass'];

                if (!xarMod::apiFunc('roles','admin','updateval',$user)) {
                  $msg = xarML('Problem updating the user information');
                     throw new BadParameterException($msg);
                }
                $resetlink = xarModURL('roles','user','resetpassword',array('phase'=>'valreset'),false);

                if (isset($usetimereset) && $usetimereset>0){
                    //set a user var with stamp of request time
                    xarModSetUserVar('roles','resetexpiry',time(),$user['uid']);
                }

            } else { //use the original password reset method - here for backward compatibility

                //Update user password to their account
                // check for user and grab uid if exists
                if (!xarMod::apiFunc('roles','admin','update',$user)) {
                    $msg = xarML('Problem updating the user information');
                      throw new DataNotFoundException(array(),$msg);
                }

            } //end of password reset option
              // Send Passowrd Reminder/reset  Email
             if (!xarMod::apiFunc('roles', 'admin','senduseremail',
                          array('uid'  => array($user['uid'] => '1'),
                                                    'mailtype'   => 'reminder',
                                                    'pass'       => $user['pass'],
                                                    'resetlink'  => $resetlink,
                                                    'usetimereset' =>$usetimereset))) return;
            // Let user know that they have an email on the way.
            $data = xarTplModule('roles','user','requestpwconfirm');
          break;
    }
    return $data;
}
?>
