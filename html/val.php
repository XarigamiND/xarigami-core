<?php
/**
 * Redirect for validating users
 *
 * Please do not modify this file: editing this file in any way will prevent it from working.
 * If you are having issues, please drop into #xarigami room at irc://talk.xarigami.com
 *
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Core package
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author John Cox
 * @todo jojodee - rethink dependencies between roles, authentication(authsystem) and
 *                 registration in relation to validation
*/

/**
 *  initialize the Xarigami core
 */
include_once (getcwd().DIRECTORY_SEPARATOR.'bootstrap.php');
if (!class_exists('sys')) die('could not load the bootstrap');
sys::init(sys::MODE_VALIDATION);

// Added but was not there before... just to see exception...
sys::import('xarigami.xarCache');
xarCache::init();
// To remove.

sys::import('xarigami.xarCore');
xarCore::init(XARCORE_SYSTEM_ALL);

if (!xarVarFetch('v', 'str:1', $v, NULL, XARVAR_DONT_SET | XARVAR_VAL_RESULT)) return xarResponse::NotFound();
if (!xarVarFetch('u', 'str:1', $u, NULL, XARVAR_DONT_SET | XARVAR_VAL_RESULT)) return xarResponse::NotFound();

if (!isset($u) || !isset($v)) return xarResponse::NotFound();

$user = xarMod::apiFunc('roles', 'user', 'get', array('uid' => $u));

//check no-one is already logged into a xarigami session and log out just in case
if (xarUserIsLoggedIn()) {
    xarUserLogOut();
}
xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation',
                              array('stage'   => 'getvalidate',
                                    'valcode' => $v,
                                    'uname'   => $user['uname'],
                                    'phase'   => 'getvalidate')));

// done
exit;
?>