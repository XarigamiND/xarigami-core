<?php
/**
 * AddMember
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
 * addMember - assign a privilege as a member of another privilege
 *
 * Make a privilege a member of another privilege.
 * This is an action page..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   none
 * @return  none
 * @throws  none
 * @todo    none
 */
function privileges_admin_addmember()
{

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    if(!xarVarFetch('ppid',   'isset', $pid   , NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('privid', 'isset', $privid, NULL, XARVAR_DONT_SET)) {return;}

    if (empty($pid) || empty($privid)) {
        xarResponseRedirect(xarModURL('privileges',
                                      'admin',
                                      'modifyprivilege',
                                      array('pid'=>$pid)));
        return true;
    }

// call the Privileges class and get the parent and child objects
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);
    $member = $privs->getPrivilege($privid);

// we bail if there is a loop: the child is already an ancestor of the parent
    $found = false;
    $descendants = $member->getDescendants();
    foreach ($descendants as $descendant) if ($descendant->getID() == $priv->getID()) $found = true;
    $privname = $priv->getName();
    if ($found) {
        $msg = xarML("The privilege you are trying to assign to is already a component member of that Privilege.");
        xarTplSetMessage($msg,'error');
        xarResponseRedirect(xarModURL('privileges', 'admin','modifyprivilege', array('pid'=>$pid)));
    }

// assign the child to the parent and bail if an error was thrown
// we bail if the child is already a member of the *parent*
// if the child was a member of an ancestor further up that would be OK.
    $found = false;
    $children = $priv->getChildren();
    foreach ($children as $child) if ($child->getID() == $member->getID()) $found = true;
    if (!$found) if (!$priv->addMember($member)) {return;}
     $msg = xarML("The privilege '#(1)' was successfully assigned to member #(2).",$privname,$privname);
     xarTplSetMessage($msg,'status');
// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin','modifyprivilege',
                             array('pid'=>$pid)));
}
?>
