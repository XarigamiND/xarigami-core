<?php
/**
 * Update users from roles_admin_showusers
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/* Update users from roles_admin_showusers
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_updatestate()
{

    // Get parameters
    if (!xarVarFetch('status',      'int:0:', $data['status'],   NULL,    XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('state',       'int:0:', $data['state'],    0,       XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('groupuid',    'int:0:', $data['groupuid'], 1,       XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('updatephase', 'str:1:', $updatephase,      'update',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uids',        'isset',  $uids,             NULL,    XARVAR_NOT_REQUIRED)) return;
    // Security Check

    $data['authid'] = xarSecGenAuthKey('roles');
    // invalid fields (we'll check this below)
    // check if the username is empty
    //Note : We should not provide xarML here. (should be in the template for better translation)
    //Might be additionnal advice about the invalid var (but no xarML..)
    if (!isset($uids)) {
       $invalid = xarML('You must choose the users to change their state');
    }
     if (isset($invalid)) {
        // if so, return to the previous template
        return xarResponseRedirect(xarModURL('roles','admin', 'showusers',
                             array('authid'  => $data['authid'],
                                   'state'   => $data['state'],
                                   'invalid' => $invalid,
                                   'uid'     => $data['groupuid'])));
    }
    //Get the notice message
    switch ($data['status']) {
        case ROLES_STATE_INACTIVE :
            $mailtype = 'deactivation';
        break;
        case ROLES_STATE_NOTVALIDATED :
            $mailtype = 'validation';
        break;
        case ROLES_STATE_ACTIVE :
            $mailtype = 'welcome';
        break;
        case ROLES_STATE_PENDING :
            $mailtype = 'pending';
        break;
        default:
            $mailtype = 'blank';
        break;
    }

    if ( (!isset($uids)) || (!isset($data['status']))
                         || (!is_numeric($data['status']))
                         || ($data['status'] < 1)
                         || ($data['status'] > 4) )       {

        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)','parameters', 'admin', 'updatestate', 'Roles');
        throw new BadParameterException(null,$msg);
    }
    $roles     = new xarRoles();
    $uidnotify = array();
    foreach ($uids as $uid => $val) {
        //Check for privilege to edit role
        if ( !xarSecurityCheck('EditGroupRoles',0,'Group',$data['groupuid'])  && !xarSecurityCheck('EditRole',0,'Roles',$uid)
        ) return xarResponseForbidden();

        //check if the user must be updated :
        $role = $roles->getRole($uid);
        if ($role->getState() != $data['status']) {
            if ($data['status'] == ROLES_STATE_NOTVALIDATED) $valcode = xarMod::apiFunc('roles','user','makepass');
            else $valcode = null;
            //Update the user
            if (!xarMod::apiFunc('roles', 'admin', 'stateupdate',
                              array('uid'     => $uid,
                                    'groupuid' => $data['groupuid'],
                                    'state'   => $data['status'],
                                    'valcode' => $valcode))) return;
            $uidnotify[$uid] = 1;
        }
    }
    $uids = $uidnotify;
    // Success
     if ((!xarModGetVar('roles', 'ask'.$mailtype.'email')) || (count($uidnotify) == 0)) {
            xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',
                          array('uid' => $data['groupuid'], 'state' => $data['state'])));
            return true;
     }
     else {
        xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                          array('uid' => $uids, 'mailtype' => $mailtype, 'groupuid' => $data['groupuid'], 'state' => $data['state'])));
     }
}
?>