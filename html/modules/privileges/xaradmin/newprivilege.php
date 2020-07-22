<?php
/**
 * Create a new privilege
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
 * newPrivilege - create a new privilege
 * Takes no parameters
 */
function privileges_admin_newprivilege()
{
    $data = array();

    if (!xarVarFetch('pid',         'isset', $data['pid'],          '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pname',       'isset', $data['pname'],        '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pparentid',   'isset', $data['pparentid'],    '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('prealm',      'isset', $data['prealm'],       'All',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pmodule',     'isset', $module,               NULL,       XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pcomponent',  'isset', $data['pcomponent'],   'All',      XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pinstance',   'isset', $data['pinstance'],    '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('plevel',      'isset', $data['plevel'],       '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('ptype',       'isset', $data['ptype'],        '',         XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('show',        'isset', $data['show'],         'assigned', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('trees',       'isset', $trees,                NULL,       XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pdescription','isset', $data['pdescription'], '',         XARVAR_NOT_REQUIRED)) {return;}

    if ($module !== NULL) {$data['pmodule'] = strtolower($module);}
    else {$data['pmodule'] = 'All';}

// Clear Session Vars
    xarSession::delVar('privileges_statusmsg');

// Security Check
    if(!xarSecurityCheck('AddPrivilege',0)) return xarResponseForbidden();

// call the Privileges class
    $privs = new xarPrivileges();

// remove duplicate entries from the list of privileges
    $privileges = array();
    $names = array();
    $privileges[] = array('pid' => 0,
                            'name' => '');
    foreach($privs->getprivileges() as $temp){
        $nam = $temp['name'];
        if (!in_array($nam,$names)){
            $names[] = $nam;
            $privileges[] = $temp;
        }
    }
    $privlist = array();
    foreach ($privileges as $k=>$v) {
        $privlist[$v['pid']] =$v['name'];
    }
    //Load Template
    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);

// send to external wizard if necessary
    if (!empty($instances['external']) && $instances['external'] == "yes") {
//    xarResponseRedirect($instances['target'] . "&extpid=0&extname=$name&extrealm=$realm&extmodule=$module&extcomponent=$component&extlevel=$level");
//        return;
        //$data['target'] = $instances['target'] . '&amp;extpid=0&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.$data['pmodule'].'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'];
        $data['target'] = $instances['target'] . '&amp;extpid=0&amp;extname='.$data['pname'].'&amp;extrealm='.$data['prealm'].'&amp;extmodule='.$data['pmodule'].'&amp;extcomponent='.$data['pcomponent'].'&amp;extlevel='.$data['plevel'].'&amp;extdescription='.$data['pdescription'];
        $data['target'] .= '&amp;pparentid=' . $data['pparentid'];
        $data['instances'] = array();
    } else {
        $data['instances'] = $instances;
    }
    $data['ptypeoptions'] = array(
                        array('id'=>'empty','name'=>xarML('Empty'),'title'=>xarML('This privilege will have no children')),
                        array('id'=>'full','name'=>xarML('With rights'),'title'=>xarML('This privilege will have children')),
                        );
    $data['ptype'] = (isset($data['ptype']) && !empty($data['ptype']))?$data['ptype'] : 'full';
    $data['authid'] = xarSecGenAuthKey();
    $data['realms'] = $privs->getrealms();
    $realmslist = array();
    foreach($data['realms'] as $k=>$v){
        $realmslist[$v['rid']] = $v['name'];
    }
    $data['realmslist']= $realmslist;

    $data['modules'] = $privs->getmodules();
    foreach($data['modules'] as $k=>$v) {
        $modlist[$v['name']] = $v['display'];
    }
    $data['modlist']  = $modlist;
    $data['privlist'] = $privlist;

    $data['showrealms']= xarModGetVar('privileges','showrealms');
    $data['components'] = $privs->getcomponents($data['pmodule']);
    foreach($data['components'] as $k=>$v) {
        $complist[$v['name']] = $v['name'];
    }
    $data['complist']=$complist;

    $levelvalues = xarMod::apiFunc('privileges','admin','getlevels');
    $levels = array();
    foreach ($levelvalues as $k=>$v) {
        $levels[$k] = xarVarPrepForDisplay($v['shortdesc']);
    }
    $data['levels'] = $levels;
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('privileges','admin','getmenulinks');
    return $data;
}


?>