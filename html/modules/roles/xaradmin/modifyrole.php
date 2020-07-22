<?php
/**
 * Modify role details
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * modifyrole - modify role details
 *
 * @author Xarigami Core Development Team
 * @param int uid
 * @param string pname The display name
 * @param string ptype
 * @param string puname The user name
 * @param string ppass
 * @param string state
 * @param string phome The user home
 * @param int pprimaryparent
 * @param string utimezone The user timezone
 * @return array
 */
function roles_admin_modifyrole()
{
    $sessionguid = xarSession::getVar('roles.groupuid')? xarSession::getVar('roles.groupuid'):0;
    if (!xarVarFetch('uid',      'int:1:',    $uid)) return;
    if (!xarVarFetch('pname',    'str:1:',    $name, '',     XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pparentid', 'str:1:',   $pparentid, $sessionguid ,     XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptype',    'str:1',     $type,  NULL,  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('puname',   'str:1:35:', $uname, '',    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail',   'str:1:',    $email, '',    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass',    'str:1:',    $pass,  '',    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state',    'str:1:',    $state, '',    XARVAR_DONT_SET)) return;
    if (!xarVarFetch('phome',    'str',       $data['phome'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pprimaryparent', 'int', $data['primaryparent'], '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('utimezone','str:1:',    $utimezone,'', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl',      'str',       $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('invalid',       'array',    $invalid, array(),      XARVAR_NOT_REQUIRED)) return; // for group return on invalid

    $data['parentid'] = isset($pparentid)? $pparentid: 0;
    // Call the Roles class and get the role to modify
    $roles = new xarRoles();
    $uid  = (int)$uid;
    $role = $roles->getRole($uid);
    $roletype= $role->type;
    // get the array of parents of this role
    //Are we allowed to edit
    //special groups and users
    $anon = _XAR_ID_UNREGISTERED;
    $everybody = xarModGetVar('roles','everybody');
    $myselfinfo = xarMod::apiFunc('roles','user','get',array('uname'=>'myself'));
    $myself = $myselfinfo['uid'];
    $administrators = xarUFindRole('Administrators');
    $adminguid = $administrators->uid;

    $noedit= array($anon,$everybody,$myself,$adminguid);
    $data['noedit'] = $noedit;
    if (in_array($uid,$noedit)) {

        return xarTplModule('roles','user','errors',array('errortype' => 'role_required', 'var1' => $role->getName()));

    }
    $data['frozen']= false;
    $parents = array();
    $names = array();
    $allowedgroups = array();
    $allowedgids = array();

    $parentroles = $role->getParents();
    foreach ($parentroles as $parent) {
         $puid = $parent->getID();

        if (xarSecurityCheck('ModerateGroupRoles',0,'Group',$puid) || xarSecurityCheck('ModerateRole',0,'Roles',$puid)) {
            $allowedgroups[$puid] = $parent->getName();
            $allowedgids[] = $puid;
        } else {
            return xarResponseForbidden();
          //  $data['frozen'] = !xarSecurityCheck('EditRole',0,'Roles',$puid);
          //  throw new BadParameterException('regid',$msg);
          //  return;
        }
        if(xarSecurityCheck('RemoveRole',0,'Relation', $puid . ":" . $uid) || in_array($puid,$allowedgids)) {
            $parents[] = array('parentid'    => $puid,
                               'parentname'  => $parent->getName(),
                               'parentuname' => $parent->getUname());
            $names[] = $parent->getName();
        }

    }
    $data['parents'] = $parents;
    $data['allowedgroups'] = $allowedgroups;
    //finally we can do the security check
    //role or group?
    //If we have normal Roles instance and $uid matches we can edit it
    //Else we need Admin level Group instance to edit a group role

    if ($roletype == ROLES_GROUPTYPE) {
        if (!xarSecurityCheck('EditRole',0,'Roles',$uid)) return xarResponseForbidden();;

    } else {
        $inallowedgroup = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$allowedgids,'uid'=>$uid));
        if (!$inallowedgroup  && !xarSecurityCheck('EditRole',0,'Roles',$uid)) {
            $msg = xarML('This role (#(1)) is a required system role and the  action you are trying to perform is not allowed.',$role->getName());
              return xarResponseForbidden($msg);
        }
    }

    // remove duplicate entries from the list of groups
    // get the array of all roles, minus the current one
    // need to display this in the template
    $groups = array();
    foreach($roles->getgroups() as $temp) {
        $nam = $temp['name'];
        if(!xarSecurityCheck('AttachRole',0,'Relation',$temp['uid']. ":" . $uid)
           && !xarSecurityCheck('ModerateGroupRoles',0,'Group',$temp['uid']) && !xarSecurityCheck('ModerateRole',0,'Roles',$temp['uid'])) continue;
            if (!in_array($nam, $names) && $temp['uid'] != $uid) {
                    $names[] = $nam;
                    $groups[] = array('duid'  => $temp['uid'],
                                  'dname' => $temp['name']);
             }
    }

    // Load Template
    if (empty($name)) $name = $role->getName();
    $data['pname'] = $name;

    if (isset($type)) {
        $data['ptype'] = $type;
    } else {
        $data['ptype'] = $roletype;
    }

     $data['roletype'] =  $roletype;

    if (!empty($uname)) {
        $data['puname'] = $uname;
    } else {
        $data['puname'] = $role->getUser();
    }

    if (!empty($phome)) {
        $data['phome'] = $phome;
    } else {
        $data['phome'] = $role->getHome();
    }
    //jojodee - this code is confusing - sometimes primary parent is int and sometimes string, very inconsistent
    //Let's decide - it is a string and just pass it's uid around for forms
    if (xarModGetVar('roles','setprimaryparent')) {
        if (isset($primaryparent) && !empty($primaryparent) && is_int($primaryparent)) { //we have a uid
            $data['pprimaryparent'] = $primaryparent;
        } else {
            $primaryparent = $role->getPrimaryParent(); //this is a string name
            $prole = xarUFindRole($primaryparent);
            $data['pprimaryparent'] = $prole->getID();//pass in the uid
        }
    } else {
        $data['pprimaryparent'] ='';
    }

    if (!empty($email)) {
        $data['pemail'] = $email;
    } else {
        $data['pemail'] = $role->getEmail();
    }

    if (isset($pstate)) {
        $data['pstate'] = $pstate;
    } else {
        $data['pstate'] = $role->getState();
    }
    if (xarModGetVar('roles','setpasswordupdate')) {
     $data['upasswordupdate']  = xarModUserVars::get('roles','passwordupdate',$uid);
    //     $data['upasswordupdate'] = $role->getPasswordUpdate();
    }else {
         $data['upasswordupdate'] ='';
    }

    if (xarModGetVar('roles','setusertimezone')) {
        $usertimezone= $role->getUserTimezone();
        $usertimezonedata =unserialize($usertimezone);
        $data['utimezone']=$usertimezonedata['timezone'];
    } else {
        $data['utimezone']='';
    }

    if (xarModGetVar('roles','setuserlastlogin')) {
        //only display it for current user or admin
        if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) {
            $data['userlastlogin']=xarSession::getVar('roles_thislastlogin');
        }elseif (xarSecurityCheck('AdminRole',0,'Roles',$uid) && xarUserGetVar('uid')!= $uid){
            $data['userlastlogin']= xarModUserVars::get('roles','userlastlogin',$uid);
        }else{
            $data['userlastlogin']='';
        }
    }else{
        $data['userlastlogin']='';
    }
    //handle DD hooks
    $data['properties'] =array();
    $propertyvalues= array();
    $withupload = (int) FALSE;

    if ($data['ptype'] == 0) {

        if (xarMod::isHooked('dynamicdata','roles',0)) {
          $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                        array('modid' => xarMod::getId('roles'),
                                              'itemtype'=> 0
                                             )
                                        );
           if (isset($object) && !empty($object->objectid)) {
               //We grab the existing properties here - not any new posted ones
                $object->getItem(array('itemid'=>$uid));
                $properties =& $object->getProperties();
                foreach ($properties as $name=>$property) {
                   // $invalid[$name] =  $property->invalid; //we don't want this - invalid will be passed in
                    $propertyvalues[$name] =   $property->value;
                }
            }
            if (isset($properties) && is_array($properties)) {
                foreach ($properties as $key => $prop) {
                    if (isset($prop->upload) && $prop->upload == TRUE) {
                        $withupload = (int) TRUE;
                    }
                }
            }
        }
        //always take the new posted vars, not the old ones, ensure we grab DD and other posted vars

    }
    $invalid = isset($invalid) ? $invalid : array();

     $data['invalid'] = $invalid;
    $data['propertyvalues'] = $propertyvalues;
    unset($properties);
     $data['withupload'] = $withupload;
    $data['ptypeoptions'] = array('1'=>xarML('Group'),'0'=>xarML('User'));
    $data['stateoptions'] = array(ROLES_STATE_INACTIVE => xarML('Inactive'),
                                  ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                                  ROLES_STATE_ACTIVE => xarML('Active'),
                                  ROLES_STATE_PENDING => xarML('Pending')
                            );
    $data['allowemail'] = xarModUserVars::get('roles', 'allowemail',$uid) ;

    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');

    // call item modify hooks (for DD etc.)
    $item = $data;
    $item['module']   = 'roles';
    $item['itemtype'] = $data['ptype']; // we might have something separate for groups later on
    $item['itemid']   = $uid;
    $item['phase']    = 'modifyrole';
    $data['hooks']    = xarMod::callHooks('item', 'modify', $uid, $item);
    unset($data['hooks']['dynamicdata']);
    $data['uid']      = $uid;
    $data['groups']   = $groups;
    $data['parents']  = $parents;
    $data['haschildren'] = $role->countChildren();
    $data['updatelabel'] = xarML('Update');
    $data['addlabel'] = xarML('Add');
    $data['authid']   = xarSecGenAuthKey('roles');
    $data['returnurl'] = $returnurl;
    $data['return_url'] = $returnurl; //for template compat
    return $data;
}

?>
