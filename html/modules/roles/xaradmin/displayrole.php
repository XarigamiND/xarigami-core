<?php
/**
 * Display role
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * display user
 */
function roles_admin_displayrole()
{
    if (!xarVarFetch('uid','int:1:',$uid)) return;
    if (!xarVarFetch('groupuid',  'str:1:', $groupuid,  '', XARVAR_NOT_REQUIRED)) return;

    $roles = new xarRoles();
    $role  = $roles->getRole($uid);
    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    $allowedgids = array();
    foreach ($role->getParents() as $parent) {
        $parentid = $parent->getID();
        $parents[] = array('parentid'    => $parentid,
                           'parentname'  => $parent->getName(),
                           'parentuname' => $parent->getUname());
        if  (xarSecurityCheck('EditGroupRoles',0,'Group',$parentid) || xarSecurityCheck('EditRole',0,'Roles',$parentid)) {
            $allowedgids[] = $parentid;
        }
    }
    //got to do something economical here these privs are killers
    if (!isset($groupid)) {
        $groupuid = isset($allowedgids[0]) ?$allowedgids[0]:current($allowedgids);
    }
    $data['groupuid']= $groupuid;
    $data['parents'] = $parents;
    $name = $role->getName();
    //cleanup old session vars
    xarSession::delVar("roles.modifyuser");
    // Security Check
    $data['frozen']= FALSE;
    $ingroup = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$allowedgids,'uid'=>$uid));
    if  (!xarSecurityCheck('EditRole',0,'Roles',$uid) && !$ingroup ){
        $data['frozen'] = xarSecurityCheck('ReadRole',0,'Roles',$uid);
        return xarResponseForbidden();
    }

    //if (!xarSecurityCheck('EditRole',1,'Roles',$uid)) return;

    $data['uid']   = $role->getID();
    $data['type']  = $role->getType();
    $data['ptype'] = $data['type'];
    $data['name']  = $name;
    $data['phome'] = $role->getHome();
    $data['roletype'] = $data['type']; //by default user
    if (xarModGetVar('roles','setprimaryparent')) { //we have activated primary parent
        $primaryparent = $role->getPrimaryParent();
        $prole = xarUFindRole($primaryparent);
        $data['primaryparent']  = $primaryparent;
        $data['pprimaryparent'] = $prole->getID();//pass in the uid
        if (!isset($data['phome']) || empty ($data['phome'])) {
            $parenthome         = $prole->getHome(); //get the primary parent home
            $data['parenthome'] = $parenthome;
        }
    } else {
        $data['parenthome']     = '';
        $data['pprimaryparent'] = '';
        $data['primaryparent']  = '';
    }
    //get the data for a user
    if ($data['type'] == 0) {
        $data['uname']   = $role->getUser();
        $data['type']    = $role->getType();
        $data['email']   = $role->getEmail();
        $data['state']   = $role->getState();
        $data['valcode'] = $role->getValCode();
    } else {
        //get the data for a group

    }
    if (xarModGetVar('roles','setuserlastlogin')) {
        //only display it for current user or admin
        if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) {
            $data['userlastlogin'] = xarSession::getVar('roles_thislastlogin');
        }elseif (xarSecurityCheck('AdminRole',0,'Roles',$uid) && xarModUserVars::get('roles','userlastlogin',$uid)<>''){
            $data['userlastlogin'] = xarModUserVars::get('roles','userlastlogin',$uid);
        }else{
            $data['userlastlogin']='';
        }
    }else{
        $data['userlastlogin']='';
    }

    $data['upasswordupdate'] = xarModUserVars::get('roles','passwordupdate',$uid);
    //initialize
    $data['offset']    = '';
    $data['utimezone'] = '';
    //timezone
    if (xarModGetVar('roles','setusertimezone')) {
        $usertimezone      = $role->getUserTimezone();
        $usertimezone      = unserialize($usertimezone);
        $data['utimezone'] = $usertimezone['timezone'];
        $offset            = $usertimezone['offset'];
        if (isset($offset)) {
            $hours = intval($offset);
            if ($hours != $offset) {
                $minutes = abs($offset - $hours) * 60;
            } else {
                $minutes = 0;
            }
            if ($hours > 0) {
                $data['offset'] = sprintf("%+d:%02d",$hours,$minutes);
            } else {
                $data['offset'] = sprintf("%+d:%02d",$hours,$minutes);
            }
        }
    }

    $properties = array();
    $propertieslist = array();
   if (xarMod::isHooked('dynamicdata','roles')) {

        $objectinfo= xarMod::apiFunc('dynamicdata','user','getitem',
                            array(  'module'=>'roles',
                                    'itemtype'=>$data['type'],
                                    'itemid'=>$uid,
                                    'getobject'=>1,
                                    ));
        if ($objectinfo) {
            $propertieslist = $objectinfo->getProperties();
            foreach($propertieslist as $name=>$info) {
                //do not show hidden properties or input only properties - we need to do this ourselves here
                if (($info->status !=Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN) &&
                    ($info->status !=Dynamic_Property_Master::DD_DISPLAYSTATE_INPUTONLY)) {
                    $properties[$name]= $propertieslist[$name];
                }
            }
        }
    }

    $data['properties'] = $properties;
    $data['propertieslist'] = $propertieslist;     //all properties

    $isonline = xarMod::apiFunc('roles','user','getallactive', array('uid'=>$uid));
    $isonline = current($isonline);
    $data['showonline'] = xarUserGetVar('showonline',$uid);
    if (is_array($isonline) && $isonlinle['uid'] = $uid) {
        $data['isonline'] = true;
    } else {
        $data['isonline'] = false;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');
    $item = $data;
    $item['module']    = 'roles';
    $item['itemtype']  = $data['type']; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));
    $hooks = array();
    $hooks = xarMod::callHooks('item', 'display', $uid, $item);
    $data['hooks'] = $hooks;
    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    return $data;
}
?>
