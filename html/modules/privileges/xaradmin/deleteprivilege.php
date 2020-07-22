<?php
/**
 * DeletePrivilege - delete a privilege
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * deletePrivilege - delete a privilege
 * prompts for confirmation
 */
function privileges_admin_deleteprivilege()
{
    if (!xarVarFetch('pid',          'isset', $pid,          NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmation', 'isset', $confirmation, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('returnurl', 'isset', $returnurl,'', XARVAR_DONT_SET)) return;


//Call the Privileges class and get the privilege to be deleted
    $privs = new xarPrivileges();
    $priv = $privs->getprivilege($pid);
    $name = $priv->getName();

// Security Check
    if(!xarSecurityCheck('DeletePrivilege',0,'Privileges',$name)) return xarResponseForbidden();
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    if (empty($confirmation)) {

//Get the array of parents of this privilege
        $parents = array();
        foreach ($priv->getParents() as $parent) {
            $parents[] = array('parentid'=>$parent->getID(),
                                        'parentname'=>$parent->getName());
        }
        //Load Template
        $data['authid'] = xarSecGenAuthKey('privileges');
        $data['pid'] = $pid;
        $data['pname'] = $name;
        $data['parents'] = $parents;
        $data['returnurl'] = !empty($returnurl)?$returnurl:xarModURL('privileges','admin','viewprivileges');
        return $data;

    }

// Check for authorization code
    if (!xarSecConfirmAuthKey('privileges')) return;

//Try to remove the privilege and bail if an error was thrown
    if (!$priv->remove()) return;

    xarMod::callHooks('item', 'delete', $pid, '');
     xarLogMessage('PRIVILEGES: A privilege with PID '.$pid.' and name '.$name.' was deleted by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    $msg = xarML('The privilege "#(1)" was successfully removed.',$name);
    xarTplSetMessage($msg,'status');
// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'viewprivileges'));
}

?>
