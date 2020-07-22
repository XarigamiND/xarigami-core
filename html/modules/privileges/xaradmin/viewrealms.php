<?php
/**
 * View the defined realms
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
 * viewRealms - view the defined realms
 * Takes no parameters
 */
function privileges_admin_viewrealms()
{
    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Security Check
    if(!xarSecurityCheck('ViewPrivileges',0,'Realm')) return xarResponseForbidden();

    $xartable = &xarDB::$tables;
    $q = new xarQuery('SELECT',$xartable['security_realms']);
    $q->addfields(array('xar_rid AS rid', 'xar_name AS name'));
    $q->setorder('xar_name');
    if(!$q->run()) return;

    $data['realms'] = $q->output();
    $data['showrealms'] = xarModGetVar('privileges', 'showrealms');    
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');           
    
    return $data;
}


?>