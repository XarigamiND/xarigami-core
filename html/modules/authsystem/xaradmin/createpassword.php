<?php
/**
 * Create a new password for the user
 *
 * @package Xaraya modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * createpassword - create a new password for the user
 * @param state
 * @param int groupuid
 * @param id uid The user id
 */
function authsystem_admin_createpassword()
{
    // Security Check
    if (!xarSecurityCheck('EditAuthsystem',0)) return xarResponseForbidden();

    // Get parameters
    if (!xarVarFetch('state',    'isset',  $state,    NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('groupuid', 'int:0:', $groupuid, 0,    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uid',      'isset',  $uid)) {
        throw new BadParameterException(array('parameters','admin','createpassword','authsystem'), xarML('Invalid #(1) for #(2) function #(3)() in module #(4)'));
    }

    $pass = xarMod::apiFunc('roles', 'user', 'makepass');
    if (empty($pass)) {
        throw new BadParameterException(null,xarML('Problem generating new password'));
     }
     $roles          = new xarRoles();
     $role           = $roles->getRole($uid);
     $modifiedstatus = $role->setPass($pass);
     $modifiedrole   = $role->update();
     if (!$modifiedrole) {
        return;
     }
     if (!xarModGetVar('roles', 'askpasswordemail')) {
        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',
                      array('uid' => $data['groupuid'], 'state' => $data['state'])));
        return true;
    }
    else {

        xarSession::setVar('tmppass',$pass);
        xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
        array('uid' => array($uid => '1'), 'mailtype' => 'password', 'groupuid' => $groupuid, 'state' => $state)));
    }
}
?>