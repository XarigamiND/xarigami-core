<?php
/**
 * Remove a privilege from a privilege
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * removeMember - remove a privilege from a privilege
 *
 * Remove a privilege as a member of another privilege.
 * This is an action page..
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @access  public
 * @param   int childid
 * @param   int parentid
 * @return  boolean true on success
 * @throws  none
 * @todo    none
 */
function privileges_adminapi_removemember($args)
{
    extract($args);
    //Do nothing if the params aren't there
    if(!isset($childid) || !isset($parentid)) return true;

// call the Privileges class and get the parent and child objects
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($parentid);
    $member = $privs->getPrivilege($childid);

// assign the child to the parent and bail if an error was thrown
    if (!$priv->removeMember($member)) {return;}
 xarLogMessage('PRIVILEGES: A privilege '.$childid.' was removed from parent id '.$parentid.' by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);

// set the session variable
    xarSession::setVar('privileges_statusmsg', xarML('Removed from Privilege',
                    'privileges'));
    return true;
}

?>