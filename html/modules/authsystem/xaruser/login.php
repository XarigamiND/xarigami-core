<?php
/**
 * Handle the user supplied data for login information
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2013 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * log user in to system
 * Description of status
 * Status 0 = deleted user
 * Status 1 = inactive user
 * Status 2 = not validated user
 * Status 3 = actve user
 *
 * @param   uname users name
 * @param   pass user password
 * @param   rememberme session set to expire
 * @param   string $fieldcheck (optional)
 * @param   redirecturl page to return user if possible
 * @return  true if status is 3
 * @throws  exceptions raised if status is 0, 1, or 2
 */
function authsystem_user_login()
{
    global $xarUser_authenticationModules;

    if (!$_COOKIE) {
        return xarTplModule('authsystem','user','errors',array('errortype' => 'no_cookies'));
    }

    /* First, see if this user has been locked, so we dont need to do authentication at all.
     * The system will check to see if the number of configurable lockout tries for this session and configurable time
     * has been exceeded and if so disallow another attempt.
     */
    $unlockTime   = (int) xarSessionGetVar('authsystem.login.lockedout');
    $lockouttime  = xarModGetVar('authsystem','lockouttime')? xarModGetVar('authsystem','lockouttime') : 15;
    $lockouttries = xarModGetVar('authsystem','lockouttries') ? xarModGetVar('authsystem','lockouttries') : 3;
    $attempts = (int) xarSessionGetVar('authsystem.login.attempts');
    $useauthcheck = xarModGetVar('authsystem','useauthcheck');
    if ($useauthcheck == TRUE) {
        if (!xarSecConfirmAuthKey()) return;
    }
    if ((time() < $unlockTime) && (xarModGetVar('authsystem','uselockout')==true)) {
        return xarTplModule('authsystem','user','errors',array('errortype' => 'locked_out', 'var1' => $lockouttime));
    }

    //now check if there has been another attempt since and they have gone over their attempt limit
    if (($attempts >= $lockouttries) && (xarModGetVar('authsystem','uselockout')==true)){
        // set the time for fifteen minutes from now
        xarSession::setVar('authsystem.login.lockedout', time() + (60 * $lockouttime));
        xarSession::setVar('authsystem.login.attempts', 0);
        return xarTplModule('authsystem','user','errors',array('errortype' => 'locked_out', 'var1' => $lockouttime));
    } else {

        //now we can continue
        // Fetch and validate the values entered into the login form
        // Username
        if (!xarVarFetch('uname','pre:trim:str:0:100',$uname,'',XARVAR_NOT_REQUIRED)) return;
        if (empty($uname))
        {
            $newattempts = $attempts + 1;
            xarSession::setVar('authsystem.login.attempts', $newattempts);
            return xarTplModule('authsystem','user','errors',array('errortype' => 'bad_try', 'var1' => $newattempts));

        }
        // Password
        if (!xarVarFetch('pass','pre:trim:passthru:str:0:100',$pass,'',XARVAR_NOT_REQUIRED)) return;
        if (empty($pass))
        {
            $newattempts = $attempts + 1;
            xarSession::setVar('authsystem.login.attempts', $newattempts);
            return xarTplModule('authsystem','user','errors',array('errortype' => 'bad_try', 'var1' => $newattempts));
        }
    }
    // Check to see if the user wants their session/login to be 'remembered' - made persistent
    if (!xarVarFetch('rememberme','checkbox',$rememberme,false,XARVAR_NOT_REQUIRED)) return;

    // By default redirect to the base URL on the site
    $redirect = xarServer::getBaseURL();
    if (!xarVarFetch('redirecturl','pre:trim:str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;
    // If the redirect URL contains authsystem go to base url
    // CHECKME: <mrb> why is this?
    if (preg_match('/authsystem/',$redirecturl)) {
        $redirecturl = $redirect;
    }

    // Scan authentication modules and set user state appropriately

    $extAuthentication = false;

    foreach($xarUser_authenticationModules as $authModName) {
        switch(strtolower($authModName)) {
             case 'authsystem':
                 //Set a $lastresort flag var
                $lastresort=false;
                // Still need to check if user exists as the user may be
                // set to inactive in the user table
                //Get and check last resort first before going to db table
                $lastresortvalue=array();
                $lastresortvalue=xarModGetVar('privileges','lastresort');
                if (isset($lastresortvalue)) {
                    $secret = @unserialize(xarModGetVar('privileges','lastresort'));
                    if (is_array($secret)) {
                        if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                            $lastresort=true;
                            $state = ROLES_STATE_ACTIVE;
                            break; //let's go straight to login api
                        }
                    }
                }
                // check for user and grab uid if exists
                $user = xarMod::apiFunc('roles','user','get', array('uname' => $uname));
                $uid = (int)$user['uid'];

               if (empty($user) && ($extAuthentication == false))
                {

                    $attempts = (int) xarSessionGetVar('authsystem.login.attempts');
                    if (($attempts >= $lockouttries) && (xarModGetVar('authsystem','uselockout')==true)){
                        // set the time for fifteen minutes from now
                        xarSession::setVar('authsystem.login.lockedout', time() + (60 * $lockouttime));
                        xarSession::setVar('authsystem.login.attempts', 0);
                        return xarTplModule('authsystem','user','errors',array('errortype' => 'bad_tries_exceeded','var1'=>$lockouttime));
                    } else {
                        $newattempts = $attempts + 1;
                        xarSession::setVar('authsystem.login.attempts', $newattempts);
                        return xarTplModule('authsystem','user','errors',array('errortype' => 'bad_try', 'var1' => $attempts));
                    }
                } elseif (empty($user)) {
                    // Check if user has been deleted.
                    try {
                        $user = xarMod::apiFunc('roles','user','getdeleteduser',
                                                array('uname' => $uname));
                    } catch (Exception $e) {
                        //getdeleteduser raised an exception
                    }
                }

                if (!empty($user)) {
                    $rolestate = $user['state'];
                    // If external authentication has already been set but
                    // the Xarigami users table has a different state (ie invalid)
                    // then override the external state
                    if (($extAuthentication == true) && ($state != $rolestate)) {
                        $state = $rolestate;
                    } else {
                        // No external authentication, so set state
                        $state = $rolestate;
                    }
                }
                break;
            // jojo  - unnecessary to have entries for authldap etc
            // kept here for documentation purposes
            // The authldap module allows the admin to allow an
            //
            // The LDAP user to automatically login to Xarigami without
            // having a Xarigami user account in the roles table.
            // If the user is successfully retrieved from LDAP,
            // then a corresponding entry will be created in the
            // roles table.  So set the user state to allow for
            // login.
            //
            // The AUTHSSO module delegates login authority to
            // web server (trusts the web server to authenticate
            // the user's credentials), just as authldap
            // delegates to an LDAP server
            case 'authldap':
            case 'authimap':
            case 'authsso':
            default:
                // some other auth module is being used eg authemail.  We're going to assume
                // that xaraya will be the slave to the other system and
                // if the user is successfully retrieved from that auth system,
                // then a corresponding entry will be created in the
                // roles table if not already.  So set the user state to allow for
                // login unless it is already set.
                // Later during core login, the individual auth module authentication routines are called
                $state = !isset($state)?ROLES_STATE_ACTIVE :$state;
                $extAuthentication = true;
                break;
        }
    }

    switch($state) {

        case ROLES_STATE_DELETED:
            // User is deleted by all means.  Return a message that says the same.
            return xarTplModule('authsystem','user','errors',array('errortype' => 'account_deleted'));
            break;
        case ROLES_STATE_INACTIVE:
            // User is inactive.  Return message stating.
            return xarTplModule('authsystem','user','errors',array('errortype' => 'account_inactive'));
            break;
        case ROLES_STATE_NOTVALIDATED:
            //User still must validate
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation'));
            break;
        case ROLES_STATE_PENDING:
            // User is pending activation
            return xarTplModule('authsystem','user','errors',array('errortype' => 'account_pending'));
            break;;
        case ROLES_STATE_ACTIVE:
        default:
            // User is active or state to be determined by external authentication
            // TODO: remove this when everybody has moved to 1.0
            // <mrb> Havent we now? If not, this shouldn't be here?

            if(!xarModGetVar('roles', 'lockdata')) {
            //We know the default administrator from roles after 1.0, so get the admin and find their group
            //Assume we have our old pre 1.0 values - valid in majority of cases
           // $admingroupuid = 4;
           // $admingroupname = 'Administrators';
            /* Grab the default roles admin and find their parent group (post 1.0) */
            $defaultadmin = xarModGetVar('roles','admin');
            if (isset($defaultadmin)  && !empty($defaultadmin)) {
                $admindata = xarMod::apiFunc('roles','user','getrole',array('uid' => $defaultadmin));
                //get the site admin parent group
                $adminrole = xarUFindRole($admindata['uname']);
                $parentrole = $adminrole->getParents();
                //assume the admin has one parent??
                $admingroupuid = $parentrole[0]->uid;
                $admingroupname = $parentrole[0]->uname;
            }

                $lockdata = array(
                    'roles' => array(
                        array(
                            'uid'    => $admingroupuid,
                            'name'   => $admingroupname,
                            'notify' => true
                        )
                    ),
                    'message'   => '',
                    'locked'    => 0,
                    'notifymsg' => '',
                    'killactive' => false
                );
                xarModSetVar('roles', 'lockdata', serialize($lockdata));
            }

            // Check if the site is locked and this user is allowed in
            $lockvars = unserialize(xarModGetVar('roles','lockdata'));
            if ($lockvars['locked'] ==1)
            {
                $rolesarray = array();
                $rolemaker = new xarRoles();
                $roles = $lockvars['roles'];
                for($i=0, $max = count($roles); $i < $max; $i++)
                        $rolesarray[] = $rolemaker->getRole($roles[$i]['uid']);
                $letin = array();
                foreach($rolesarray as $roletoletin)
                {
                    if ($roletoletin->isUser())
                        $letin[] = $roletoletin;
                    else
                        $letin = array_merge($letin,$roletoletin->getUsers());
                }
                $letthru = false;
                foreach ($letin as $roletoletin)
                {
                    if (strtolower($uname) == strtolower($roletoletin->getUser()))
                    {
                        $letthru = true;
                        break;
                    }
                }

                if (!$letthru)
                {
                    // If there is a locked.xt page then use that, otherwise show the default.xt page
                    xarTplSetPageTemplateName('locked');
                    return xarTplModule('authsystem','user','errors',array('errortype' => 'site_locked', 'var1'  => $lockvars['message']));

                }
            }

            // OK, let's try to log this user in, we no longer have enough
            // information to determine this here, so we pass it on to the
            // login API function and let that determine for us if this user/pw
            // combo can be authenticated.
            xarLogMessage("Authsystem: passing authentication to core");
            $res = xarMod::apiFunc(
                'authsystem','user','login',
                array('uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme)
            );

            xarLogMessage("Authsystem: authentication chain delivered: ". var_export($res,true));

            if ($res === null)
            {
                // Null means error?
                return;
            } elseif ($res == false) {
                // Problem logging in
                // TODO - work out flow, put in appropriate HTML
                xarLogMessage("Authsystem: auth failed");

                // Cast the result to an int in case VOID is returned
                $attempts = (int) xarSessionGetVar('authsystem.login.attempts');

                if (($attempts >= $lockouttries) && (xarModGetVar('authsystem','uselockout')==true)){
                    // set the time for fifteen minutes from now
                    xarSession::setVar('authsystem.login.lockedout', time() + (60 * $lockouttime));
                    xarSession::setVar('authsystem.login.attempts', 0);
                     return xarTplModule('authsystem','user','errors',array('errortype' => 'bad_tries_exceeded', 'var1' => $lockouttime));
                } else {
                    $newattempts = $attempts + 1;
                    xarSession::setVar('authsystem.login.attempts', $newattempts);
                    return xarTplModule('authsystem','user','errors',array('errortype' => 'bad_try', 'var1' => $newattempts));
                }
            } elseif ($res !== true && !is_int($res)) {
                return xarTplModule('authsystem','user','errors',array('errortype' => $res));
            }

            //FR for last login - first capture the last login for this user
            $thislastlogin =xarModUserVars::get('roles','userlastlogin');
            if (!empty($thislastlogin) ) {
                //move this to a session var for this user
                xarSession::setVar('roles_firstlogin',FALSE);
                xarSession::setVar('roles_thislastlogin',$thislastlogin);
            } else {
                xarSession::setVar('roles_firstlogin',TRUE);
            }
            xarModSetUserVar('roles','userlastlogin',time()); //this is what everyone else will see
            $userdata['uname'] =$uname;
            $userdata['pass'] = $pass;
            if (empty($uid)) {
                 $user = xarMod::apiFunc('roles','user','get', array('uname' => $uname));
                 $uid = $user['uid'];
            }
            //call hooks here - new login - creat hook ....
            $userdata['module'] = 'authsystem';
            $userdata['itemid'] = $uid;
            xarMod::callHooks('item', 'create', $uid, $userdata);
            //start check for redirects on login - first login or any login
            $redirected = xarMod::apiFunc('authsystem','user','checkredirects',array('uname'=>$uname,'uid'=>$uid,'lastresort'=>$lastresort,'redirecturl'=>$redirecturl));

            xarResponseRedirect($redirected);

    }
    return true;
}
?>
