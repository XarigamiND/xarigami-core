<?php
/**
 * Add a role
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
 * addRole - add a role
 * This function tries to create a user and provides feedback on the
 * result.
 */
function roles_admin_addrole()
{
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) return;
    $defaultRole = xarMod::apiFunc('roles', 'user', 'get', array('name'  => xarMod::apiFunc('roles','user','getdefaultgroup'), 'type'   => ROLES_GROUPTYPE));
    $defaultuid  = $defaultRole['uid'];
    // get some vars for both groups and users
    if (!xarVarFetch('pname',      'str:1:', $pname,      NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptype',      'str:1',  $ptype,      NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pparentid',  'str:1:', $pparentid,  $defaultuid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl',      'str',       $returnurl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url',      'str',       $return_url, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('template','str:1:100',$template,'',XARVAR_NOT_REQUIRED)) return;

    if (!empty($return_url) && empty($returnurl)) $returnurl = $return_url;
    // get the rest for users only
    $roles = new xarRoles();
    // get the role to be deleted
    $role = $roles->getRole($pparentid);

    //Check for privilege to add role
    if ( !xarSecurityCheck('AddGroupRoles',0,'Group',$pparentid) &&  !xarSecurityCheck('AddRole',0)
        )  return xarResponseForbidden();
    if (isset($ptype) && !xarSecurityCheck('AddRole',0,'Roles',$pparentid) && ($ptype == ROLES_GROUPTYPE)) {
            $msg = xarML('No access to create a Group (#$(4)) in #(3)_#(1)_#(2).php', 'admin', 'addrole', 'roles',$role->getName());
             return xarResponseForbidden($msg);

    }
    if ($ptype == ROLES_USERTYPE) {
        xarVarFetch('puname', 'str:1:100:', $puname, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pemail', 'str:1:', $pemail, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('ppass1', 'str:1:', $ppass1, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('ppass2', 'str:1:', $ppass2, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pstate', 'str:1:', $pstate, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('phome', 'str', $phome, NULL, XARVAR_NOT_REQUIRED);
        xarVarFetch('pprimaryparent', 'int', $pprimaryparent, NULL, XARVAR_NOT_REQUIRED); // this seems redundant here
    }

        // invalid fields (we'll check this below)
        $invalid = array();
        xarSession::delVar("roles.modifyuser");
    // checks specific only to users
    if ($ptype == ROLES_USERTYPE) {

        // check username
        $invalid['puname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'username', 'var'=>$puname));

       // check display name if required
        $requiredisplayname = xarModGetVar('roles','requiredisplayname');
        if ($requiredisplayname == TRUE) {
            $invalid['pname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'displayname', 'var'=>$pname));
        } elseif (empty($pname)) {
            $pname= $puname; //only set this if it's not set already
        }

        // check email
        $invalid['pemail'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'email', 'var'=>$pemail));

        $pass = '';
        $invalid['ppass1'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass1', 'var'=>$ppass1 ));
        if (empty($invalid['ppass1'])) {
            $invalid['ppass2'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass2', 'var'=>array($ppass1,$ppass2) ));
        }
        if (empty($invalid['ppass1']) && empty($invalid['ppass2']))   {
            $pass = $ppass1;
        }

        // dynamic properties
        //grab any properties - show or hide in the template accordingly
        // Cannot use hooks for this (yet)
        $properties = null;
        $isvalid = true;
        //holds the property values so we can use them easily in the templates
        $propertyvalues = array();
        if (xarMod::isHooked('dynamicdata','roles',ROLES_USERTYPE)) {
            // get the Dynamic Object defined for this module (and itemtype, if relevant)
            $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                      array('module' => 'roles','itemtype'=>ROLES_USERTYPE));

            if (isset($object) && !empty($object->objectid)) {
                // check the input values for this object !
                $isvalid = $object->checkInput();
               // get the Dynamic Properties of this object
                $properties =& $object->getProperties();
                foreach ($properties as $name=>$property) {
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

        if ($countInvalid > 0 || !$isvalid) {
        // if so, return to the previous template
            $args= array('puname' => $puname,
                            'pname'  => $pname,
                            'pemail'  => $pemail,
                            'ptype' =>$ptype,
                            'pstate' =>$pstate,
                            'phome'=>$phome,
                            'ppass1' => '',
                            'ppass2'=> '',
                            'returnurl' => $returnurl,
                            'pparentid' =>$pparentid,
                            'properties' => $properties,
                            'propertyvalues' => $propertyvalues,
                            'invalid' => $invalid,
                            'pprimaryparent' => $pprimaryparent,
                            'template' => $template
                            );
        $args['ptypeoptions'] = array(ROLES_GROUPTYPE=>xarML('Group'),ROLES_USERTYPE=>xarML('User'));
        $args['stateoptions'] = array(ROLES_STATE_INACTIVE => xarML('Inactive'),
                                  ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                                  ROLES_STATE_ACTIVE => xarML('Active'),
                                  ROLES_STATE_PENDING => xarML('Pending')
                            );
        //we need some more vars to pass back to the template
            $groups = array();
            $names = array();
            $groupselect = array();
            $groups = $roles->getgroups();
            foreach($groups as $temp) {
                $nam = $temp['name'];
                $hasaccess =   (xarSecurityCheck('AddGroupRoles',0,'Group',$temp['uid']) || xarSecurityCheck('AddRole',0,'Roles',$temp['uid']));
                if ($hasaccess) {
                    if (!in_array($nam, $names)) {
                        $names[]  = $nam;
                        $groups[] = $temp;
                        $groupselect[$temp['uid']] = $temp['name'];
                    }
                }
            }
            $args['groupselect'] = $groupselect;
            $args['authid']     = xarSecGenAuthKey();
            $item = $args;
            $item['module']   = 'roles';
            $item['itemtype'] = $args['ptype']; // we might have something separate for groups later on
            $item['phase']    = 'addrole';
            $hooks   = xarMod::callHooks('item', 'new', '', $item);
            unset($hooks['dynamicdata']);//we handle this ourselves
            $args['hooks'] = $hooks;
            xarTplSetMessage(xarML('The user role has not been created. Please check the form for errors.'),'error');

           return xarTplModule('roles','admin','newrole',$args,$template);
        }

    }
    // assemble the args into an array for the role constructor
    if ($ptype ==  ROLES_USERTYPE) {
        $duvs = array();
        if (isset($phome) && xarModGetVar('roles','setuserhome'))
            $duvs['userhome'] = $phome;
        if (xarModGetVar('roles','setprimaryparent')) {
            //the primary parent is a string name inline with default role etc
            $parentrole= xarMod::apiFunc('roles', 'user', 'get', array('uid'  => $pparentid, 'type'   => ROLES_GROUPTYPE));
            $duvs['primaryparent'] = $parentrole['uname'];
        }
        $pargs = array('name' => $pname,
            'type' => $ptype,
            'parentid' => $pparentid,
            'uname' => $puname,
            'email' => $pemail,
            'pass' => $pass,
            'val_code' => 'createdbyadmin',
            'state' => $pstate,
            'auth_module' => 'authsystem',
            'duvs' => $duvs,
            );
    } else {
        //For group
        $invalid['pname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'displayname', 'var'=>$pname, 'isgroup'=>true));

        if ($invalid['pname']) {
            $args =   array('invalid'=>$invalid,
                            'pname' => $pname,
                            'ptype' => ROLES_GROUPTYPE,
                            'pparentid' => $pparentid,
                            );

            xarResponseRedirect(xarModURL('roles','admin','newrole',$args));
        } else {
        $pargs  = array('name' => $pname,
                        'type' => $ptype,
                        'parentid' => $pparentid,
                        'uname' => xarSession::getVar('uid') . time(),
                        'val_code' => 'createdbyadmin',
                        'auth_module' => 'authsystem',
                        );
        }
    }
    // create a new role object
    $role = new xarRole($pargs);
    // Try to add the role to the repositoryand bail if an error was thrown
    if (!$role->add()) {
        return;
    }
    $tname = $ptype == ROLES_GROUPTYPE ? xarML('Group'): xarML('User');
    $msg = xarML('The #(1) named "#(2)" was successfully added.',$tname, $pname);
    xarTplSetMessage($msg,'status');
    // retrieve the uid of this new user
    $uid = $role->uid;

    // call item create hooks (for DD etc.)
// TODO: move to add() function
    $pargs['module'] = 'roles';
    $pargs['itemtype'] = $ptype; // we might have something separate for groups later on
    $pargs['itemid'] = $uid;
    xarMod::callHooks('item', 'create', $uid, $pargs);

    // redirect to the next page
    if (!empty($returnurl)) {
        xarResponseRedirect($returnurl);
    } else {
        if (xarSecurityCheck('EditGroupRoles',0,'Group',$pparentid) || xarSecurityCheck('EditRole',0,'Roles',$uid)) {
            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyrole',array('uid' => $uid)));
        } else {
            xarResponseRedirect(xarModURL('roles', 'admin', 'showusers',array('uid' => $pparentid)));
        }
    }
}
?>
