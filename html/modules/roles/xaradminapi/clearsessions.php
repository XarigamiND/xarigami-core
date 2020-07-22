<?php
/**
 * Delete a group & info
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */

/* deletegroup - delete a group & info
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid']
 * @return true on success, false otherwise
 */
function roles_adminapi_clearsessions($spared)
{
    // Security Check
    if(!xarSecurityCheck('AdminRole',0)) return xarResponseForbidden();

    if(!isset($spared)) {
        $msg = xarML('Wrong arguments to groups_adminapi_clearsessions');
                throw new BadParameterException(null,$msg);
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $sessionstable = $xartable['session_info'];
    $roles = new xarRoles();
    $lockdata=unserialize(xarModGetVar('roles', 'lockdata'));
    $killactive = $lockdata['killactive'];

    $query = "SELECT xar_sessid, xar_uid FROM $sessionstable";
    $result = $dbconn->Execute($query);
    if (!$result) return;

    for (; !$result->EOF; $result->MoveNext()) {
        list($thissession, $thisuid) = $result->fields;
        foreach ($spared as $uid) {
            $thisrole = $roles->getRole($thisuid);
            $thatrole = $roles->getRole($uid);
            if (($thisuid == $uid) || ($thisrole->isParent($thatrole)) || ($thisuid ==_XAR_ID_UNREGISTERED)) {
            } else {
                if ($killactive) {
                  $query = "DELETE FROM $sessionstable
                          WHERE xar_uid = ?";
                          if (!$dbconn->Execute($query,array($thisuid))) return;
                }
            }
        }
    }

    return true;
}

?>