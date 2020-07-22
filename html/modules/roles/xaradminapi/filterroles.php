<?php
/**
 * View users in a group
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
 * Filter users for use in showusers list
 *
 * @param $args['pid'] group id
 * @return $users array containing uname, pid
 */
function roles_adminapi_filterroles($args)
{
    extract($args);
    //set this here so it doesn't appear in the URL
    $args['pending'] = TRUE;
    $groups    = xarMod::apiFunc('roles', 'user', 'getallgroups');
    $data['groups'] = array(); //used for access checking
    $allowedgids = array(); //used to test access to groups
    $canmailgids = array(); //used to test mail privs

    $deletegids = array();
    $addgids = array();  
    $readgids = array();
    //get groups this user has minimum access to - ie moderate
    foreach($groups as $group) {
        if (xarSecurityCheck('DeleteRole',0)) { 
            $data['groups'][]= $group;
            $allowedgids[] = $group['uid'];
            $canmailgids[] = $group['uid'];
        } else {
            //min access for viewing a group in a drop down
            if  (xarSecurityCheck('ModerateGroupRoles',0,'Group',$group['uid']) || xarSecurityCheck('ModerateRole',0,'Roles',$group['uid'])) {
                $data['groups'][]= $group;
                $allowedgids[] = $group['uid'];
    
            }
            if  (xarSecurityCheck('MailRoles',0,'Mail',$group['uid']) ) {
                $canmailgids[] = $group['uid'];
            }
        }       
    }

    $numitems = xarModGetVar('roles', 'itemsperpage');

    //gather arguments for the getall user call
    $userlist = xarMod::apiFunc('roles','user','getall', $args);    

    $data['totalselect'] = count($userlist);  
    // assemble the info for the display
    $users = array();
    //special groups and users
    $anon = _XAR_ID_UNREGISTERED;
    $everybody = xarModGetVar('roles','everybody');
    $myselfinfo = xarMod::apiFunc('roles','user','get',array('uname'=>'myself'));
    $myself = $myselfinfo['uid'];
    $administrators = xarUFindRole('Administrators');
    $adminguid = $administrators->uid;

    $data['noedit']= array($anon,$everybody,$myself,$adminguid);
    $uidaccess = array();   
    
    if (!isset($args['uid']) && isset($args['group']) ) {
        $args['uid'] = $args['group']; 
    }else {
        $args['uid'] = 0;
    }    
    $data['groupuid']=  $args['uid'];
    //Use the info we have so we don't have to call priv checks again
    //as well as privileges we have special groups/users that we cannot edit (no-edit groups)
    $data['generatenewpass'] = xarModGetVar('roles', 'askpasswordemail');
    $availableusers = array();
    if (xarSecurityCheck('DeleteRole',0)) {
        //quick return for admins
         $availableusers = count($userlist);
    } else {
        foreach($userlist as $user) {
            //defaults
       
            $user['isfrozen'] = false;
            $frozen = in_array($user['uid'],$data['noedit']);
            if ($frozen) {
                $user['isfrozen'] =true;
            }                
            if ($data['groupuid'] !=0) { //we have all users in a specific group 
                $moderatelevel = in_array($data['groupuid'],$allowedgids); //user has at least moderate level
                if ($moderatelevel) {
                      $availableusers[] = $user['uid'];
                    
                } 
            } else { //we need difference checks
                //is the user in any of the relevant groups for access?
                 $moderatelevel = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$allowedgids,'uid'=>$user['uid']));
    
                if ($moderatelevel || xarSecurityCheck('ModerateRole',0,'Roles',$user['uid']) )  { 
                                    $availableusers[] = $user['uid'];
                 
                }
    
                
            }
        }
        $uniqueusers = array_unique($availableusers);    
        $availableusers = count($uniqueusers);
    }

    return $availableusers;      
}

?>
