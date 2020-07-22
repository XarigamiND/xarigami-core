<?php
/**
 * Create a new password for the user
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * createpassword - create a new password for the user
 */
function roles_admin_createpassword()
{

    // Get parameters
    if (!xarVarFetch('state',    'isset',  $state,    NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('groupuid', 'int:0:', $groupuid, 0,    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uid', 'isset', $uid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','parameters', 'admin', 'createpassword', 'Roles');
       throw new BadParameterException(null,$msg);

    }
    // Security Check
    if (!xarSecurityCheck('EditRole',0,'Roles',$uid) && !xarSecurityCheck('ModerateGroupRoles',0,'Group',$groupuid))  return xarResponseForbidden();

    $pass = xarMod::apiFunc('roles', 'user', 'makePass');
    if (empty($pass)) {
            $msg = xarML('Problem generating new password');
            throw new BadParameterException(null,$msg);
     }
     $roles = new xarRoles();
     $role  = $roles->getRole($uid);
     $modifiedstatus = $role->setPass($pass);
     $modifiedrole   = $role->update();
     if (!$modifiedrole) {
        return;
     }

     if (!xarModGetVar('roles', 'askpasswordemail')) {

        xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',
                      array('uid' => $groupuid, 'state' => $state)));
        return true;
    }  else {

        xarSession::setVar('tmppass',$pass);
        xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                            array('uid'      => array($uid => '1'),
                                  'mailtype' => 'password',
                                  'groupuid' => $groupuid,
                                  'state'    => $state)));
                                      
    }
}
?>