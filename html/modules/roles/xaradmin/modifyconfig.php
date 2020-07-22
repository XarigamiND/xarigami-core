<?php
/**
 * Modify configuration
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * modify configuration
 */
function roles_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminRole',0))  return xarResponseForbidden();
    if (!xarVarFetch('phase', 'str:1:100', $phase,       'modify',  XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('tab',   'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    switch (strtolower($phase)) {
        case 'modify':
        default:
            // get a list of everyone with admin privileges
            // TODO: find a more elegant way to do this
            // first find the id of the admin privilege
            $roles = new xarRoles();
            $role  = $roles->getRole(xarModGetVar('roles','admin'));
            $privs = array_merge($role->getInheritedPrivileges(),$role->getAssignedPrivileges());
            foreach ($privs as $priv)
            {
                if ($priv->getLevel() == 800)
                {
                    $adminpriv = $priv->getID();
                    break;
                }
            }

            $dbconn   = xarDB::$dbconn;
            $xartable = &xarDB::$tables;
            $acltable = xarDB::$prefix . '_security_acl';
            $query    = "SELECT xar_partid FROM $acltable
                         WHERE xar_permid   = ?";
            $result   = $dbconn->Execute($query, array((int) $adminpriv));
            if (!$result) return;


            // so now we have the list of all roles with *assigned* admin privileges
            // now we have to find which ones ar candidates for admin:
            // 1. They are users, not groups
            // 2. They inherit the admin privilege
            $admins = array();
            while (!$result->EOF)
            {
                list($id) = $result->fields;
                $role     = $roles->getRole($id);
                $admins[] = $role;
                $admins   = array_merge($admins,$role->getDescendants());
                $result->MoveNext();
            }

            $siteadmins = array();
            $adminids = array();
            foreach ($admins as $admin)
            {
                if($admin->isUser() && !in_array($admin->getID(),$adminids)){
                    $siteadmins [$admin->getID()] =  $admin->getName();
                }
            }

            // create the dropdown of groups for the template display
            // get the array of all groups
            // remove duplicate entries from the list of groups
            $groups = array();
            $names  = array();
            $grouplist = array();
            foreach($roles->getgroups() as $temp) {
                $nam = $temp['name'];
                if (!in_array($nam, $names)) {
                   array_push($names, $nam);
                   array_push($groups, $temp);
                }
                $grouplist[$nam]= $nam;
            }

            $data['grouplist'] = $grouplist;

            /* No longer required, catered for in Registration module
            $checkip = xarModGetVar('roles', 'disallowedips');
            if (empty($checkip)) {
                $ip = serialize('10.0.0.1');
                xarModSetVar('roles', 'disallowedips', $ip);
            }
            */
            $data['siteadmins']   = $siteadmins;
            $data['defaultgroup'] = xarModGetVar('roles', 'defaultgroup');
            $data['groups']       = $groups;

            $data['authid']       = xarSecGenAuthKey('roles');
            $data['updatelabel']  = xarML('Update Roles Configuration');
            $hooks = array();

            switch ($data['tab']) {

                case 'hooks':
                    // Item type 0 is the default itemtype for 'user' roles.
                    $hooks = xarMod::callHooks('module', 'modifyconfig', 'roles',
                                             array('module' => 'roles',
                                                   'itemtype' => 0));
                    break;
                case 'grouphooks':
                    // Item type 1 is the (current) itemtype for 'group' roles.
                    $hooks = xarMod::callHooks('module', 'modifyconfig', 'roles',
                                             array('module' => 'roles',
                                                   'itemtype' => 1));
                    break;
                default:
                    break;
            }

            $data['hooks'] = $hooks;
          //  $data['emails'] = unserialize(xarModGetVar('roles', 'disallowedemails'));
         //   $data['disallowedips'] = unserialize(xarModGetVar('roles', 'disallowedips'));
         //   $data['disallowednames'] = unserialize(xarModGetVar('roles', 'disallowednames'));
            $data['defaultauthmod']     = 'authsystem'; //always set at authsystem
            $data['defaultregmod']      = xarModGetVar('roles', 'defaultregmodule');
            $data['defaultproxy']      = xarModGetVar('roles', 'defaultproxy');
            $data['proxygroup']      = xarModGetVar('roles', 'proxygroup');
            $data['requirelogin']      = xarModGetVar('roles', 'requirelogin');
            $data['allowuserhomeedit']  = xarModGetVar('roles', 'allowuserhomeedit');
            $data['requirevalidation']  = xarModGetVar('roles', 'requirevalidation');
            $data['usernameurls']       = xarModGetVar('roles', 'usernameurls');
            $data['shorturls']          = xarModGetVar('roles', 'SupportShortURLs');
            $data['useModuleAlias']     = xarModGetVar('roles', 'useModuleAlias');
            $data['aliasname']         = xarModGetVar('roles', 'aliasname');
            $data['itemsperpage']   = xarModGetVar('roles', 'itemsperpage');
            $data['siteadmin'] = xarModGetVar('roles','admin');
            $data['advpasswordreset'] = xarModGetVar('roles', 'advpasswordreset');
            $data['advresetemailreq'] = xarModGetVar('roles', 'advresetemailreq')? xarModGetVar('roles', 'advresetemailreq'):0;
            $data['advresetnamereq'] = xarModGetVar('roles', 'advresetnamereq')?xarModGetVar('roles', 'advresetnamereq'):0;
            $data['resetexpiry'] = xarModGetVar('roles', 'resetexpiry')?xarModGetVar('roles', 'resetexpiry'):0;
            $data['uniqueemail'] = xarModGetVar('roles', 'uniqueemail');
            $data['passrequirements'] = xarModGetVar('roles', 'passrequirements')?xarModGetVar('roles', 'passrequirements'):'';
            $data['passhelptext'] = xarModGetVar('roles', 'passhelptext')?xarModGetVar('roles', 'passhelptext'):'';
            $data['minpasslength'] = xarModGetVar('roles', 'minpasslength')?xarModGetVar('roles', 'minpasslength'):0;
            $data['maxpasslength'] = xarModGetVar('roles', 'maxpasslength')?xarModGetVar('roles', 'maxpasslength'):0;
            $data['searchbyemail'] = xarModGetVar('roles', 'searchbyemail');
            $data['usersendemails'] = xarModGetVar('roles', 'usersendemails');
            $data['usereditaccount'] = xarModGetVar('roles', 'usereditaccount');
            $data['firstloginurl'] = xarModGetVar('roles', 'firstloginurl')?xarModGetVar('roles', 'firstloginurl'):'';
            $data['anonurl'] = xarModGetVar('roles', 'anonurl')?xarModGetVar('roles', 'anonurl'):'';

            $data['loginredirect'] = xarModGetVar('roles', 'loginredirect');
            $data['allowuserhomeedit']  = xarModGetVar('roles', 'allowuserhomeedit');
            $data['setuserhome'] = xarModGetVar('roles', 'setuserhome');
            $data['allowexternalurl'] = xarModGetVar('roles', 'allowexternalurl');
            $data['uniquedisplay'] = xarModGetVar('roles', 'uniquedisplay');
            $data['requiredisplayname'] = xarModGetVar('roles', 'requiredisplayname');
            $data['uniqueemail']        = xarModGetVar('roles', 'uniqueemail');
            // Deprecated and replaced with dropdown options
            $data['memberliststate'] =  xarModGetVar('roles', 'memberliststate') ?xarModGetVar('roles', 'memberliststate') :0;

            $data['memberlistoptions'] = array (
                                        0 => xarML('No Menu Item - No Member List'),
                                        1 => xarML('No Menu Item - Full Member List'),
                                        2 => xarML('No Menu Item - Member List Restricted'),
                                        3 => xarML('Roles Menu Item - Full Member List'),
                                        4 => xarML('Roles Menu Item - Member List Restricted'),
                                        );
            $data['displayrolelist'] = xarModGetVar('roles', 'displayrolelist');

            //check for roles hook in case it's set independently elsewhere
            if (xarMod::isHooked('roles', 'roles')) {
                xarModSetVar('roles','usereditaccount',true);
            } else {
                xarModSetVar('roles','usereditaccount',false);
            }
            //create allowed groups for proxylogin
            $allowedproxied = array();
            $allowedproxiers = array();
            foreach ($groups as $k=>$v) {
                $groupid = $v['uid'];
                if ($groupid != 1 && $groupid !=4) { //Everybody, Admins
                    $allowedproxied[$groupid] = $v['name'];
                }
                if ($groupid != 1) { //Everybody
                    $allowedproxiers[$groupid] = $v['name'];
                }
            }
            $data['allowedproxiers'] =$allowedproxiers ;
            $data['allowedproxied'] =$allowedproxied ;

            break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            switch ($data['tab']) {
                case 'general':
                    if (!xarVarFetch('itemsperpage',      'str:1:4:', $itemsperpage,     '20', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('defaultregmodule',  'int:0:',   $defaultregmodule, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('shorturls',         'checkbox', $shorturls,        false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('usernameurls',      'checkbox', $usernameurls,     false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('siteadmin',         'int:1',    $siteadmin,        xarModGetVar('roles','admin'), XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultgroup',      'str:1',    $defaultgroup,     'Users', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('aliasname',         'str:1:',   $aliasname, '',   XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('modulealias',        'checkbox', $modulealias,    false,XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('requirelogin',        'checkbox', $requirelogin,    false,XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('advpasswordreset',  'checkbox', $advpasswordreset, false,  XARVAR_NOT_REQUIRED)) return;
                   if (!xarVarFetch('advresetemailreq',  'checkbox', $advresetemailreq, false,  XARVAR_NOT_REQUIRED)) return;
                   if (!xarVarFetch('advresetnamereq',  'checkbox', $advresetnamereq, false,  XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('resetexpiry',       'int:0:',   $resetexpiry, 0, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('defaultproxy',      'int:0',    $defaultproxy,     0, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('proxygroup',      'int:0',    $proxygroup,     0, XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;


                    if (!xarVarFetch('passrequirements', 'str:1', $passrequirements, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                    if (!xarVarFetch('passhelptext',     'str:0', $passhelptext, '', XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('maxpasslength',    'int:0:', $maxpasslength, 0, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('minpasslength',    'int:0:', $minpasslength, 5, XARVAR_NOT_REQUIRED)) return;

                    xarModSetVar('roles', 'passrequirements',$passrequirements);
                    xarModSetVar('roles', 'minpasslength',$minpasslength);
                    xarModSetVar('roles', 'maxpasslength',$maxpasslength);
                    xarModSetVar('roles', 'passhelptext',$passhelptext);


                    xarModSetVar('roles', 'itemsperpage', $itemsperpage);
                    xarModSetVar('roles', 'defaultregmodule', $defaultregmodule);
                    xarModSetVar('roles', 'defaultgroup', $defaultgroup);
                    xarModSetVar('roles', 'SupportShortURLs', $shorturls);
                    xarModSetVar('roles', 'admin', $siteadmin);
                    xarModSetVar('roles', 'advpasswordreset', $advpasswordreset);
                    xarModSetVar('roles', 'advresetemailreq', $advresetemailreq);
                    xarModSetVar('roles', 'advresetnamereq', $advresetnamereq);
                    xarModSetVar('roles', 'resetexpiry', $resetexpiry);
                    xarModSetVar('roles', 'usernameurls', $usernameurls);
                    xarModSetVar('roles', 'defaultproxy', $defaultproxy);
                    xarModSetVar('roles', 'proxygroup', $proxygroup);
                    xarModSetVar('roles', 'requirelogin', $requirelogin);
                    if (isset($aliasname) && trim($aliasname)<>'') {
                        xarModSetVar('roles', 'useModuleAlias', $modulealias);
                    } else{
                         xarModSetVar('roles', 'useModuleAlias', 0);
                    }
                    $currentalias = xarModGetVar('roles','aliasname');
                    $newalias = trim($aliasname);
                    /* Get rid of the spaces if any, it's easier here and use that as the alias*/
                    if ( strpos($newalias,'_') === FALSE )
                    {
                        $newalias = str_replace(' ','_',$newalias);
                    }
                    $hasalias= xarModGetAlias($currentalias);
                    $useAliasName= xarModGetVar('roles','useModuleAlias');

                    // if a new one is set or if there is an old one there and we don't want to use alias anymore
                    if ($useAliasName && !empty($newalias)) {
                         if ($aliasname != $currentalias)
                         /* First check for old alias and delete it */
                            if (isset($hasalias) && ($hasalias =='roles')){
                                xarModDelAlias($currentalias,'roles');
                            }
                            /* now set the new alias if it's a new one */
                            $newalias = xarModSetAlias($newalias,'roles');
                            if (!$newalias) { //name already taken so unset
                                 xarModSetVar('roles', 'aliasname', '');
                                 xarModSetVar('roles', 'useModuleAlias', false);
                            } else { //it's ok to set the new alias name
                                xarModSetVar('roles', 'aliasname', $aliasname);
                                xarModSetVar('roles', 'useModuleAlias', $modulealias);
                            }
                    } else {
                       //remove any existing alias and set the vars to none and false
                            if (isset($hasalias) && ($hasalias =='roles')){
                                xarModDelAlias($currentalias,'roles');
                            }
                            xarModSetVar('roles', 'aliasname', '');
                            xarModSetVar('roles', 'useModuleAlias', false);
                    }
                    $msg = xarML('Roles general configuration settings have been updated.');
                    xarTplSetMessage($msg,'status');
                    xarLogMessage('ROLES: Configuration settings in General were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                     break;
                case 'restrictions':
                  //   if (!xarVarFetch('disallowedemails', 'str:1', $disallowedemails, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                  //  if (!xarVarFetch('disallowedips', 'str:1', $disallowedips, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                  //  if (!xarVarFetch('disallowednames', 'str:1', $disallowednames, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
                                    //$disallowedemails = serialize($disallowedemails);
                    //xarModSetVar('roles', 'disallowedemails', $disallowedemails);
                    //$disallowedips = serialize($disallowedips);
                   // xarModSetVar('roles', 'disallowedips', $disallowedips);
                   // $disallowednames = serialize($disallowednames);
                   // xarModSetVar('roles', 'disallowednames', $disallowednames);

                case 'hooks':
                    // Role type 'user' (itemtype 0).
                    xarMod::callHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => 0));
                   xarLogMessage('ROLES: Configuration settings for User hooks  were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                     $msg = xarML('Roles hook settings have been updated.');
                    xarTplSetMessage($msg,'status');
                    break;
                case 'grouphooks':
                    // Role type 'group' (itemtype 1).
                    xarMod::callHooks('module', 'updateconfig', 'roles',
                                    array('module' => 'roles',
                                          'itemtype' => 1));
                    break;
                    xarLogMessage('ROLES: Configuration settings for Group hooks  were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                case 'memberlist':
                    if (!xarVarFetch('uniqueemail',      'checkbox', $uniqueemail, true, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('uniquedisplay',      'checkbox', $uniquedisplay, true, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('requiredisplayname',      'checkbox', $requiredisplayname, true, XARVAR_NOT_REQUIRED)) return;

                    if (!xarVarFetch('searchbyemail',    'checkbox', $searchbyemail,     false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('displayrolelist',  'checkbox', $displayrolelist,   false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('usersendemails',   'checkbox', $usersendemails,    false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('usereditaccount',  'checkbox', $usereditaccount,   true,  XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('userhomeedit',     'checkbox', $userhomeedit,      false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('allowexternalurl', 'checkbox', $allowexternalurl,  false, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('loginredirect',    'checkbox', $loginredirect,     true,  XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('requirevalidation','checkbox', $requirevalidation, true,  XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('memberliststate',  'int:0:5', $memberliststate,  0, XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('firstloginurl',    'pre:trim:passthru:str:1:255', $firstloginurl, '',  XARVAR_NOT_REQUIRED)) return;
                    if (!xarVarFetch('anonurl',          'pre:trim:passthru:str:1:255', $anonurl, '',  XARVAR_NOT_REQUIRED)) return;
                    xarModSetVar('roles', 'uniqueemail',$uniqueemail);
                    xarModSetVar('roles', 'uniquedisplay',$uniquedisplay);
                    xarModSetVar('roles', 'requiredisplayname',$requiredisplayname);

                    xarModSetVar('roles', 'searchbyemail', $searchbyemail); //search by email
                    xarModSetVar('roles', 'usersendemails', $usersendemails);
                    xarModSetVar('roles', 'displayrolelist', $displayrolelist); //display member list in Roles menu links
                    xarModSetVar('roles', 'usereditaccount', $usereditaccount); //allow users to edit account
                    xarModSetVar('roles', 'allowexternalurl', $allowexternalurl); //allow users to set external urls for home page
                    xarModSetVar('roles', 'loginredirect', $loginredirect); //search by email
                    xarModSetVar('roles', 'requirevalidation', $requirevalidation); //require revalidation if email changed
                    xarModSetVar('roles', 'firstloginurl', $firstloginurl);
                    xarModSetVar('roles', 'memberliststate', $memberliststate);
                     xarModSetVar('roles', 'anonurl', $anonurl);

                    if (xarModGetVar('roles', 'setuserhome')==true) { //we only want to allow option of users editing home page if we are using homepages
                       $allowuserhomeedit = $userhomeedit ==true ? true:false;
                    }else {
                        $allowuserhomeedit=false;
                    }
                    xarModSetVar('roles', 'allowuserhomeedit', $allowuserhomeedit); //allow users to set their own homepage
                    if ($usereditaccount) {
                        //check and hook Roles to roles if not already hooked
                         if (!xarMod::isHooked('roles', 'roles')) {
                         xarMod::apiFunc('modules','admin','enablehooks',
                                 array('callerModName' => 'roles',
                                       'hookModName'   => 'roles'));
                         }
                    } else {
                         //unhook roles from roles
                         if (xarMod::isHooked('roles', 'roles')) {
                         xarMod::apiFunc('modules','admin','disablehooks',
                                 array('callerModName' => 'roles',
                                       'hookModName'   => 'roles'));
                         }
                   }
                    $msg = xarML('Roles member settings have been updated.');
                    xarTplSetMessage($msg,'status');
                   xarLogMessage('ROLES: Configuration settings for Members settings were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                    break;
            }

//            if (!xarVarFetch('allowinvisible', 'checkbox', $allowinvisible, false, XARVAR_NOT_REQUIRED)) return;
            // Update module variables
//            xarModSetVar('roles', 'allowinvisible', $allowinvisible);

            xarResponseRedirect(xarModURL('roles', 'admin', 'modifyconfig',array('tab' => $data['tab'])));
            // Return
            return true;
            break;

        case 'links':
            switch ($data['tab']) {
                case 'duvs':
                    $duvarray = array('setuserhome'       => 'userhome',
                                      'setprimaryparent'  => 'primaryparent',
                                      'setpasswordupdate' => 'passwordupdate',
                                      'setuserlastlogin'  => 'userlastlogin',
                                      'setuserlastvisit'  => 'userlastvisit',
                                      'setusertimezone'   => 'usertimezone');
                    foreach ($duvarray as $duv=>$userduv) {
                        if (!xarVarFetch($duv, 'int', $$duv, null, XARVAR_DONT_SET)) return;
                        if (isset($$duv)) {
                            if ($$duv) {
                                xarModSetVar('roles',$duv, true);
                                $msg = xarML('"#(1)" roles variable has been activated.',$userduv);
                                xarTplSetMessage($msg,'status');
                                if ($userduv =='primaryparent') { // let us set it to the default Role
                                    $defaultrole=xarModGetVar('roles','defaultgroup');
                                    xarModSetVar('roles','primaryparent', $defaultrole);
                                }elseif ($userduv =='usertimezone') {//set to the default site timezone
                                    $defaultzone = xarConfigGetVar('Site.Core.TimeZone');
                                    if (!isset($defaultzone) || empty($defaultzone)) {
                                        xarConfigSetVar('Site.Core.TimeZone','Europe/London');
                                        $defaultzone = xarConfigGetVar('Site.Core.TimeZone');
                                    }
                                    $timeinfo = xarMod::apiFunc('base','user','timezones', array('timezone' => $defaultzone));
                                    if (!is_array($timeinfo)){ //we still need to set this to something
                                        xarConfigSetVar('Site.Core.TimeZone','Etc/UTC');
                                        $defaultzone = xarConfigGetVar('Site.Core.TimeZone');
                                    }
                                    //And try again
                                    $timeinfo = xarMod::apiFunc('base','user','timezones', array('timezone' => $defaultzone));
                                    list($hours,$minutes) = explode(':',$timeinfo[0]);
                                    $offset               = (float) $hours + (float) $minutes / 60;
                                    $timeinfoarray        = array('timezone' => $defaultzone, 'offset' => $offset);
                                    $defaultusertime      = serialize($timeinfoarray);
                                    xarModSetVar('roles','usertimezone', $defaultusertime);
                                }else {
                                   xarModSetVar('roles', $userduv, '');
                                }
                            } else {
                                xarModSetVar('roles',$duv, false);
                                $msg = xarML('"#(1)" roles variable has been set off.',$userduv);
                                 xarTplSetMessage($msg,'status');
                            }
                        }
                    }
                    xarLogMessage('ROLES: Configuration settings for User Variables  were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
                    break;
                }
        break;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');
    $data['authid'] = xarSecGenAuthKey('roles');
    return $data;
}
?>
