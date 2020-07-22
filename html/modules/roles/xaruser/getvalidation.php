<?php
/**
 * Getvalidation validates a new user into the system
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * getvalidation validates a new user into the system
 * if their status is set to two.
 *
 * @param   uname users name
 * @param   valcode is the validation code sent to user on registration
 * @param   phase is the point in the function to return
 * @return  bool true if valcode matches valcode in user status table
 * @throws   exceptions raised valcode does not match
 * @TODO jojodee - validation process, duplication of functions and call to registration module needs to be rethought
 *         Rethink to provide cleaner separation between roles, authentication and registration
 */
function roles_user_getvalidation()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
       xarResponseRedirect(xarModURL('roles', 'user', 'account',
                                      array('uid' => xarUserGetVar('uid'))));
       return true;
    }

    if (!xarVarFetch('uname',  'str:1:100', $uname,   '',                XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('valcode','str:1:100', $valcode, '',                XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sent',   'int:0:2',   $sent,    0,                 XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase',  'str:1:100', $phase,   'startvalidation', XARVAR_NOT_REQUIRED)) return;

    xarTplSetPageTitle(xarML('Validate Your Account'));
     // Users can be newly registering, or existing users revalidating their accounts
    // Using this workaround to tell the difference until we can separate the processes further
    $newuser=false;
    //jojodee  - need to fix this properly and unentangle the processes
    if (!empty($uname)) {
        // check for user and grab uid if exists
        $status    = xarMod::apiFunc('roles', 'user', 'get', array('uname' => $uname));
        $lastlogin = xarModUserVars::get('roles','userlastlogin',$status['uid']);

        if (!isset($lastlogin) || empty($lastlogin)) {
            $newuser=TRUE;
        }
    }

    //Get default registration module info if any
    //jojo - there may be no registration on site - what then?
    //users can still need to revalidate their email
    $defaultregdata  = xarMod::apiFunc('roles','user','getdefaultregdata');
    if (($defaultregdata['defaultregmodactive'] == FALSE) && $newuser == TRUE) {
        //jojo - do we need it?
        $msg = xarML('There is no active registration module installed');
         $regmodule = '';
        //throw new ModuleNotFoundException($regmodule);
    } elseif ($defaultregdata['defaultregmodactive'] == TRUE)  {
        $regmodule       = $defaultregdata['defaultregmodname'];
         $newpending  = xarModGetVar($regmodule, 'explicitapproval');
    } else {
        $regmodule = ''; //just initialize it
    }
    //Get default authentication module info if any
    $defaultloginmodname = 'authsystem';
    $authmodule          = 'authsystem';

    //Set some general vars that we need in various options
    $userpending = xarModGetVar($authmodule, 'explicitapproval');
    $loginlink   = xarModURL($defaultloginmodname,'user','main');

    $tplvars  = array();
    $tplvars['loginlink'] = $loginlink;
    //we need to know whether pending refers to a new user, or one revalidating
    if ($newuser && isset($newpending)) {
        $tplvars['pending'] = $newpending;
    }else {
        $tplvars['pending'] = $userpending;
    }

    switch(strtolower($phase)) {

        case 'startvalidation':
        default:
            //values will be empty for vars for users entering through login
            $data = xarTplModule('roles','user', 'startvalidation',
                           array('phase'   => $phase,
                                 'uname'   => $uname,
                                 'sent'    => $sent,
                                 'valcode' => $valcode,
                                 'validatelabel' => xarML('Validate Your Account'),
                                 'resendlabel'   => xarML('Resend Validation Information')));
            break;

        case 'getvalidate':

            // Trick the system when a user has double validated.
            if (empty($status['valcode'])){
                $data = xarTplModule('roles', 'user', 'getvalidation', $tplvars);
                    return $data;
            }

            // Check Validation codes to ensure a match.
            if ($valcode != $status['valcode']) {
                $msg = xarML('The validation codes do not match');
                throw new DataNotFoundException(array(),$msg);
            }

            //Process users that need to be approved first and put in status PENDING
            //These include new users or existing users that require approval
            if ((isset($newpending) && $newpending == 1 || $userpending==1) && ($status['uid'] != xarModGetVar('roles','admin')))  {
                // Update the user status table to reflect a pending account.
                if (!xarMod::apiFunc('roles', 'user', 'updatestatus',
                                    array('uname' => $uname,
                                          'state' => ROLES_STATE_PENDING)));

                //Do Pending email notifications
                //Only for existing users. New users are handled below with all new user notifications

                if (!$newuser) { //we already know they are pending
                // Admin wants to be notified when accounts of existing users are set to pending
                    if (!xarMod::apiFunc( 'roles', 'admin', 'sendpendingemail',
                                  array('uid'   => $status['uid'],
                                        'uname' => $uname,
                                        'name'  => $status['name'],
                                        'email' => $status['email']))) {
                      $msg = xarML('Problem sending pending email');
                      throw new GeneralException(null, $msg);
                    }
                }

            } else {// Update the user status table to reflect a validated account.

                if (!xarMod::apiFunc('roles', 'user', 'updatestatus',
                              array('uname' => $uname,
                                   'state' => ROLES_STATE_ACTIVE))) return;

                //let's tell hooks we've updated a user
                $item['module'] = 'roles';
                $item['itemid'] = $status['uid'];
                $item['uid'] = $status['uid'];
                $item['status'] = ROLES_STATE_ACTIVE;
                $item['itemtype'] = 0;
                xarMod::callHooks('item', 'update', $status['uid'], $item);
            }

            //If we have registration of new user and the admin wants notificaton, let's send an email
            //and also one to the user if that is set in the registration module
            if ($newuser){
                //Set the last newly registered user
                xarModSetVar('roles', 'lastuser', $status['uid']);

                // first to the user
                //Send welcome email to the user
                //This could be templated specifically for a 'new' user now
                if (xarModGetVar($regmodule, 'sendwelcomeemail')) {
                xarLogMessage("EMAIL: sending welcome email for new user");
                    if (!xarMod::apiFunc('roles','admin','senduseremail',
                                    array('uid' => array($status['uid'] => '1'),
                                                         'mailtype'     => 'welcome'))) {

                        $msg = xarML('Problem sending welcome email');
                        throw new GeneralException(null, $msg);
                    }
                }
                if (xarModGetVar($regmodule, 'sendnotice')==1) {
                    //now to the admin
                    $terms= '';
                    if (xarModGetVar($regmodule, 'showterms') == 1) {
                    // User has agreed to the terms and conditions.
                        $terms = xarML('This user has agreed to the site terms and conditions.');
                    }

                    $status = xarMod::apiFunc('roles','user','get',array('uname' => $uname)); //check status as it may have changed

                    $emailargs =  array('adminname'    => xarModGetVar('mail', 'adminname'),
                                        'adminemail'   => xarModGetVar('registration', 'notifyemail'),
                                        'userdisplayname' => $status['name'],
                                        'username'     => $status['uname'],
                                        'useremail'    => $status['email'],
                                        'terms'        => $terms,
                                        'uid'          => $status['uid'],
                                        'userstatus'   => $status['state']
                                        );
                    if (!xarMod::apiFunc($regmodule, 'user', 'notifyadmin', $emailargs)) {
                       xarLogMessage("EMAIL: sending admin new user notification failed");
                        return; // TODO ...something here if the email is not sent..
                    }
                }
            //Else if this is an existing user who has just revalidated their email account,
            //let's send an email to admin to tell them if they have requested this.
            //Also send 'successful validation email' to the user revalidating their email
            } elseif  (xarModGetVar('roles', 'requirevalidation') && !$newuser) {
                //send successful validation email for 'existing' user - could be reworked using exising roles welcome template
                if (xarModGetVar('roles', 'sendwelcomeemail')) {
                    if (!xarMod::apiFunc('roles','admin','senduseremail',
                                    array('uid' => array($status['uid'] => '1'),
                                                         'mailtype'     => 'welcome'))) {

                        $msg = xarML('Problem sending welcome email');
                        throw new GeneralException(null, $msg);
                    }
                }
                //now email to admin if they requested it
                if (xarModGetVar('roles','askwelcomeemail')) {
                    $adminname  = xarModGetVar('mail', 'adminname');
                    $adminemail = xarModGetVar('mail', 'adminmail');
                    $message    = "".xarML('A user has re-validated their changed email address.  Here are the details')." \n\n";
                    $message   .= "".xarML('Username')." = $status[name]\n";
                    $message   .= "".xarML('Email Address')." = $status[email]";

                    $messagetitle = "".xarML('A user has updated information')."";

                    if (!xarMod::apiFunc('mail', 'admin', 'sendmail',
                                       array('info'    => $adminemail,
                                             'name'    => $adminname,
                                             'subject' => $messagetitle,
                                             'message' => $message))) return;
                }

            }

            //The user has validated their account and can be redirected to login
            $url = xarModUrl('roles', 'user', 'main');

            $time = '5';
            xarCoreCache::setCached('Meta.refresh','url', $url);
            xarCoreCache::setCached('Meta.refresh','time', $time);

            $data = xarTplModule('roles','user', 'getvalidation', $tplvars);

            break;

        case 'resend':
            // check for user and grab uid if exists
            $status = xarMod::apiFunc('roles', 'user', 'get', array('uname' => $uname));

            if (!xarMod::apiFunc('roles','admin','senduseremail',
                                array('uid'      => array($status['uid'] => '1'),
                                      'mailtype' => 'confirmation',
                                      'ip'       => xarML('Cannot resend IP'),
                                      'pass'     => xarML('Can Not Resend Password')))) {

                    $msg = xarML('Problem resending confirmation email');
                    throw new GeneralException(null, $msg);
                }

            $data = xarTplModule('roles','user', 'getvalidation', $tplvars);

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation',array('sent' => 1)));

        }
    return $data;
}
?>