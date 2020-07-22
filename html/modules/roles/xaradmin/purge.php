<?php
/**
 * Purge users by status
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
 * purge users by status
 * @param 'status' the status we are purging
 * @param string operation
 * @param string confirmation The confirmation that this item can be purge
 * @return array
 */
function roles_admin_purge($args)
{
    // Security Check - handle the error
    if(!xarSecurityCheck('DeleteRole',0)) return xarResponseForbidden();

    // Get parameters from whatever input we need
    if (!xarVarFetch('operation',    'str', $data['operation'], 'recall', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmation', 'str', $confirmation,       '',       XARVAR_NOT_REQUIRED)) return;

    extract($args);

    // Get database setup
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $rolestable = $xartable['roles'];

    $deleted = '[deleted]';
    $numitems = xarModGetVar('roles', 'itemsperpage');
    // Make sure a value was retrieved for itemsperpage
    if (empty($numitems)) $numitems = -1;

    //set up array for drop down selectors

    $data['statearray'] = array(
                                ROLES_STATE_INACTIVE => xarML('Inactive'),
                                ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                                ROLES_STATE_ACTIVE => xarML('Active'),
                                ROLES_STATE_PENDING => xarML('Pending')
                        );

    $data['purgearray' ] = array( -1 => xarML('All'),
                                ROLES_STATE_DELETED => xarML('Deleted')
                        );

    $data['purgearray'] = $data['purgearray'] + $data['statearray'];

    if ($data['operation'] == 'recall')
    {
        if (!xarVarFetch('recallstate',    'int:1:', $data['recallstate'],  NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsubmit',   'str',    $recallsubmit,         NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallsearch',   'str',    $data['recallsearch'], NULL, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('recallstartnum', 'int:1:', $recallstartnum,       1,    XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('recalluids',     'isset',  $recalluids,           array(), XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('groupuid',       'int:1',  $data['groupuid'],     0,    XARVAR_NOT_REQUIRED)) return;

        if ($confirmation == 'recall')
        {
 // --- recall users and groups
            if(!xarSecurityCheck('DeleteRole')) return;
            $roleslist = new xarRoles();
            if ($data['groupuid'] != 0) $parentgroup = $roleslist->getRole($data['groupuid']);
            foreach ($recalluids as $uid => $val) {
                $role = $roleslist->getRole($uid);
                $state = $role->getType() ? ROLES_STATE_ACTIVE : $data['recallstate'];
                $recalled = xarMod::apiFunc('roles','admin','recall', array('uid' => $uid,'state' => $state));
                $parentgroup->addmember($role);
            }
        }
// --- display roles that can be recalled
        //Create the selection
        $q = new xarQuery('SELECT',$rolestable);
        $q->addfields(array('xar_uid AS uid',
                    'xar_uname AS uname',
                    'xar_name AS name',
                    'xar_email AS email',
                    'xar_type AS type',
                    'xar_date_reg AS date_reg'));
        $q->setorder('xar_name');
        if (!empty($data['recallsearch'])) {
            //jojo - we need this compatible over multiple db
            //check  xarquery handling of qstr and getting double quoting?

            $quoted = strtr($data['recallsearch'],array('_'=>'\_','%'=>'\%'));
            $quoted = $dbconn->qstr('%'.$quoted.'%');
            $c[1] = $q->like('xar_name',".$quoted.");
            $c[2] = $q->like('xar_uname',".$quoted.");
            $c[3] = $q->like('xar_email',".$quoted.");
            $q->qor($c);
        }
        $q->eq('xar_state',ROLES_STATE_DELETED);
        $q->ne('xar_date_reg','');
        $q->setrowstodo($numitems);
        $q->setstartat($recallstartnum);
//        $q->qecho();

        if(!$q->run()) return;

        $data['totalselect'] = $q->getrows();

        if ($data['totalselect'] == 0) {
            $data['recallmessage'] = xarML('There are no deleted groups/users ');
        }
        else {
            $data['recallmessage']         = '';
        }

        $recallroles = array();
        foreach ($q->output() as $role) {
// check each role's user name
            if (empty($role['uname'])) {
                $msg = xarML('Execution halted: the role with id #(1) has an empty name. This needs to be corrected manually in the database.', $role['uid']);
                throw new Exception($msg);
            }
            if (xarSecurityCheck('ReadRole', 0, 'Roles', $role['uid'])) {
                $skip = 0;
                $unique = 1;
                if ($role['type']) {
                    $existinguser = xarMod::apiFunc('roles','user','get',array('uname' => $role['uname'], 'type' => 1, 'state' => ROLES_STATE_CURRENT));
                    if (is_array($existinguser)) $unique = 0;
                    $role['uname'] = "";
                }
                else {
                    $uname1 = explode($deleted,$role['uname']);
// checking empty unames for code robustness :-)
                    if($uname1[0] == '') {
                        $existinguser = 0;
                        $skip = 1;
                    }
                    else
                        $existinguser = xarMod::apiFunc('roles','user','get',array('uname' => $uname1[0], 'state' => ROLES_STATE_CURRENT));
                    if (is_array($existinguser)) $unique = 0;
                    $role['uname'] = $uname1[0];
// remove [deleted] marker from email (fix for Bug 3484)
                    $email = explode($deleted,$role['email']);
                    $role['email']=$email[0];
// now check that email is unique if this has to be checked (fix for nonexisting Bug)
//jojo - i disagree with this check. Can cause problems if unique email is turned on after the fact, or if no email.
// adding some check to prevent error on no email else the whole function will fail
                    if (xarModGetVar('roles', 'uniqueemail')) {
                        if (!empty($email[0])) {
                            $existinguser = xarMod::apiFunc('roles','user','get',array('email' => $email[0], 'state' => ROLES_STATE_CURRENT));
                         if (is_array($existinguser)) $unique = 0;
                        }
                    }
               }
                if (!$skip) {
                    $role['type'] = $role['type'] ? xarML("Group") : xarML("User");
                    $role['unique'] = $unique;
                    $recallroles[] = $role;
                }
            }
        }
// --- send to template
        $data['groups'] = xarMod::apiFunc('roles', 'user', 'getallgroups');
        $recallfilter['recallstartnum'] = '%%';
        $filter['state']         = $data['recallstate'];
        $recallfilter['recallsearch']   = $data['recallsearch'];
        $data['submitRecall']    = xarML('Recall');
        $data['recallroles']     = $recallroles;
        $data['recallpager']     = xarTplGetPager($recallstartnum,
                                                  $data['totalselect'],
                                                  xarModURL('roles', 'admin', 'purge', $recallfilter),
                                                  $numitems);
  /**
    * Purge Users */
    }  elseif ($data['operation'] == 'purge') {

        if (!xarVarFetch('purgestate',    'int',    $data['purgestate'], -1,      XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesearch',   'str',    $data['purgesearch'], NULL,   XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgesubmit',   'str',    $purgesubmit,         NULL,   XARVAR_DONT_SET)) return;
        if (!xarVarFetch('purgestartnum', 'int:1:', $purgestartnum,       1,      XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('purgeuids',     'isset',  $purgeuids,           array(), XARVAR_NOT_REQUIRED)) return;

        //define users that cannot be deleted
        $anon = _XAR_ID_UNREGISTERED;
        $everybody = xarModGetVar('roles','everybody');
        $admin  = xarModGetVar('roles','admin');
        $myselfrole = xarMod::apiFunc('roles','user','get', array('uname'=>'myself'));
        $myself = $myselfrole['uid'];
        $nodelete = array($admin,$anon,$everybody,$myself);

        // Check for confirmation.
        if ($confirmation == 'purge')
        {
            if(!xarSecurityCheck('AdminRole')) return;
            $roleslist = new xarRoles();
            foreach ($purgeuids as $uid => $val) {
                // --- skip if we are trying to remove the designated site admin or the anonymous user, or myself, or everyboy
               if (in_array($uid,$nodelete)) continue;

                // --- do this in 2 stages. First, delete the role: this will update the user
                // --- count on all the role's parents
                $role = $roleslist->getRole($uid);
                $olduname = $role->getUname();
                //is this role active? we require users to be deleted status before purging
                if ($role->getState()!= ROLES_STATE_DELETED )
                {
                    //set a message
                    $msg = xarML('The user "#(1)" was not purged. The user must be deleted first.',$olduname);
                    xarTplSetMessage($msg,'alert');
                    continue;
                } else {
                    $role->remove();
                }
                // --- now actually remove the data from the role's entry
                $state = ROLES_STATE_DELETED;
                $uname = $deleted . microtime(TRUE) .'.'. $uid;
                $name = '';
                $pass = '';
                $email = '';
                $date_reg = '';
                $q = new xarQuery('UPDATE',$rolestable);
                $q->addfield('xar_name',$name);
                $q->addfield('xar_uname',$uname);
                $q->addfield('xar_pass',$pass);
                $q->addfield('xar_email',$email);
                $q->addfield('xar_date_reg',$date_reg);
                $q->addfield('xar_state',$state);
                $q->eq('xar_uid',$uid);
                $q->run();
// --- Let any hooks know that we have purged this user.
                $item['module'] = 'roles';
                $item['itemid'] = $uid;
                $item['method'] = 'purge';
                xarMod::callHooks('item', 'delete', $uid, $item);
                //set a message
                $msg = xarML('The user "#(1)" was purged from the system.',$olduname);
                xarTplSetMessage($msg,'status');
            }
        }
// --- display users that can be purged
        $selection = " WHERE xar_email != ''";
        //Create the selection
        if ($data['purgestate'] != -1) {
            $selection .= " AND xar_state = " . $data['purgestate'];
            switch ($data['purgestate']):
                case ROLES_STATE_DELETED :
                    $data['purgestatetext'] = 'deleted';
                    break ;
                case ROLES_STATE_INACTIVE :
                    $data['purgestatetext'] = 'inactive';
                    break ;
                case ROLES_STATE_NOTVALIDATED :
                    $data['purgestatetext'] = 'not validated';
                    break ;
                case ROLES_STATE_ACTIVE :
                    $data['purgestatetext'] = 'active';
                    break ;
                case ROLES_STATE_PENDING :
                    $data['purgestatetext'] = 'pending';
                    break ;
            endswitch ;
        }
        else {
            $data['purgestatetext'] = '';
        }
        if (!empty($data['purgesearch'])) {
            //jojo - review - we need this compatible with multiple db types
            $quoted = strtr($data['purgesearch'],array('_'=>'\_','%'=>'\%'));
            $quoted = $dbconn->qstr('%'.$quoted.'%');
            $selection .= " AND (";
            $selection .= "(xar_name LIKE ".$quoted.")";
            $selection .= " OR (xar_uname LIKE  ".$quoted.")";
            $selection .= " OR (xar_email LIKE  ".$quoted.")";
            $selection .= " OR (xar_state LIKE  ".$quoted.")";
            $selection .= ")";


        }
        // Select-clause.
        $query = '
            SELECT DISTINCT xar_uid,
                    xar_uname,
                    xar_name,
                    xar_email,
                    xar_state,
                    xar_date_reg
                    FROM ' . $rolestable .
                    $selection .
                    ' ORDER BY xar_name';

        $result = $dbconn->Execute($query);
        $data['totalselect'] = $result->_numOfRows;
        if (!$result) {return;}
        if ($purgestartnum != 0) {
            $result = $dbconn->SelectLimit($query, $numitems, $purgestartnum-1);
            if (!$result) {return;}
        }

        if ($data['totalselect'] == 0) {
            $data['purgemessage'] = xarML('There are no users selected ');
        }
        else {
            $data['purgemessage'] = '';
        }

        $purgeusers = array();
        for (; !$result->EOF; $result->MoveNext()) {
            list($uid, $uname, $name, $email, $state, $date_reg) = $result->fields;
            //Display name is not longer required - so we cannot check for display name
            //if (empty($name) || empty($uname)) {
            if (empty($uname)) {
                $msg = xarML('Execution halted: the role with uid #(1) has an empty user name. This needs to be corrected manually in the database.', $uid);
                throw new EmptyParameterException(null,$msg);
            }
            switch ($state):
                case ROLES_STATE_DELETED :
                    $state = xarML('deleted');
                    break ;
                case ROLES_STATE_INACTIVE :
                    $state = xarML('inactive');
                    break ;
                case ROLES_STATE_NOTVALIDATED :
                    $state = xarML('not validated');
                    break ;
                case ROLES_STATE_ACTIVE :
                    $state = xarML('active');
                    break ;
                case ROLES_STATE_PENDING :
                    $state = xarML('pending');
                    break ;
            endswitch ;
            if (!in_array($uid, $nodelete)) {
                $purgeusers[] = array(
                    'uid'       => $uid,
                    'uname'     => $uname,
                    'name'      => $name,
                    'email'     => $email,
                    'state'      => $state,
                    'date_reg'  => $date_reg
                    );
            }
        }
// --- send to template
        $purgefilter['purgestartnum'] = '%%';
        $purgefilter['purgesearch'] = $data['purgesearch'];
        $purgefilter['operation'] = 'purge';
        $data['submitPurge'] = xarML('Purge');
        $data['purgeusers']  = $purgeusers;
        $purgefilter['purgestate']         = $data['purgestate'];
        $data['purgepager']  = xarTplGetPager($purgestartnum,
                                              $data['totalselect'],
                                              xarModURL('roles', 'admin', 'purge', $purgefilter),
                                              $numitems);
    } else {
       //what?
    }

// --- finish up
    $data['authid']         = xarSecGenAuthKey('roles');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');
    // Return
    return $data;
}
?>