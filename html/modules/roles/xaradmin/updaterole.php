<?php
/**
 * Update a role
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * updaterole - update a role
 * this is an action page
 */
function roles_admin_updaterole()
{
 // Check for authorization code
 if (!xarSecConfirmAuthKey()) return;
    //sec checks moved down after check of role type
    $sessionguid = xarSession::getVar('roles.groupuid')? xarSession::getVar('roles.groupuid'):0;
    if (!xarVarFetch('uid',            'int:1:',    $uid)) return;
    if (!xarVarFetch('pname',          'str:1:100:', $pname)) return;
    if (!xarVarFetch('ptype',          'int',       $ptype)) return;
    if (!xarVarFetch('phome',          'str',       $phome, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pprimaryparent', 'int',       $pprimaryparent, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl',      'str',       $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl',      'str',       $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url',      'str',       $return_url, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('utimezone',      'str:1:',    $utimezone,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('allowemail',     'checkbox',  $allowemail,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pparentid', 'str:1:',   $pparentid, $sessionguid ,     XARVAR_NOT_REQUIRED)) return;
     if (!xarVarFetch('template','str:1:100',$template,'',XARVAR_NOT_REQUIRED)) return;
  if (!empty($return_url) && empty($returnurl)) $returnurl = $return_url;
    $data['pparentid'] = $pparentid;
    //need username to do the sec check and this is not available
    //until later in the function as it could be changed in this process
    xarSession::delVar("roles.modifyuser");

    $tname = ($ptype == ROLES_GROUPTYPE) ? xarML('Group'): xarML('User');
    //special groups and users
    $anon = _XAR_ID_UNREGISTERED;
    $everybody = xarModGetVar('roles','everybody');
    $myselfinfo = xarMod::apiFunc('roles','user','get',array('uname'=>'myself'));
    $myself = $myselfinfo['uid'];
    $administrators = xarUFindRole('Administrators');
    $adminguid = $administrators->uid;
    //let's get out quick
    $noedit= array($anon,$everybody,$myself,$adminguid);
    $roles  = new xarRoles();
    $role   = $roles->getRole($uid);
    if (in_array($uid,$noedit)) {
        $msg = xarML('That role (#(1)) cannot be edited.',$role->getName());
        return xarResponseForbidden($msg);
    }

    //Grab it here if primary parent modvar is activated
    if (!empty($pprimaryparent) && is_integer($pprimaryparent) && xarModGetVar('roles','setprimaryparent')) {
        $primaryrole   = new xarRoles();
        $primaryp      = $primaryrole->getRole($pprimaryparent);
        $primaryparent = $primaryp->uname;
    } else {
        $primaryparent='';
    }
    if (!empty($utimezone) && is_string($utimezone) && xarModGetVar('roles','setusertimezone')) {
        $timeinfo = xarMod::apiFunc('base','user','timezones', array('timezone' => $utimezone));

        list($hours,$minutes) = explode(':',$timeinfo[0]);
        $offset        = (float) $hours + (float) $minutes / 60;
        $timeinfoarray = array('timezone' => $utimezone, 'offset' => $offset);
        $usertimezone  = serialize($timeinfoarray);
    } else {
        $usertimezone='';
    }

    //Save the old state and type
    $roles    = new xarRoles();
    $oldrole  = $roles->getRole($uid);
    $oldstate = $oldrole->getState();
    $oldtype  = $oldrole->getType();

    // groups dont have pw etc., and can only be active
    if ($ptype == ROLES_GROUPTYPE) {
        //For group
        $invalid['pname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'displayname', 'var'=>$pname, 'isgroup'=>true, 'uid'=>$uid));

        if ($invalid['pname']) {
            $args =   array('pinvalid'=>$invalid,
                            'pname' => $pname,
                            'ptype' => 1,
                            'pparentid' => $pparentid,
                            'uid'=>$uid
                            );

            xarResponseRedirect(xarModURL('roles','admin','modifyrole',$args));
        } else {
            $puname = $oldrole->getUser();
            $pemail = "";
            $ppass1 = "";
            $pstate = 3;

        }

    } else {
        if (!xarVarFetch('puname', 'str:1:100:', $puname)) return;
        if (!xarVarFetch('pemail', 'str:1:',    $pemail)) return;
        if (!xarVarFetch('ppass1', 'str:1:',    $ppass1,'')) return;
        if (!xarVarFetch('ppass2', 'str:1:',    $ppass2,'')) return;
        if (!xarVarFetch('pstate', 'int:1:',    $pstate)) return;

        $invalid = array();

    // check username
        $invalid['puname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'username', 'var'=>$puname, 'uid'=>$uid));

       // check display name if required
        $requiredisplayname = xarModGetVar('roles','requiredisplayname');
        if ($requiredisplayname == TRUE) {
            $invalid['pname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'displayname', 'var'=>$pname,'uid'=>$uid));
        } elseif (empty($pname)) {
            $pname= $puname; //only set this if it's not set already
        }

        // check email
        $invalid['pemail'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'email', 'var'=>$pemail,'uid'=>$uid));

        $pass = '';
        $invalid['ppass1'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass1', 'var'=>$ppass1 ,'uid'=>$uid));
        if (empty($invalid['ppass1'])) {
            $invalid['ppass2'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass2', 'var'=>array($ppass1,$ppass2),'uid'=>$uid ));
        }
        if (empty($invalid['ppass1']) && empty($invalid['ppass2']))   {
            $pass = $ppass1;
        }

        //grab any properties - show or hide in the template accordingly
        $properties = null;
        $isvalid = true;
         //holds the property values so we can use them easily in the templates
        $propertyvalues = array();
        if (xarMod::isHooked('dynamicdata','roles',ROLES_USERTYPE)) {
            // get the Dynamic Object defined for this module (and itemtype, if relevant)
                 $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                        array('modid' => xarMod::getId('roles'),
                                              'itemtype'=> ROLES_USERTYPE
                                             )
                                        );
            if (isset($object) && !empty($object->objectid)) {
                // check the values submitted for the DD object  properties
                $object->getItem(array('itemid'=>$uid));
                $isvalid = $object->checkInput();
                // get the Dynamic Properties of this object
                $properties =& $object->getProperties();
                foreach ($properties as $name=>$property) {

                    //see if we have any invalid values and need to return
                    $invalid[$name]=$property->invalid;
                    $propertyvalues[$name] = $property->value;
                }
            }
        } else {
           $properties = array();
        }

        $a = array_count_values($invalid); // $a[''] will be the count of null values
        if (!isset($a[''])) $a['']='';
        $countInvalid = count($invalid) - $a[''];
        $args = array('puname'      => $puname,
                        'pname'     => $pname,
                        'pemail'    => $pemail,
                        'ptype'     =>$ptype,
                        'pstate'    =>$pstate,
                        'phome'     =>$phome,
                        'pprimaryparent' => $pprimaryparent,
                        'propertyvalues'=> $propertyvalues,
                        'pparentid' => $pparentid,
                        'invalid'   =>$invalid,
                        'returnurl' =>  $returnurl,
                        'uid'       => (int)$uid)

                        ;

        if ($countInvalid > 0 || !$isvalid) {

        //we need to get more data to return to template
              $args['frozen']= false;
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
                }

                if(xarSecurityCheck('RemoveRole',0,'Relation', $puid . ":" . $uid) || in_array($puid,$allowedgids)) {
                    $parents[] = array('parentid'    => $puid,
                                       'parentname'  => $parent->getName(),
                                       'parentuname' => $parent->getUname());
                    $names[] = $parent->getName();
                }

            }
            $args['parents'] = $parents;
            $args['allowedgroups'] = $allowedgroups;
            $args['ptypeoptions'] = array('1'=>xarML('Group'),'0'=>xarML('User'));
            $args['stateoptions'] = array(ROLES_STATE_INACTIVE => xarML('Inactive'),
                                  ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                                  ROLES_STATE_ACTIVE => xarML('Active'),
                                  ROLES_STATE_PENDING => xarML('Pending')
                                  );
             $args['authid']   = xarSecGenAuthKey('roles');
             $args['noedit']= array($anon,$everybody,$myself,$adminguid);
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
             $args['groups'] = $groups;
           // if so, return to the previous template, don't send plain text pass
            xarTplSetMessage(xarML('The user role has not been updated. Please check the form for errors.'),'error');
           return xarTplModule('roles','admin','modifyrole',$args,$template);

            return;
        }
    }

    //security checks
    $parentroles = $oldrole->getParents();
    $oldroletype = $oldrole->getType();
    foreach ($parentroles as $parent) {
         $puid = $parent->getID();

        if (xarSecurityCheck('ModerateGroupRoles',0,'Group',$puid) || xarSecurityCheck('ModerateRole',0,'Roles',$puid)) {
            $allowedgroups[$puid] = $parent->getName();
            $allowedgids[] = $puid;
        }
    }

    //now we have the username for role and group. Let's do the security check
    if ($oldroletype == ROLES_GROUPTYPE) {
        if (!xarSecurityCheck('EditRole',0,'Roles',$uid)) return;

    } else {
        $inallowedgroup = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$allowedgids,'uid'=>$uid));
        //check access rights, do not throw an exception - handle the response
        if (!$inallowedgroup  && !xarSecurityCheck('EditRole',0,'Roles',$uid)) {
            $msg = xarML('No access to modify this user in #(3)_#(1)_#(2).php', 'admin', 'updaterole', 'roles');
            return xarResponseForbidden($msg);
        }
    }

    $duvs = array();
    if (isset($phome) && xarModGetVar('roles','setuserhome'))
            $duvs['userhome'] = $phome;

    if ((!empty($ppass1)) && xarModGetVar('roles','setpasswordupdate')){
        //assume if it's not empty then it's already been matched with ppass2
        $duvs['passwordupdate'] = time();
    } elseif (xarModGetVar('roles','setpasswordupdate')) { //get existing
        $duvs['passwordupdate'] = xarModUserVars::get('roles','passwordupdate',$uid);
    }
    if (xarModGetVar('roles','setuserlastlogin')) {
        $duvs['userlastlogin'] = xarModUserVars::get('roles','userlastlogin', $uid);
    }

    if (xarModGetVar('roles','setprimaryparent')) {
        $duvs['primaryparent'] = $primaryparent;
    }
    if (xarModGetVar('roles','setusertimezone')) {
        $duvs['usertimezone'] = $usertimezone;
    }

    //the user cannot receive emails from other users until they allow it and admin allows this option
    xarModSetUserVar('roles','allowemail', $allowemail, $uid);
    // assemble the args into an array for the role constructor
    $pargs = array('uid'      => $uid,
                   'name'     => $pname,
                   'type'     => $ptype,
                   'uname'    => $puname,
                   'userhome' => $phome,
                   'primaryparent' => $primaryparent,
                   'usertimezone' => $usertimezone,
                   'email'    => $pemail,
                   'pass'     => $ppass1,
                   'state'    => $pstate,
                   'duvs'     =>$duvs);

   $role = new xarRole($pargs);
   $user = xarMod::apiFunc('roles','user','get',array('uid'=>$uid));
   // Try to update the role  and bail if an error was thrown
    if (!$role->update()) {
        return;
    } else {
        $msg = xarML('The #(1) named "#(2)" was updated successfully.',$tname, $pname);
        xarTplSetMessage($msg,'status');
    }
    // call item update hooks (for DD etc.)
    // TODO: move to update() function
    $pargs['module']   = 'roles';
    $pargs['itemtype'] = $ptype; // we might have something separate for groups later on
    $pargs['itemid']   = (int)$uid;
    xarMod::callHooks('item', 'update', $uid, $pargs);

    //Change the defaultgroup var values if the name is changed
    if ($ptype == 1) {
        $defaultgroup = xarModGetVar('roles', 'defaultgroup');
        $defaultgroupuid = xarMod::apiFunc('roles','user','get',
                                   array('uname'  => $defaultgroup,
                                         'type'   => 1));
        if ($uid == $defaultgroupuid) xarModSetVar('roles', 'defaultgroup', $pname);

        // Adjust the user count if necessary
        if ($oldtype == 0) $oldrole->adjustParentUsers(-1);
    }else {
        // Adjust the user count if necessary
        if ($oldtype == 1) $oldrole->adjustParentUsers(1);
        //TODO : Be able to send 2 email if both password and type has changed... (or an single email with a overall msg...)
        //Ask to send email if the password has changed
        if ($ppass1 != '') {
            if (xarModGetVar('roles', 'askpasswordemail')) {
                xarSession::setVar('tmppass',$ppass1);
                xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                array('uid' => array($uid => '1'), 'mailtype' => 'password','pparentid' => $pparentid)));
            }
            //TODO : If askpasswordemail is false, the user won't know his new password...
        }

        //Ask to send email if the state has changed
        if ($user['state'] != $pstate) {
            //Get the notice message
            switch ($pstate) {
                case ROLES_STATE_INACTIVE :
                    $mailtype = 'deactivation';
                break;
                case ROLES_STATE_NOTVALIDATED :
                    $mailtype = 'validation';
                break;
                case ROLES_STATE_ACTIVE :
                    $mailtype = 'welcome';
                break;
                case ROLES_STATE_PENDING :
                    $mailtype = 'pending';
                break;
            }
            if (xarModGetVar('roles', 'ask'.$mailtype.'email')) {
                xarResponseRedirect(xarModURL('roles', 'admin', 'asknotification',
                              array('uid' => array($uid => '1'), 'mailtype' => $mailtype, 'pparentid' => $pparentid)));
            }
            //TOTHINK : If ask$mailtypeemail is false, the user won't know his new state...
        }
    }


    // which page to redirect to?
    if (empty($returnurl)) {
        if (xarSecurityCheck('ModerateGroupRoles',0,'Group',$pparentid) || xarSecurityCheck('ModerateRole',0,'Roles',$uid))  {
            $returnurl = xarModURL('roles', 'admin', 'modifyrole', array('uid' => $uid,'pparentid' => $pparentid));
        } else {
            $returnurl = xarModURL('roles', 'admin', 'showusers');
        }
    }

    xarResponseRedirect($returnurl);

}

?>