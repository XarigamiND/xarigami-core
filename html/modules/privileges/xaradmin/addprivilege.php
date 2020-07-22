<?php
/**
 * AddPrivilege - add a privilege to the repository
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
 * addPrivilege - add a privilege to the repository
 * This is an action page
 */
function privileges_admin_addprivilege()
{
    if(!xarSecurityCheck('AddPrivilege',0)) return xarResponseForbidden();

    if(!xarVarFetch('pname',      'isset', $pname,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',     'isset', $prealm,     'All', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',    'isset', $pmodule,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pcomponent', 'isset', $pcomponent, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',      'isset', $type,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',     'isset', $plevel,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pparentid',  'isset', $pparentid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',  'array', $pinstances, array(), XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pdescription', 'isset', $pdescription, NULL,    XARVAR_DONT_SET)) {return;}

    $instance = "";
    foreach ($pinstances as $pinstance) {
        $instance .= $pinstance . ":";
    }
    if ($instance =="") {
        $instance = "All";
    }
    else {
        $instance = substr($instance,0,strlen($instance)-1);
    }

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    if ($type =="empty") {

// this is just a container for other privileges
        $pmodule = 'empty';
        $plevel = '0';
        $pparentid = 'All';
        $pargs = array('name' => $pname,
                    'realm' => 'All',
                    'module' => 'empty',
                    'component' => 'All',
                    'instance' => 'All',
                    'level' => 0,
                    'description' => $pdescription,
                    'parentid' => 'All',
                    );
    }
    else {

// this is privilege has its own rights assigned
        $pargs = array('name' => $pname,
                    'realm' => $prealm,
                    'module' => $pmodule,
                    'component' => $pcomponent,
                    'instance' => $instance,
                    'level' => $plevel,
                    'description' => $pdescription,
                    'parentid' => $pparentid,
                    );
    }

//Call the Privileges class
    $priv = new xarPrivilege($pargs);

//Try to add the privilege and bail if an error was thrown
    if (!$priv->add()) {
        //error message is raised at point of error in add
      xarResponseRedirect(xarModURL('privileges', 'admin', 'newprivilege'));
        return;
    }
    $msg = xarML('Privilege "#(1)" was successfully added',$pname);
     xarLogMessage('PRIVILEGES: A new privilege with name '. $pname.', module '. $pmodule.' and level '.$plevel.' was added to parent '.$pparentid.' by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);

    xarSession::setVar('privileges_statusmsg', xarML('Privilege Added', 'privileges'));

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'newprivilege'));
}

?>