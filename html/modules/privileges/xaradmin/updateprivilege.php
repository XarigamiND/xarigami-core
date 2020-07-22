<?php
/**
 * Update a privilege
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
 * updateprivilege - update a privilege
 */
function privileges_admin_updateprivilege()
{
// Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

// Check for authorization code
    if (!xarSecConfirmAuthKey()) return;

    if(!xarVarFetch('pid',        'isset', $pid,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pname',      'isset', $name,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',     'isset', $realm,     'All', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pmodule',    'isset', $module,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pcomponent', 'isset', $component,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',      'isset', $type,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',     'isset', $level,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',  'array', $pinstance, array(), XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pdescription', 'isset', $description, NULL, XARVAR_DONT_SET)) {return;}

    $instance = "";
    foreach($pinstance as $part) $instance .= $part . ":";
    if ($instance =="") {
        $instance = "All";
    }
    else {
        $instance = substr($instance,0,strlen($instance)-1);
    }

// Security Check
    if(!xarSecurityCheck('EditPrivilege',0,'Privileges',$name)) return xarResponseForbidden();

// call the Privileges class and update the values

    if ($type =="empty") {

// this is just a container for other privileges
        $privs = new xarPrivileges();
        $priv = $privs->getPrivilege($pid);
        $priv->setName($name);
        $priv->setRealm('All');
        $priv->setModule('empty');
        $priv->setComponent('All');
        $priv->setInstance('All');
        $priv->setLevel(0);
        $priv->setDescription($description);
    }
    else {
        $privs = new xarPrivileges();
        $priv = $privs->getPrivilege($pid);
        $priv->setName($name);
        $priv->setRealm($realm);
        $priv->setModule($module);
        $priv->setComponent($component);
        $priv->setInstance($instance);
        $priv->setLevel($level);
        $priv->setDescription($description);
    }

//Try to update the privilege to the repository and bail if an error was thrown
    if (!$priv->update()) {return;}

    xarMod::callHooks('item', 'update', $pid, '');
        xarLogMessage('PRIVILEGES: A privilege with PID '.$pid.' and name '.$name.' was modified by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    $msg = xarML('Privilege "#(1)" was successfully updated.',$name);
    xarTplSetMessage($msg,'status');

// redirect to the next page
    xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyprivilege', array('pid' => $pid)));
}

?>