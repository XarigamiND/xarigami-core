<?php
/**
 * Create a new realm
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * addRealm - create a new realm
 * Takes no parameters
 */
function privileges_admin_newrealm()
{
    $data = array();

    if (!xarVarFetch('name',      'str:1:20', $name,      '',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('confirmed', 'bool', $confirmed, false, XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('AddPrivilege',0,'Realm')) return xarResponseForbidden();

    if ($confirmed) {
        if (!xarSecConfirmAuthKey()) return;

        $xartable = &xarDB::$tables;
        $q = new xarQuery('SELECT',$xartable['security_realms'],'xar_name');
        $q->eq('xar_name', $name);
        if(!$q->run()) return;

        if ($q->getrows() > 0) {
            $msg = xarML('There is already a realm with the name "#(1)"', $name);
             xarTplSetMessage($msg,'error');
              xarResponseRedirect(xarModURL('privileges', 'admin', 'newrealm'));
             //throw new BadParameterException(null,$msg);
        }

        $q = new xarQuery('INSERT',$xartable['security_realms']);
        $q->addfield('xar_name', $name);
        if(!$q->run()) return;

        //Redirect to view page
         $msg = xarML('Realm "#(1)" added successfully.', $name);
             xarTplSetMessage($msg,'status');
        xarResponseRedirect(xarModURL('privileges', 'admin', 'viewrealms'));
    }
    $data['showrealms'] = xarModGetVar('privileges', 'showrealms');
    $data['authid'] = xarSecGenAuthKey('privileges');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    return $data;
}


?>