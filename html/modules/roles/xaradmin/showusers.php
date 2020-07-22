<?php
/**
 * Display the users of this role
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * showusers - display the users of this role
 */
function roles_admin_showusers()
{
    // Security Check - don't throw exception handle the response
    if  (!xarSecurityCheck('ModerateGroupRoles',0) && !xarSecurityCheck('EditRole',0)) return xarResponseForbidden();

    // Get parameters
    if (xarCoreCache::isCached('roles', 'defaultgroupuid')) {
        $defaultgid = xarCoreCache::getCached('roles', 'defaultgroupuid');
    } else {
        $defaultgroup = xarMod::apiFunc('roles','user','getdefaultgroup');
        $defaultgrouprole = xarMod::apiFunc('roles','user','get',
                                                 array('uname'  => $defaultgroup,
                                                       'type'   => ROLES_GROUPTYPE));
        $defaultgid =  $defaultgrouprole['uid'];
    }
    xarCoreCache::setCached('roles', 'defaultgroupuid',$defaultgid );
    xarSession::delVar("roles.modifyuser"); //clean up some vars

    //what if our moderators don't have access to the defaultgid??
    if (!xarSecurityCheck('ModerateGroupRoles',0,'Group',$defaultgid) && !xarSecurityCheck('ModerateRole',0,'Role',$defaultgid)) {
        $defaultgid = 0; //just show the main page
    }

    if (!xarVarFetch('uid',      'int:0:', $data['uid'], $defaultgid , XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum,         1,   XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state',    'int:0:', $data['state'],    ROLES_STATE_CURRENT, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('selstyle', 'isset',  $data['selstyle'], xarSession::getVar('rolesdisplay'), XARVAR_DONT_SET)) return;
    if (!xarVarFetch('invalid',  'str:0:', $data['invalid'],  NULL, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('order',    'str:0:', $data['order'],    '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc',   $data['sort'],    '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('search',   'str:0:', $data['search'],   NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('reload',   'str:0:', $reload,           NULL,    XARVAR_DONT_SET)) return;
    if (!xarVarFetch('pparentid',  'str:1:', $data['pparentid'],  NULL, XARVAR_NOT_REQUIRED)) return;

    //uid in showusers function is always a group or 'all users'
    if (isset($data['pparentid'])) $data['uid'] = $data['pparentid'];

    if (!isset($data['uid'])) {
        $sessiongroupid =xarSession::getVar('roles.groupuid');
        $uid = isset($sessiongroupid) ? $sessiongroupid:$defaultgid ;
    } else {
       $uid = $data['uid'];
       xarSession::setVar('roles.groupuid',$uid);
    }
    $data['groupuid'] = $data['uid'];

    //we have access here but do we have access to this $uid - maybe it came in via URL
    //let's reset it to 0
    if  (!xarSecurityCheck('ModerateGroupRoles',0,'Group',$data['groupuid'])
          && !xarSecurityCheck('ModerateRole',0,'Roles',$data['groupuid'])
          && !xarSecurityCheck('MailRoles',0,'Roles',$data['groupuid'])) {
             $data['groupuid'] = 0;
             $data['uid'] = 0;
            xarSession::setVar('roles.groupuid',$data['groupuid']); 
    }

    if (empty($data['selstyle'])) $data['selstyle'] = 0;
    xarSession::setVar('rolesdisplay', $data['selstyle']);

    //Create the role tree
    if ($data['selstyle'] == '1') {
        sys::import('modules.roles.xartreerenderer');
        $renderer = new xarTreeRenderer();
        $data['roletree'] = $renderer->drawtree($renderer->maketree());
        $data['treenode'] = array($renderer->maketree());
    }
    //special groups and users
    $anon = _XAR_ID_UNREGISTERED;
    $everybody = xarModGetVar('roles','everybody');
    $myselfinfo = xarMod::apiFunc('roles','user','get',array('uname'=>'myself'));
    $myself = $myselfinfo['uid'];
    $administrators = xarUFindRole('Administrators');
    $adminguid = $administrators->uid;
    $siteadmin = xarModGetVar('roles','admin');
    $myuid = xarUserGetVar('uid');
    $data['noedit']= array($anon,$everybody,$myself,$adminguid);
    $data['nodel']= array($anon,$everybody,$myself,$adminguid,$siteadmin,$myuid);
    $data['totalusers'] = xarMod::apiFunc('roles','user','countall');

    // Get information on the group we're at
    //These groups are used in drop down selectors and show if the user has access level
    $groups    = xarMod::apiFunc('roles', 'user', 'getallgroups');

    $data['groups'] = array(); //used for access checking
    $allowedgids = array(); //used to test access to groups
    $canmailgids = array(); //used to test mail privs
    $groupoptions = array(); //used in drop down lists
    $groupoptions['0'] = xarML("All Users - #(1)",$data['totalusers'] );
    $deletegids = array();
    $addgids = array();
    $readgids = array();
    //get groups this user has minimum access to - ie moderate
    foreach($groups as $group) {
         if (xarSecurityCheck('DeleteRole',0)) { //this level has access to everything so skip the individual sec checks
            $data['groups'][]= $group;
            $allowedgids[] = $group['uid'];
            $canmailgids[] = $group['uid'];
            $deletegids[] = $group['uid'];
            $addgids[] = $group['uid'];
            $readgids[] = $group['uid'];
            $groupoptions[$group['uid']] = xarVarPrepForDisplay($group['name']).' - '.$group['users'];
         } else {
            //min access for viewing a group in a drop down
            if  (xarSecurityCheck('ModerateGroupRoles',0,'Group',$group['uid']) || xarSecurityCheck('ModerateRole',0,'Roles',$group['uid'])) {
                $data['groups'][]= $group;
                $allowedgids[] = $group['uid'];
                $groupoptions[$group['uid']] = xarVarPrepForDisplay($group['name']).' - '.$group['users'];
            }
            if  (xarSecurityCheck('MailRoles',0,'Mail',$group['uid']) ) {
                $canmailgids[] = $group['uid'];
                $groupoptions[$group['uid']] = xarVarPrepForDisplay($group['name']).' - '.$group['users'];
            }
            //now other levels
            if  (xarSecurityCheck('DeleteGroupRoles',0,'Group',$group['uid']) ) {
                $deletegids[] = $group['uid'];
            }
            if  (xarSecurityCheck('AddGroupRoles',0,'Group',$group['uid']) ) {
                $addgids[] = $group['uid'];
            }
            if  (xarSecurityCheck('ReadGroupRoles',0,'Group',$group['uid']) ) {
                $readgids[] = $group['uid'];
            }
        }
    }


    $lastquery = xarSession::getVar('showusers.rolesquery');

    $data['groupoptions'] = array_unique($groupoptions);
    $data['ptype'] = ROLES_USERTYPE; //by default user

    $data['ancestors'] = array();
    $data['subgroups'] = array();
    if ($uid != 0) {
        // Call the Roles class and get the role
        $roles     = new xarRoles();
        $role      = $roles->getRole($uid);
        $ancestors = $role->getAncestors();
        $data['groupname'] = $role->getName();
        $data['ptype'] = $role->getType();
        $data['title'] = "";
        $data['ancestors'] = array();
        foreach ($ancestors as $ancestor) {
            $data['ancestors'][] = array('name' => $ancestor->getName(),
                                          'uid' => $ancestor->getID());
        }
        //$data['subgroups'] = $roles->getsubgroups($uid);

    }  else {
        $data['title'] = xarML('All ')." ";
        $data['groupname'] = '';
    }

    $numitems = xarModGetVar('roles', 'itemsperpage');
    //gather arguments for the getall user call
    $args = array();
    $args = array( 'startnum'  => $startnum,
                   'numitems'  => $numitems,
                   'state'     => $data['state'],
                   'type'      => ROLES_USERTYPE,
                   'pending'    => true); //get pending as well
    //this is polluted from somewhere - make sure it is valid
    $lastsort = !empty($lastquery['sort']) && ($lastquery['sort'] == 'asc' or $lastquery['sort'] == 'desc') ? $lastquery['sort']: '';
    $data['order'] = !empty($data['order']) ? $data['order'] : (!empty($lastquery['order']) ? $lastquery['order']:'xar_name') ;
    $data['sort'] = !empty($data['sort'])  ? $data['sort']: (!empty($lastsort) ? $lastsort:'asc') ;

    $orderclause = $data['order'].','.strtoupper($data['sort']);
    $args['orderclause'] = $orderclause;
    $args['order']= $data['order'];
    $args['sort']= $data['sort'];

    // If a group was chosen, get only the users of that group
    if ($uid != 0) {
            $args['group'] = $uid;
    }

   //setup query for searching
   //initialize extra search string
    $selection = '';
    if (!empty($data['search']) ){
        $q = $data['search'];
        $q = str_replace('%','\%',$q);
        $q = str_replace('_','\_',$q);
        $likesearch = '%'.$q.'%';
        $selection = "AND (";
        $selection .= " (xar_name LIKE '" . $likesearch . "')";
        $selection .= " OR (xar_uname LIKE '" . $likesearch . "')";
        $selection .= " OR (xar_email LIKE '" . $likesearch . "')";
        $selection .=')';
        $args['selection'] = $selection;
    }
    $userlist = xarMod::apiFunc('roles','user','getall', $args);

    xarSession::setVar('showusers.rolesquery',$args);
    $data['authid']     = xarSecGenAuthKey('roles');
    $data['totalselect'] = count($userlist);

    $data['message'] = '';
    switch ($data['state']) {
        case ROLES_STATE_CURRENT :
        default:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no users');
            $data['title'] .= xarML('Users');
            break;
        case ROLES_STATE_INACTIVE:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no inactive users');
            $data['title'] .= xarML('Inactive Users');
            break;
        case ROLES_STATE_NOTVALIDATED:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no users waiting for validation');
            $data['title'] .= xarML('Users Waiting for Validation');
            break;
        case ROLES_STATE_ACTIVE:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no active users');
            $data['title'] .= xarML('Active Users');
            break;
        case ROLES_STATE_PENDING:
            if ($data['totalselect'] == 0) $data['message'] = xarML('There are no pending users');
            $data['title'] .= xarML('Pending Users');
            break;
    }

    //check if the user can proxy login
    $data['canproxy'] = xarMod::apiFunc('roles','admin','canproxy');
    $data['proxygroup']  = xarModGetVar('roles','proxygroup'); //can be proxied, could be thousands?
    $data['defaultproxy']  = xarModGetVar('roles','defaultproxy'); //can login as

    // assemble the info for the display
    $users = array();
    $uidaccess = array();
    $availableusers = array(); //used for total accessible user count
    //Use the info we have so we don't have to call priv checks again
    //as well as privileges we have special groups/users that we cannot edit (no-edit groups)
    $data['generatenewpass'] = xarModGetVar('roles', 'askpasswordemail');
    //move all the url generation from template to function below
    //this usually happens in the template but moving sec checks here

    $canshowprivs = xarSecurityCheck('ReadPrivilege',0);
    if (xarSecurityCheck('DeleteRole',0)) { //this user can do anything to any role so just setup the array
        foreach($userlist as $user) {
                $user['isfrozen'] = false;
                $user['candelete'] ='';
                $user['canproxy'] ='';
                $frozen = in_array($user['uid'],$data['noedit']);
                if ($frozen) {
                    $user['isfrozen'] =true;
                }
                $groupuid = !empty($data['groupuid'])?$data['groupuid']:'';
                if (!in_array($user['uid'],$data['nodel'])) {
                    $user['candelete']  = xarModURL('roles','admin','deleterole',array('uid'=>$user['uid'], 'authid'=>$data['authid'],'pparentid' => $groupuid));
                }
                $user['canedit']  = xarModURL('roles','admin','modifyrole',array('uid'=>$user['uid'], 'pparentid' => $groupuid));
                if ($data['generatenewpass']) {
                    $user['newpass'] = xarModURL('roles','admin','createpassword', array('uid'=>$user['uid'], 'groupuid' => $groupuid));
                }
                $user['canadd'] =xarModURL('roles','admin','addrole',array('uid'=>$user['uid'], 'pparentid' => $groupuid));
                $user['canshowprivs'] = $canshowprivs ? xarModURL('roles','admin','showprivileges',array('uid'=>$user['uid'], 'pparentid' => $groupuid)):'';
                $user['canqueryprivs'] = $canshowprivs ? xarModURL('roles','admin','testprivileges',array('uid'=>$user['uid'], 'pparentid' => $groupuid)):'';
                $user['candisplay'] =xarModURL('roles','admin','displayrole', array('uid'=>$user['uid'], 'pparentid' => $groupuid));
                $user['canmail'] =xarModURL('roles','admin','createmail',array('uid'=>$user['uid'], 'pparentid' => $groupuid));
                if  ($data['canproxy'] &&  ($data['groupuid'] == $data['proxygroup']) && !in_array($user['uid'],$data['nodel'])) {
                    $user['canproxy'] =xarModURL('roles','admin','loginas',array('uid'=>$user['uid'], 'pparentid' => $groupuid));
                }
                $avalidableusers[] = $user['uid'];
              $users[] = $user;
        }
    } else {
        foreach($userlist as $user) {
                //defaults
                $user['candisplay'] ='';
                $user['canedit'] ='';
                $user['canadd'] ='';
                $user['candelete'] ='';
                $user['canproxy'] ='';
                $user['canmail'] ='';
                $user['candisplay'] ='';
                $user['canshowprivs'] ='';
                $user['canqueryprivs'] ='';
                $user['newpass'] ='';
                $user['isfrozen'] = false;
                $frozen = in_array($user['uid'],$data['noedit']);
                if ($frozen) {
                    $user['isfrozen'] =true;
                }
            if ($data['groupuid'] !=0) { //we have all users in a specific group
                $moderatelevel = in_array($data['groupuid'],$allowedgids); //user has at least moderate level
                $candelete = in_array($data['groupuid'],$deletegids);
                $canadd = in_array($data['groupuid'],$addgids);
                $canread = in_array($data['groupuid'],$readgids);
                $canmail = in_array($data['groupuid'],$canmailgids); //user has mail level
                $currenturl = xarServer::getCurrentURL();

                if  (xarSecurityCheck('DeleteRole',0,'Roles',$user['uid']) || $candelete) {
                    if (!in_array($user['uid'],$data['nodel'])) {
                        $user['candelete']  = xarModURL('roles','admin','deleterole',array('uid'=>$user['uid'], 'authid'=>$data['authid'],'pparentid' => $data['groupuid']));
                    }
                    $user['canedit']  = xarModURL('roles','admin','modifyrole',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    if ($data['generatenewpass']) {
                        $user['newpass'] = xarModURL('roles','admin','createpassword', array('uid'=>$user['uid'], 'groupuid' => $data['groupuid']));
                    }
                    $user['canadd'] =xarModURL('roles','admin','addrole',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    $user['canshowprivs'] =xarModURL('roles','admin','showprivileges',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    $user['canqueryprivs'] =xarModURL('roles','admin','testprivileges',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    $user['candisplay'] =xarModURL('roles','admin','displayrole', array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));

                } elseif  (xarSecurityCheck('AddRole',0,'Roles',$user['uid']) || $canadd) {
                    $user['canedit']  = xarModURL('roles','admin','modifyrole',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    if ($data['generatenewpass']) {
                        $user['newpass'] = xarModURL('roles','admin','createpassword', array('uid'=>$user['uid'], 'groupuid' => $data['groupuid']));
                    }
                    $user['canadd'] =xarModURL('roles','admin','addrole',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    $user['canshowprivs'] =xarModURL('roles','admin','showprivileges',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    $user['canqueryprivs'] =xarModURL('roles','admin','testprivileges',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    $user['candisplay'] = xarModURL('roles','admin','displayrole', array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));

                } elseif ($moderatelevel) {
                    $user['canedit']  = xarModURL('roles','admin','modifyrole',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                    if ($data['generatenewpass']) {
                        $user['newpass'] = xarModURL('roles','admin','createpassword', array('uid'=>$user['uid'], 'groupuid' => $data['groupuid']));
                    }
                    $user['candisplay'] =xarModURL('roles','admin','displayrole', array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));

                } elseif  (xarSecurityCheck('ReadRole',0,'Roles',$user['uid']) || $canread) {
                    $user['candisplay'] =xarModURL('roles','admin','displayrole', array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                }

                if  (($canmail||xarSecurityCheck('AdminRole',0,'Roles',$user['uid']) ) && !in_array($user['uid'],$data['noedit'])) {
                    $user['canmail'] =xarModURL('roles','admin','createmail',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                }
                if  ($data['canproxy'] &&  ($data['groupuid'] == $data['proxygroup']) && !in_array($user['uid'],$data['noedit'])) {
                    $user['canproxy'] =xarModURL('roles','admin','loginas',array('uid'=>$user['uid'], 'pparentid' => $data['groupuid']));
                }

            } else { //we need difference checks
                //is the user in any of the relevant groups for access?
                 $moderatelevel = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$allowedgids,'uid'=>$user['uid']));
                 $canmail = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$canmailgids,'uid'=>$user['uid']));
                 $canadd = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$addgids,'uid'=>$user['uid']));
                 $candelete = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$deletegids,'uid'=>$user['uid']));
                 $canread = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$readgids,'uid'=>$user['uid']));

                 if ($candelete || xarSecurityCheck('DeleteRole',0,'Roles',$user['uid']) ) {

                    $user['candisplay'] =xarModURL('roles','admin','displayrole', array('uid'=>$user['uid']));

                    $user['canedit']  = xarModURL('roles','admin','modifyrole',array('uid'=>$user['uid']));
                    $user['candelete']  = xarModURL('roles','admin','deleterole',array('uid'=>$user['uid'],'authid'=>$data['authid']));
                    if ($data['generatenewpass']) {
                        $user['newpass'] = xarModURL('roles','admin','createpassword', array('uid'=>$user['uid']));
                    }
                    $user['canadd'] =xarModURL('roles','admin','addrole', array('uid'=>$user['uid']));
                    $user['canshowprivs'] =xarModURL('roles','admin','showprivileges',array('uid'=>$user['uid']));
                    $user['canqueryprivs'] =xarModURL('roles','admin','testprivileges',array('uid'=>$user['uid']));

                } elseif ($canadd || xarSecurityCheck('AddRole',0,'Roles',$user['uid']) ) {
                    if ($data['generatenewpass']) {
                       $user['newpass'] = xarModURL('roles','admin','createpassword', array('uid'=>$user['uid']));
                    }
                    $user['canshowprivs'] =xarModURL('roles','admin','showprivileges',array('uid'=>$user['uid']));
                    $user['canadd'] =xarModURL('roles','admin','addrole', array('uid'=>$user['uid']));
                    $user['canqueryprivs'] =xarModURL('roles','admin','testprivileges',array('uid'=>$user['uid']));
                    $user['candisplay'] =xarModURL('roles','admin','displayrole', array('uid'=>$user['uid']));
                    $user['canedit']  = xarModURL('roles','admin','modifyrole',array('uid'=>$user['uid']));
                } elseif ($moderatelevel || xarSecurityCheck('ModerateRole',0,'Roles',$user['uid']))  {
                    if ($data['generatenewpass']) {
                        $user['newpass'] = xarModURL('roles','admin','createpassword', array('uid'=>$user['uid']));
                    }
                    $user['candisplay'] =xarModURL('roles','admin','displayrole', array('uid'=>$user['uid']));
                    $user['canedit']  = xarModURL('roles','admin','modifyrole',array('uid'=>$user['uid']));
                }

                if  (($canmail||xarSecurityCheck('AdminRole',0,'Roles',$user['uid']) ) && !in_array($user['uid'],$data['noedit'])) {
                    $user['canmail'] =xarModURL('roles','admin','createmail',array('uid'=>$user['uid']));
                }

                $user['canproxy'] ='';
            }
            $users[] = $user;
        }
    }
    $availableusers = array_unique($availableusers);
    if ($uid != 0) $data['title'] .= " ".xarML('of group')." ";

    //selstyle
    $data['selstyleoptions'] = array('0' => xarML('Simple'),
                           '1' => xarML('Tree'),
                          // '2' => xarML('Tabbed') //removed 2009-11-19
                           );

    // Load Template
    $data['uid']        = $uid;
    $data['users']      = $users;
    $data['changestatuslabel'] = xarML('Change Status');

    $data['removeurl']  = xarModURL('roles', 'admin','deleterole', array('roleid' => $uid, 'authid'=>$data['authid']));

    $filter['startnum'] = '%%';
    $filter['numitems'] = $numitems;
    $filter['group']      = isset($uid) && !empty($uid)? $uid: NULL;
    $filter['uid']      = $uid;
    $filter['type'] = ROLES_USERTYPE;
    $filter['state']    = $args['state'];
    $filter['search']   = $data['search'];
    $filter['order']    = $args['order'];
    $filter['sort']     = $args['sort'];

    $filter['pending'] = TRUE;

    $filteredusers = xarMod::apiFunc('roles','admin','filterroles',$filter);
    $data['pager']      = xarTplGetPager($startnum,
                                         $filteredusers,
                                         xarModURL('roles', 'admin', 'showusers',$filter),
                                         $numitems);



    //common admin menu and constants defined for template
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');
    $data['statecurrent'] = ROLES_STATE_CURRENT;
    $data['stateinactive']= ROLES_STATE_INACTIVE;
    $data['statenotvalidated'] = ROLES_STATE_NOTVALIDATED;
    $data['stateactive']= ROLES_STATE_ACTIVE;
    $data['statepending']= ROLES_STATE_PENDING;
    $data['usertype']= ROLES_USERTYPE;
    $data['grouptype']= ROLES_GROUPTYPE;
    $data['ptype'] = $data['grouptype'];
    $data['dummyimage'] = xarTplGetImage('blank.gif','base');

    $data['statusoptions'] = array(ROLES_STATE_INACTIVE=>xarML('Inactive'),
                                   ROLES_STATE_NOTVALIDATED => xarML('Not Validated'),
                                   ROLES_STATE_ACTIVE => xarML('Active'),
                                   ROLES_STATE_PENDING => xarML('Pending')
                                  );
    $data['stateoptions']  = array(ROLES_STATE_CURRENT => xarML('All')) + $data['statusoptions'];
    $data['sortimgclass'] = '';
    $data['sortimglabel'] = '';
    if ($data['sort'] == 'asc') {
        $data['sortimgclass'] = 'esprite xs-sorted-asc';
        $data['sortimglabel'] = xarML('Ascending');
    } else {
        $data['sortimgclass'] = 'esprite xs-sorted-desc';
         $data['sortimglabel'] = xarML('Descending');
    }
    //decide what image goes where
    $sortimage = array();

    $headerarray= array('xar_name','xar_uname','xar_email','xar_state','xar_date_reg');
    foreach ($headerarray as $headername) {
        $sortimage[$headername] = false;
        if ($data['order'] == $headername) $sortimage[$headername] = true;
    }

    $data['sortimage'] = $sortimage;
    $data['dsort'] = ($data['sort'] == 'asc') ? 'desc' : 'asc';
    return $data;

}
?>
