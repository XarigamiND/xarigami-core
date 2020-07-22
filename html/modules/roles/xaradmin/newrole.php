<?php
/**
 * Create a new role
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
 * newRole - create a new role
 * Takes no parameters
 *
 * @author Marc Lutolf
 * @author Jo Dalle Nogare
 */
function roles_admin_newrole()
{
    $defaultRole = xarMod::apiFunc('roles', 'user', 'get', array('name'  => xarMod::apiFunc('roles','user','getdefaultgroup'),
                                                               'type'   => ROLES_GROUPTYPE));
    $defaultuid  = $defaultRole['uid'];

    if (!xarVarFetch('returnurl',     'isset',    $returnurl, NULL,       XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('pparentid',      'int:',     $pparentid, $defaultuid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pname',          'str:1:',   $name,      '',          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ptype',          'str:1:',   $type,      '',          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('puname',         'str:1:35:',$uname,     '',          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail',         'str:1:',   $email,     '',          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass1',         'str:1:',   $pass,      '',          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state',          'str:1:',   $state,     '',          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phome',          'str',      $home,      '',          XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('invalid',        'isset',     $invalid,   array())) return;
    if (!isset($uid)) $uid= $pparentid;
    // Security Check
    $roles = new xarRoles();
    $role = $roles->getRole($pparentid);
    //Check for privilege to add role - don't throw an exception
    if ( !xarSecurityCheck('AddGroupRoles',0,'Group',$pparentid) && !xarSecurityCheck('AddRole',0,'Roles',$pparentid)) {
        $msg = xarML('You do not have the required access level to add new members or groups.');
         return xarResponseForbidden($msg);
    }
    //Must have AddRole access to add a group
    if (isset($type) && !xarSecurityCheck('AddRole',0,'Roles',$pparentid) && ($type == ROLES_GROUPTYPE)) {
        $msg = xarML('No access to create a Group #(4) in #(3)_#(1)_#(2).php', 'admin', 'newrole', 'roles',$role->getName());
            return xarResponseForbidden($msg);
    }
    // Call the Roles class
    $roles = new xarRoles();
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
    $data['groupselect'] = $groupselect;

    // Load Template
    if (isset($name)) {
        $data['pname'] = $name;
    } else {
        $data['pname'] = '';
    }

    if (isset($type)) {
        $data['ptype'] = $type;
    } else {
        $data['ptype'] = ROLES_GROUPTYPE;
    }

    if (isset($uname)) {
        $data['puname'] = $uname;
    } else {
        $data['puname'] = '';
    }

    if (isset($email)) {
        $data['pemail'] = $email;
    } else {
        $data['pemail'] = '';
    }

    if (isset($pass)) {
        $data['ppass1'] = $pass;
    } else {
        $data['ppass1'] = '';
    }

    if (isset($state)) {
        $data['pstate'] = $state;
    } else {
        $data['pstate'] = 1;
    }

    if (isset($home)) {
        $data['phome'] = $home;
    } else {
        $data['phome'] = '';
    }
    //Primary parent is a name string (apparently looking at other code) but passed in here as an int
    //we want to pass it to the template as an int as well
    //Preparing it here but no real use in this function afaik. The Primary parent will be the same as the parent on creation
    if (isset($primaryparent) && is_int($primaryparent)) { //we have a uid
        $data['pprimaryparent'] = $primaryparent;
    } else {
        //this is a new role. Let's set it at the current default roles group
        $data['primaryparent']  = xarModGetVar('roles','defaultgroup');
        $data['pprimaryparent'] = $defaultRole['uid'];;//pass in the uid
    }
    if (isset($pparentid)) {
        $data['pparentid'] = $pparentid;
    } else {
        $data['pparentid'] = $defaultuid;
    }
    $propertyvalues= array();
    $properties = array();
    if ($data['ptype'] == ROLES_USERTYPE) {
        //check if we've been here before and returning with values to recheck


        //handle DD ourselves here for more flexiblity in input
        if (xarMod::isHooked('dynamicdata','roles',ROLES_USERTYPE)) {
            // get the Dynamic Object defined for this module (and itemtype, if relevant)
            $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                                  array('module' => 'roles','itemtype'=>ROLES_USERTYPE));
            if (isset($object) && !empty($object->objectid)) {
               //We grab the existing properties here - not any new posted ones
                $properties =& $object->getProperties();
                foreach ($properties as $name=>$property) {
                    //$invalid[$name] =  $property->invalid;
                    $propertyvalues[$name] =   $property->value;
                }
            }
        }

    }
    $invalid = isset($invalid) ? $invalid : array();

    $data['properties'] = $properties;
    $data['invalid'] = $invalid;
    $data['propertyvalues'] = $propertyvalues;
    $data['stateinactive'] = ROLES_STATE_INACTIVE;
    $data['statenotvalidated'] = ROLES_STATE_NOTVALIDATED;
    $data['stateactive'] =  ROLES_STATE_ACTIVE;
    $data['statepending'] =  ROLES_STATE_PENDING;
    $data['ptypeoptions'] = array(ROLES_GROUPTYPE=>xarML('Group'),ROLES_USERTYPE=>xarML('User'));
    $data['stateoptions'] = array(ROLES_STATE_INACTIVE => xarML('Inactive'),
                                  ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                                  ROLES_STATE_ACTIVE => xarML('Active'),
                                  ROLES_STATE_PENDING => xarML('Pending')
                            );

    // call item new hooks (for DD etc.)
    $item = $data;
    $item['module']   = 'roles';
    $item['itemtype'] = $data['ptype']; // we might have something separate for groups later on
    $item['phase']    = 'addrole';
    $data['hooks']    = xarMod::callHooks('item', 'new', '', $item);
    unset($data['hooks']['dynamicdata']);//we handle this ourselves
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');

    $data['authid']     = xarSecGenAuthKey('roles');
    $data['addlabel']   = xarML('Add');
    $data['groups']     = $groups;
    $data['returnurl'] = $returnurl;
    $data['return_url'] = $returnurl; //for template compat

    return $data;
}
?>