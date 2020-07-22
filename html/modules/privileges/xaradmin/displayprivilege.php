<?php
/**
 * Displayprivilege - display privilege details
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
 *displayprivilege - display privilege details
 */
function privileges_admin_displayprivilege()
{
// Security Check
    if(!xarSecurityCheck('EditPrivilege',0)) return xarResponseForbidden();

    if(!xarVarFetch('pid',           'isset', $pid,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',     'array', $instance,   array(), XARVAR_NOT_REQUIRED)) {return;}

//Call the Privileges class and get the privilege to be modified
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                                    'parentname'=>$parent->getName());
    }

// Load Template
    if(isset($pid)) {$data['ppid'] = $pid;}
    else {$data['ppid'] = $priv->getID();}

    sys::import('modules.privileges.xartreerenderer');
    $renderer = new xarTreeRenderer();

    $data['tree'] = $renderer->drawtree($renderer->maketree($priv));
    $data['pname'] = $priv->getName();
    $data['prealm'] = $priv->getRealm();
    $data['pmodule'] = $priv->getModule();
    $data['pcomponent'] = $priv->getComponent();
    $data['plevel'] = $priv->getLevel();
    $data['pdescription'] = $priv->getDescription();
    $levellist = xarMod::apiFunc('privileges','admin','getlevels');
    $levelvalues = array();
    foreach ($levellist as $k=>$v) {
        $levelvalues[$k] = xarVarPrepForDisplay($v['shortdesc']);
    }
    $data['levelvalues'] = $levelvalues;
    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);
    $numInstances = count($instances); // count the instances to use in later loops

    $default = array();
    $data['instance'] = $priv->getInstance();

    $data['ptype'] = $priv->isEmpty() ? "empty" : "full";
    $data['parents'] = $parents;
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    return $data;
}

?>