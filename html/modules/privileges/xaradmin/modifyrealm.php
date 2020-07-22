<?php
/**
 * Modify an existing realm
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
 * modifyRealm - modify an existing realm
 * @param rid the id of the realm to be modified
 */
function privileges_admin_modifyrealm()
{
    // Security Check
    if(!xarSecurityCheck('EditPrivilege',0,'Realm')) return;

    if (!xarVarFetch('rid',       'int', $rid,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirmed', 'bool', $confirmed, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('name',      'str:1.20', $name,      '',      XARVAR_NOT_REQUIRED)) {return;}
    $xartable = &xarDB::$tables;

    if ($confirmed !== TRUE) {
        $q = new xarQuery('SELECT',$xartable['security_realms']);
        $q->addfields(array('xar_rid AS rid','xar_name AS name'));
        $q->eq('xar_rid', $rid);
        if(!$q->run()) return;
        $result = $q->row();
        if ($result)
        $name = $result['name'];

    } else {
        if (!xarVarFetch('newname',   'str:1.20',$newname, '',XARVAR_NOT_REQUIRED)) {return;}

        if (!xarSecConfirmAuthKey()) return xarResponseForbidden();
        $q = new xarQuery('SELECT',$xartable['security_realms'],'xar_name');
        $q->eq('xar_name', $newname);
        if(!$q->run()) return;

        if ($q->getrows() > 0 && (strtolower($newname) !=strtolower($name))) {
            $msg = xarML('There is already a realm with the name #(1)', $newname);
            xarTplSetMessage($msg,'error');
        }

        $q = new xarQuery('UPDATE',$xartable['security_realms']);
        $q->addfield('xar_name', $newname);
        $q->eq('xar_rid', $rid);
        if(!$q->run()) return;
        $msg = xarML('Realm "#(1)" was successfully modified.', $name);
        xarTplSetMessage($msg,'status');
        xarLogMessage('PRIVILEGES: A realm with RID '.$rid.' and original name '.$name.' was modified by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
        xarResponseRedirect(xarModURL('privileges', 'admin', 'viewrealms'));
    }

    $data['rid'] = $rid;
    $data['name'] = $name;
    $data['newname'] = '';
    $data['authid'] = xarSecGenAuthKey('privileges');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    return $data;
}


?>