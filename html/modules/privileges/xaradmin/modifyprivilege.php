<?php
/**
 * Modify privilege details
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
 * modifyprivilege - modify privilege details
 *
 * @param int pid
 * @param string pname
 * @param int prealm
 * @param int pmodule
 * @param int pcomponent
 * @param int poldcomponent
 * @param int ptype
 * @param int plevel
 * @param int pinstance
 * @return array
 */
function privileges_admin_modifyprivilege()
{

    if(!xarVarFetch('pid',           'isset', $pid,          NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pname',         'isset', $name,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prealm',        'isset', $realm,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pmodule',       'isset', $module,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pcomponent',    'isset', $component,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('poldcomponent', 'isset', $oldcomponent, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptype',         'isset', $type,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('plevel',        'isset', $level,        NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pinstance',     'array', $instance,     array(), XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('pdescription',  'isset', $description,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pparentid',     'isset', $pparentid,    NULL, XARVAR_DONT_SET)) {return;}

// Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('EditPrivilege',0)) return xarResponseForbidden();

//Call the Privileges class and get the privilege to be modified
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                           'parentname'=>$parent->getName());
    }

// remove duplicate entries from the list of privileges
//Get the array of all privileges, minus the current one
// need this for the dropdown display
    $privileges = array();
    $names = array();
    foreach($privs->getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names) && $temp['pid'] != $pid){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }

// Load Template
    if(isset($pid)) {$data['ppid'] = $pid;}
    else {$data['ppid'] = $priv->getID();}

    if(empty($name)) $name = $priv->getName();
    $data['pname'] = $name;

    // Security Check
    $data['frozen'] = !xarSecurityCheck('EditPrivilege',0,'Privileges',$name);

    if(isset($realm)) {
        $data['prealm'] = $realm;
    } else {
        $data['prealm'] = $priv->getRealm();
    }

    if(isset($module)) {
        $data['pmodule'] = strtolower($module);
    } else {
        $data['pmodule'] = $priv->getModule();
    }

    if(isset($component)) {
        $data['pcomponent'] = $component;
    } else {
        $data['pcomponent'] = $priv->getComponent();
    }

    if(isset($level)) {
        $data['plevel'] = $level;
    } else {
       $data['plevel'] = $priv->getLevel();
    }
    if (isset($description)) {
        $data['pdescription'] = $description;
    } else {
        $data['pdescription'] = $priv->getDescription();
    }

    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);
    $numInstances = count($instances); // count the instances to use in later loops

    if(count($instance) > 0) {
        $default = $instance;
    } else {
        $default = array();
        $inst = $priv->getInstance();
        if ($inst == "All") for($i=0; $i < $numInstances; $i++) $default[] = "All";
        else $default = explode(':',$priv->getInstance());
    }

// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
//    xarResponseRedirect($instances['target'] . "&extpid=$pid&extname=$name&extrealm=$realm&extmodule=$module&extcomponent=$component&extlevel=$level");
//        return;
        $data['target'] = $instances['target'] . '&amp;extpid='.$data['ppid'].'&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.$data['pmodule'].'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'];
        $data['target'] .= '&amp;extinstance=' . urlencode(join(':',$default));
        $data['curinstance'] = join(':',$default);
        $data['instances'] = array();
    } else {
        for ($i=0; $i < $numInstances; $i++) {
            if($component == ''|| ($component == $oldcomponent)) {
                $instances[$i]['default'] = $default[$i];}
            else {
                $instances[$i]['default'] = '';}
            }
        $data['instances'] = $instances;
    }

    if(isset($type)) {$data['ptype'] = $type;}
    else {$data['ptype'] = $priv->isEmpty() ? "empty" : "full";}

    if(isset($show)) {$data['show'] = $show;}
    else {$data['show'] = 'assigned';}

    sys::import('modules.privileges.xartreerenderer');
    $renderer = new xarTreeRenderer();
    $levels = xarMod::apiFunc('privileges','admin','getlevels');
    $levelvalues = array();
    foreach ($levels as $k=>$v) {
        $levelvalues[$k] = xarVarPrepForDisplay($v['shortdesc']);
    }
    $data['levelvalues'] = $levelvalues;
    $data['tree'] = $renderer->drawtree($renderer->maketree($priv));
    $data['oldcomponent'] = $component;
    $data['authid'] = xarSecGenAuthKey();
    $data['parents'] = $parents;
    $data['privileges'] = $privileges;
    $data['realms'] = $privs->getrealms();
    $data['modules'] = $privs->getmodules();
    $data['components'] = $privs->getcomponents($data['pmodule']);
    $data['refreshlabel'] = xarML('Refresh');
     xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Modify Privilege #(1)',$data['pname'])));
    //common admin menu
     $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    return $data;
}

?>