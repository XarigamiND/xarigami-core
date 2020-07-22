<?php
/**
 * Delete a realm
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * deleteRealm - delete a realm
 * prompts for confirmation
 */
function privileges_admin_deleterealm()
{
    if (!xarVarFetch('rid',          'isset', $rid,          NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('confirmed', 'isset', $confirmed, NULL, XARVAR_DONT_SET)) return;

    $xartable = &xarDB::$tables;
    $q = new xarQuery('SELECT',$xartable['security_realms']);
    $q->addfields(array('xar_rid AS rid','xar_name AS name'));
    $q->eq('xar_rid', $rid);
    if(!$q->run()) return;
    $result = $q->row();
    $name = $result['name'];
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
// Security Check
    if(!xarSecurityCheck('DeletePrivilege',0,'Realm',$name)) return xarResponseForbidden();

    if (empty($confirmed)) {
        $data['authid'] = xarSecGenAuthKey('privileges');
        $data['rid'] = $rid;
        $data['name'] = $name;
        return $data;
    }

// Check for authorization code
    if (!xarSecConfirmAuthKey('privileges')) return;

    $q = new xarQuery('DELETE',$xartable['security_realms']);
    $q->eq('xar_rid', $result['rid']);
    if(!$q->run()) {
     $msg = xarML('The realm "#(1)" could not be removed due to a database problem.', $name);
     xarTplSetMessage($msg,'error');
    } else {
        // Hmm... what do we do about hooks?
        //xarMod::callHooks('item', 'delete', $pid, '');
        $msg = xarML('Realm "#(1)" has been deleted.', $name);
                 xarTplSetMessage($msg,'status');
        xarLogMessage('PRIVILEGES: A realm with RID '.$rid.' and name '.$name.' was deleted by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    }
// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'viewrealms'));
}

?>
