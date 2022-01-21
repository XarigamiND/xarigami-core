<?php
/**
 * User System
 *
 * @package Xarigami core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Users
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Exceptions defined by this subsystem
 *
 */
class NotLoggedInException extends xarExceptions
{
    protected $message = 'An operation was encountered that requires the user to be logged in. If you are currently logged in please report this as a bug.';
}

/**
 * Authentication modules capabilities
 * (to be revised e.g. to differentiate read & update capability for core & dynamic)
 */
define('XARUSER_AUTH_AUTHENTICATION',             1);
define('XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER',  2);
define('XARUSER_AUTH_PERMISSIONS_OVERRIDER',     16);
define('XARUSER_AUTH_USER_CREATEABLE',           32);
define('XARUSER_AUTH_USER_DELETEABLE',           64);
define('XARUSER_AUTH_USER_ENUMERABLE',          128);

/*
 * Error codes
 */
define('XARUSER_AUTH_FAILED', -1);
define('XARUSER_AUTH_DENIED', -2);
define('XARUSER_LAST_RESORT', -3);

/**
 * Initialise the User System
 *
 * @access protected
 * @global xarUser_authentication modules array
 * @param args[authenticationModules] array
 * @return bool true on success
 */
function xarUser_init($args, $whatElseIsGoingLoaded)
{
    // User System and Security Service Tables
    $systemPrefix = xarDB::$sysprefix;

    // CHECK: is this needed?
    $tables = array( 'roles'       => $systemPrefix . '_roles',
                     'realms'      => $systemPrefix . '_security_realms',
                     'rolemembers' => $systemPrefix . '_rolemembers');

    xarDB::importTables($tables);

    $GLOBALS['xarUser_authenticationModules'] = $args['authenticationModules'];

    if (function_exists('xarUserGetNavigationLocale')) {
        $locale = xarUserGetNavigationLocale();
        xarMLS_setCurrentLocale($locale);
        // Performs eventually some redirections here (MLS virtual paths):
        xarMLSEnforceLocale($locale);
    }

    if (class_exists('xarTpl')) {
        xarTpl::setThemeName(xarUserGetNavigationThemeName());
    }

    // Register the UserLogin event
    xarEvents::register('UserLogin');
    // Register the UserLogout event
    xarEvents::register('UserLogout');

    // Eventually log last time visit
    xarUser__logLastVisit();

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarUser__shutdown_handler');
    return true;
}

/**
 * Log the user in
 *
 * @access public
 * @param  string  $userName the name of the user logging in
 * @param  string  $password the password of the user logging in
 * @param  integer $rememberMe whether or not to remember this login
 * @return bool true if the user successfully logged in
 * @throws EmptyParameterException, SQLException
 */
function xarUserLogIn($userName, $password, $rememberMe = 0)
{
    //check to see if it is a proxy user as they have to be logged in
    $proxyuser = xarMod::apiFunc('roles','admin','canproxy');

    if (xarUserIsLoggedIn() && ($proxyuser == FALSE)) {
        return true;
    }

    if (empty($userName)) throw new EmptyParameterException('userName');
    if (empty($password)) throw new EmptyParameterException('password');

    $userId = XARUSER_AUTH_FAILED;
    $args = array('uname' => $userName, 'pass' => $password);

    foreach($GLOBALS['xarUser_authenticationModules'] as $authModName)
    {
        // Bug #918 - If the module has been deactivated, then continue
        // checking with the next available authentication module
        if (!xarMod::isAvailable($authModName))
            continue;

        // Every authentication module must at least implement the
        // authentication interface so there's at least the authenticate_user
        // user api function
        if (!xarMod::apiLoad($authModName, 'user'))
            continue;

        xarLogMessage("Auth: trying authentication against $authModName");

        $userId = xarMod::apiFunc($authModName, 'user', 'authenticate_user', $args);

        if (!isset($userId)) {
            return; // throw back
        } elseif ($userId != XARUSER_AUTH_FAILED) {
            // Someone authenticated the user or passed XARUSER_AUTH_DENIED
            break;
        }
    }
    //$userId could be empty, null, an integer, XARUSER_AUTH_FAILED, or some authsystem $tpl user error name
    //We have returned if not set
    //Carefully go through other alternatives in order
    if ($userId == XARUSER_AUTH_FAILED || $userId == XARUSER_AUTH_DENIED) {
        if (xarModGetVar('privileges','lastresort')) {
            $secret = @unserialize(xarModGetVar('privileges','lastresort'));
            if ($secret['name'] == MD5($userName) && $secret['password'] == MD5($password)) {
                $userId = XARUSER_LAST_RESORT;
                $rememberMe = 0;
            }
        }

        //set var to hold check status
        $checkok = false;
        //let's be careful here as we may hve a number of conditions
        if ($userId ==XARUSER_LAST_RESORT) {
            $checkok =TRUE;
        }
        if ($proxyuser) {
            //log them out of their session before continuing so we don't get confused session info
            $user = xarMod::apiFunc('roles','user','get',array('uname'=>$userName));
            $userId = $user['uid'];
            $checkok =TRUE;

        }
        if ($checkok == FALSE) {
            return FALSE;
        }
    }  elseif (!is_int($userId)) {
        //Return - could be $tpl user error name, null, or empty here
        //continues to next step only if $userId is int
        return $userId;
    }

    // Catch common variations (0, false, '', ...)
    if (empty($rememberMe))
        $rememberMe = 0; //tinyint
    else
        $rememberMe = 1;

    // Set user session information
    if (!xarSession::setUserInfo($userId, $rememberMe)) return; // throw back

    // Set user auth module information
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolestable = $xartable['roles'];

    // TODO: this should be inside roles module
    $query = "UPDATE $rolestable SET xar_auth_module = ? WHERE xar_uid = ?";
    $result = $dbconn->Execute($query,array($authModName,$userId));
    if (!$result) return;

    // Set session variables

    // Keep a reference to auth module that authenticates successfully
    xarSessionSetVar('authenticationModule', $authModName);

    // <lakys> and eventually last visit.
    xarUser__logLastVisit();

    // User logged in successfully, trigger the proper event with the new userid
    xarEvents::trigger('UserLogin',$userId);

    return true;
}

/**
 * Log the user out
 *
 * @access public
 * @return bool true if the user successfully logged out
 */
function xarUserLogOut()
{
    if (!xarUserIsLoggedIn()) {
        return true;
    }
    // get the current userid before logging out
    $userId = xarSession::getVar('uid');

    // Reset user session information
    $res = xarSession_setUserInfo(_XAR_ID_UNREGISTERED, 0);
    if (!isset($res)) {
        return; // throw back
    }

    xarSession::delVar('authenticationModule');

    // User logged out successfully, trigger the proper event with the old userid
    xarEvents::trigger('UserLogout',$userId);

    return true;
}

/**
 * Check if the user logged in
 *
 * @access public
 * @return bool true if the user is logged in, false if they are not
 */

global $installing;

function xarUserIsLoggedIn()
{
    // FIXME: restore "clean" code once uid+session issues are resolved
    //return xarSession::getVar('uid') != _XAR_ID_UNREGISTERED;
    return (xarSession::getVar('uid') != _XAR_ID_UNREGISTERED
            && xarSession::getVar('uid') != 0);
}

/**
 * Gets the user navigation theme name
 * @access public
 * @return string name of the users navigation theme
 */
function xarUserGetNavigationThemeName()
{
    $themeName = xarTpl::getThemeName();

    if (xarUserIsLoggedIn()){
        $uid = xarUserGetVar('uid');
        $userThemeName = xarModUserVars::get('themes', 'default', $uid);
        if ($userThemeName) $themeName=$userThemeName;
    }

    return $themeName;
}

/**
 * Set the user navigation theme name
 *
 * @access public
 * @param  string $themeName name of the theme to set as navigation theme
 * @return void
 */
function xarUserSetNavigationThemeName($themeName)
{
    assert($themeName != "");
    // uservar system takes care of dealing with anynomous
    xarModSetUserVar('themes', 'default', $themeName);
}

/**
 * Get the user navigation locale
 *
 * @access public
 * @return string $locale users navigation locale name
 */
function xarUserGetNavigationLocale()
{
    // Shortcut if the suitable modes are not activated
    // It would prevent the User Var to be overwritten
    if (xarMLSGetMode() == XARMLS_SINGLE_LANGUAGE_MODE) {
        $locale = xarMLSGetSiteLocale();
        xarSession::setVar('navigationLocale', $locale);
        //jojo - we need consistent return from call of this function ie $locale
        //return true;
        //Let us return the existing locale
        return $locale;
    }
    // MLS Virtual paths
    if (xarMLSVirtualPathsIsEnabled()) {
        $locale = xarMLSLocaleFromURI(xarServer::getClientRequestURI());
    }

    // User is logged in
    if (xarUserIsLoggedIn()) {
        $uid = xarUserGetVar('uid');
          //last resort user is falling over on this uservar by setting multiple times
         //return true for last resort user - use default locale
         if ($uid==XARUSER_LAST_RESORT) return true;

        $userLocale = xarModUserVars::get('roles', 'locale');
        $sessionLocale = xarSession::getVar('navigationLocale');

        if (isset($locale)) {
            // Virtual paths determine the locale
            if (!isset($userLocale) || $userLocale != $locale) xarModSetUserVar('roles', 'locale', $locale); // Should it be overwritten here?
            if (!isset($sessionLocale) || $sessionLocale != $locale) xarSession::setVar('navigationLocale', $locale);
            return $locale;
        }

        $siteLocales = xarMLSListSiteLocales();

        // Use first user var
        $locale = $userLocale;
        if (isset($locale)) {
            // User locale still existing?
            if (!in_array($locale, $siteLocales)) {
                xarLogMessage("WARNING: falling back to an alternate locale: $locale missing in xarUserGetNavigationLocale function");
                unset($locale);
            }
        }

        if (!isset($locale) && isset($sessionLocale)) {
            // Use session var then
            $locale = $sessionLocale;
            // Well accidentally the locale files have been just removed
            if (!in_array($locale, $siteLocales)) {
                xarLogMessage("WARNING: falling back to an alternate locale: $locale missing in xarUserGetNavigationLocale function");
                unset($locale);
            }
        }

        if (!isset($locale)) {
            // Use site default (from MLS)
            $locale = xarMLSGetSiteLocale();
            if (!isset($locale)) {
                // Use site default (from roles)
                $locale = xarModGetVar('roles', 'locale');
            }
            // We would not be here if files were missing.
        }

        // So now we have found what is the locale
        // We can eventually overwrite the vars
        if (!isset($userLocale) || $userLocale != $locale) xarModSetUserVar('roles', 'locale', $locale); // Should it be overwritten here?
        if (!isset($sessionLocale) || $sessionLocale != $locale) xarSession::setVar('navigationLocale', $locale);

    } else {
        // Anonymous user
        $sessionLocale = xarSession::getVar('navigationLocale');

        // No virtual path found
        if (!isset($locale)) {
            // New Session
            if (!isset($sessionLocale)) {
                // CHECKME: use dynamicdata for roles, module user variable and/or
                // session variable (see also 'timezone' in xarMLS_userOffset())

                // Locale auto-dectection from client browser information
                $locale = xarMLSGetClientBrowserLocale(); // Still return xarMLSGetSiteLocale() if auto-detection is deactivated
            }
            // Use the Session Locale
            else {
                $locale = $sessionLocale;
            }
        }

        // Update session var navigation locale only if required
        if (!isset($sessionLocale) || isset($locale) && $sessionLocale != $locale) xarSession::setVar('navigationLocale', $locale);
    }

    return $locale;
}

/**
 * Set the user navigation locale
 *
 * @access public
 * @param  string $locale
 * @return bool true if the navigation locale is set, false if not
 */
function xarUserSetNavigationLocale($locale)
{
    if (xarMLSGetMode() != XARMLS_SINGLE_LANGUAGE_MODE)
    {
        xarSession::setVar('navigationLocale', $locale);
        if (xarUserIsLoggedIn())
        {
            $userLocale = xarModUserVars::get('roles', 'locale');
            if (!isset($userLocale)) {
                $siteLocale = xarModVars::get('roles', 'locale');
                if (!isset($siteLocale)) {
                    xarModVars::set('roles', 'locale', '');
                }
            }
            xarModSetUserVar('roles', 'locale', $locale);
        }
        return true;
    }
    return false;
}

/*
 * User variables API functions
 */

/*
 * Initialise the user object
 */
$GLOBALS['xarUser_objectRef'] = null;

/**
 * Get a user variable
 *
 * @access public
 * @param  string  $name the name of the variable
 * @param  integer $userId integer the user to get the variable for
 * @return mixed the value of the user variable if the variable exists, void if the variable doesn't exist
 * @throws EmptyParameterException, NotLoggedInException, BadParameterException, IDNotFoundException
 * @todo <marco> #1 figure out why this check failsall the time now: if ($userId != xarSessionGetVar('role_id')) {
 * @todo <marco FIXME: ignoring unknown user variables for now...
 */
function xarUserGetVar($name, $userId = NULL)
{
    if (empty($name)) throw new EmptyParameterException('name');

    if (empty($userId)) $userId = xarSessionGetVar('uid');
    if ($name == 'id' || $name == 'uid') return $userId;

    if (empty($userId)) {
        $userId = xarSession::getVar('uid');
    }
    if ($name == 'uid') {
        return $userId;
    }
    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user => only uid, name and uname allowed, for other variable names
        // an exception of type NOT_LOGGED_IN is raised
        if ($name == 'name' || $name == 'uname') {
            return xarML('Anonymous');
        }
        throw new NotLoggedInException();
    }

    // Don't allow any module to retrieve passwords in this way
    if ($name == 'pass') throw new BadParameterException('name');

    if (!xarCoreCache::isCached('User.Variables.'.$userId, $name)) {

        if ($name == 'name' || $name == 'uname' || $name == 'email' || $name == 'date_reg') {
            if ($userId == XARUSER_LAST_RESORT) {
                return xarML('No Information');
            }
            // retrieve the item from the roles module
            $userRole = xarMod::apiFunc('roles',  'user',  'get',
                                       array('uid' => $userId));

            if (empty($userRole) || $userRole['uid'] != $userId) {
                throw new IDNotFoundException($userId,'User identified by id #(1) does not exist.');
            }

            xarCoreCache::setCached('User.Variables.'.$userId, 'uname', $userRole['uname']);
            xarCoreCache::setCached('User.Variables.'.$userId, 'name', $userRole['name']);
            xarCoreCache::setCached('User.Variables.'.$userId, 'email', $userRole['email']);
            xarCoreCache::setCached('User.Variables.'.$userId, 'date_reg', $userRole['date_reg']);
        } elseif (!xarUser__isVarDefined($name)) {
            if (xarModVars::get('roles',$name) || xarModVars::get('roles','set'.$name)) { //acount for optionals that need to be activated
                $value = xarModUserVars::get('roles',$name,$userId);
                 if ($value == null) {
                    xarCoreCache::setCached('User.Variables.'.$userId, $name, false);
                    // Here we can't raise an exception because they're all optional
                    $optionalvars=array('locale','timezone','usertimezone','userlastlogin', 'userlastvisit',
                                        'userhome','primaryparent','passwordupdate');
                    //if ($name != 'locale' && $name != 'timezone') {
                    if (!in_array($name, $optionalvars)) {
                    // log unknown user variables to inform the site admin
                        $msg = xarML('User variable #(1) was not correctly registered', $name);
                        xarLogMessage($msg, XARLOG_LEVEL_ERROR);
                    }
                    return;
                }  else {
                    xarCoreCache::setCached('User.Variables.'.$userId, $name, $value);
                }
            }

        } else {
            // retrieve the user item
            $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
            if (empty($itemid) || $itemid != $userId) {
                throw new IDNotFoundException($userId,'User identified by id #(1) does not exist.');
            }

            // save the properties
            $properties =& $GLOBALS['xarUser_objectRef']->getProperties();
            foreach (array_keys($properties) as $key) {
                if (isset($properties[$key]->value)) {
                    xarCoreCache::setCached('User.Variables.'.$userId, $key, $properties[$key]->value);
                }
            }
        }
    }

    if (!xarCoreCache::isCached('User.Variables.'.$userId, $name)) {
        return false; //failure
    }

    $cachedValue = xarCoreCache::getCached('User.Variables.'.$userId, $name);
    if ($cachedValue === false) {
        // Variable already searched but doesn't exist and has no default
        return;
    }

    return $cachedValue;
}

/**
 * Set a user variable
 *
 * @since 1.23 - 2002/02/01
 * @access public
 * @param  string  $name  the name of the variable
 * @param  mixed   $value the value of the variable
 * @param  integer $userId integer user's ID
 * @return bool true if the set was successful, false if validation fails
 * @throws EmptyParameterException, BadParameterException, NotLoggedInException, xarException, IDNotFoundException
 * @todo redesign the delegation to auth* modules for handling user variables
 * @todo some securitycheck for retrieving at least other users variables ?
 */
function xarUserSetVar($name, $value, $userId = NULL)
{
    // check that $name is valid
    if (empty($name)) throw new EmptyParameterException('name');
    if ($name == 'uid' || $name == 'authenticationModule' || $name == 'pass') {
        throw new BadParameterException('name');
    }

    if (empty($userId)) {
        $userId = xarSession::getVar('uid');
    }
    if ($userId == _XAR_ID_UNREGISTERED) {
        // Anonymous user
        throw new NotLoggedInException();
    }

    if ($name == 'name' || $name == 'uname' || $name == 'email') {
        // TODO: replace with some roles API
        xarUser__setUsersTableUserVar($name, $value, $userId);

    } elseif (!xarUser__isVarDefined($name)) {
        if (xarModVars::get('roles',$name)) {
            xarCoreCache::setCached('User.Variables.'.$userId, $name, false);
            throw new xarException($name,'User variable #(1) was not correctly registered');
        } else {
            xarModSetUserVar('roles',$name,$value,$userId);
        }
    } else {
        // retrieve the user item
        $itemid = $GLOBALS['xarUser_objectRef']->getItem(array('itemid' => $userId));
        if (empty($itemid) || $itemid != $userId) {
            throw new IDNotFoundException($userId,'User identified by id "#(1)" does not exist.');
        }

        // check if we need to update the item
        if ($value != $GLOBALS['xarUser_objectRef']->properties[$name]->value) {
            // validate the new value
            if (!$GLOBALS['xarUser_objectRef']->properties[$name]->validateValue($value)) {
                return false;
            }
            // update the item
            $itemid = $GLOBALS['xarUser_objectRef']->updateItem(array($name => $value));
            if (!isset($itemid)) return; // throw back
        }
    }

    // Keep in sync the UserVariables cache
    xarCoreCache::setCached('User.Variables.'.$userId, $name, $value);

    return true;
}

/**
 * Compare Passwords
 *
 * @access public
 * @param  string $givenPassword  the password given for comparison
 * @param  string $realPassword   the reference password to compare to
 * @param  string $userName       name of the corresponding user?
 * @param  string $cryptSalt      ?
 * @return bool true if the passwords match, false otherwise
 */
function xarUserComparePasswords($givenPassword, $realPassword, $userName, $cryptSalt = '')
{
    // TODO: consider moving to something stronger like sha1
    $md5pass = md5($givenPassword);
    if (strcmp($md5pass, $realPassword) == 0)
        return $md5pass;

    return false;
}

// PROTECTED FUNCTIONS

// PRIVATE FUNCTIONS

/**
 * Get user's authentication module
 *
 * @access private
 * @param string $userId
 * @throws UNKNOWN, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 * @todo FIXME: what happens for anonymous users ???
 * @todo check coherence 1 vs. 0 for Anonymous users !!!
 */
function xarUser__getAuthModule($userId)
{
    if ($userId == xarSession::getVar('uid')) {
        $authModName = xarSession::getVar('authenticationModule');
        if (isset($authModName)) {
            return $authModName;
        }
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    // Get user auth_module name
    $rolestable = $xartable['roles'];

    $query = "SELECT xar_auth_module FROM $rolestable WHERE xar_uid = ?";
    $result = $dbconn->Execute($query,array($userId));
    if (!$result) return;

    if ($result->EOF) {
        // That user has never logon, strange, don't you think?
        // However fallback to authsystem
        $authModName = 'authsystem';
    } else {
        list($authModName) = $result->fields;
        // TODO: remove when issue of Anonymous users is resolved
        // Q: what issue?
        if (empty($authModName)) {
            $authModName = 'authsystem';
        }
    }
    $result->Close();

    if (!xarMod::apiLoad($authModName, 'user')) return;

    return $authModName;
}

/**
 * See if a Variable has been defined
 * @access private
 * @param  string $name name of the variable to check
 * @return bool true if the variable is defined
 */
function xarUser__isVarDefined($name)
{
    // Retrieve the dynamic user object if necessary
    if (!isset($GLOBALS['xarUser_objectRef']) && xarMod::isHooked('dynamicdata','roles')) {
        $GLOBALS['xarUser_objectRef'] = xarMod::apiFunc('dynamicdata', 'user', 'getobject',
                                                       array('module' => 'roles'));
        if (empty($GLOBALS['xarUser_objectRef']) || empty($GLOBALS['xarUser_objectRef']->objectid)) {
            $GLOBALS['xarUser_objectRef'] = false;
        }
    }

    // Check if this property is defined for the dynamic user object
    if (empty($GLOBALS['xarUser_objectRef']) || empty($GLOBALS['xarUser_objectRef']->properties[$name])) {
        return false;
    }

    return true;
}

/**
 * @access private
 * @return bool
 * @throws NOT_LOGGED_IN, UNKNOWN, DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST
 */
function xarUser__syncUsersTableFields()
{
    $userId = xarSession::getVar('uid');
    assert($userId != _XAR_ID_UNREGISTERED);

// TODO: configurable one- or two-way re-synchronisation of core + dynamic fields ?

    $authModName = xarUser__getAuthModule($userId);
    if (!isset($authModName)) return; // throw back
    if ($authModName == 'authsystem') return true; // Already synced

    $res = xarMod::apiFunc($authModName, 'user', 'has_capability',
                         array('capability' => XARUSER_AUTH_DYNAMIC_USER_DATA_HANDLER));
    if (!isset($res)) return; // throw back
    if ($res == false) return true; // Impossible to go out of sync

// TODO: improve multi-update operations

    $name = xarUserGetVar('name');
    if (!isset($name)) return; // throw back
    $res = xarUser__setUsersTableUserVar('name', $name, $userId);
    if (!isset($res)) return; // throw back
    $uname = xarUserGetVar('uname');
    if (!isset($uname)) return; // throw back
    $res = xarUser__setUsersTableUserVar('uname', $uname, $userId);
    if (!isset($res)) return; // throw back
    $email = xarUserGetVar('email');
    if (!isset($email)) return; // throw back
    $res = xarUser__setUsersTableUserVar('email', $email, $userId);
    if (!isset($res)) return; // throw back

    return true;
}

/**
 * @access private
 * @return bool
 * @throws DATABASE_ERROR
 */
function xarUser__setUsersTableUserVar($name, $value, $userId)
{
    $dbconn = xarDB::$dbconn;
    xarModDBInfoLoad('roles');
    $xartable = &xarDB::$tables;

    $rolestable = $xartable['roles'];
    $usercolumns = $xartable['users_column'];

    // The $name variable will be used to get the appropriate column
    // from the users table.
    $query = "UPDATE $rolestable
              SET $usercolumns[$name] = ? WHERE xar_uid = ?";
    $result = $dbconn->Execute($query,array($value,$userId));
    if (!$result) return;
    return true;
}

/**
 * @access private
 */
function xarUser__logLastVisit()
{
    static $__doneonce;
    // test on static for best performances.
    if (!isset($__doneonce) && xarModGetVar('roles','set'. 'userlastvisit') && xarUserIsLoggedIn()) {
        xarModSetUserVar('roles', 'userlastvisit', time());
    }
    $__doneonce = true;
}
?>
