<?php
/**
 * Update site restrictions
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Update site restrictions
 *
 * @param string tab Part of the config to update
 * @param string returnurl  optional
 * @return bool true on success of update
 */
function base_admin_updaterestrictions()
{
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
    if(!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();

    $invalid = array();
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'restrict', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str:1:100', $data['returnurl'], Null, XARVAR_NOT_REQUIRED)) return;

    // Check for sitelock action submissions
    xarVarFetch('delete', 'isset', $delete, NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('add', 'isset', $add, NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('toggle_lock', 'isset', $toggle_lock, NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('save', 'isset', $save, NULL, XARVAR_NOT_REQUIRED);


    switch ($data['tab']) {
        case 'restrict':
            if (!xarVarFetch('disallowedemails', 'str:1', $disallowedemails, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('disallowedips', 'str:1', $disallowedips, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('disallowednames', 'str:1', $disallowednames, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('passrequirements', 'str:1', $passrequirements, '', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('passhelptext',     'str:0', $passhelptext, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('maxpasslength',    'int:0:', $maxpasslength, 0, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('minpasslength',    'int:0:', $minpasslength, 5, XARVAR_NOT_REQUIRED)) return;

            $disallowedemails = serialize($disallowedemails);
            xarModSetVar('roles', 'disallowedemails', $disallowedemails);
            $disallowedips = serialize($disallowedips);
            xarModSetVar('roles', 'disallowedips', $disallowedips);
            $disallowednames = serialize($disallowednames);
            xarModSetVar('roles', 'disallowednames', $disallowednames);

            xarModSetVar('roles', 'passrequirements',$passrequirements);
            xarModSetVar('roles', 'minpasslength',$minpasslength);
            xarModSetVar('roles', 'maxpasslength',$maxpasslength);
            xarModSetVar('roles', 'passhelptext',$passhelptext);
             xarLogMessage('BASE: Configuration settings for Restrictions were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
            $msg = xarML('Site and user restrictions settings were successfully updated.');
             xarTplSetMessage($msg,'status');
            break;
        case 'sitelock':

            if (!xarVarFetch('serialroles', 'str', $serialroles, NULL, XARVAR_NOT_REQUIRED)) return;
            $roles = unserialize($serialroles);
            $rolesCount = count($roles);
            if (!xarVarFetch('lockedoutmsg', 'str',   $lockedoutmsg, NULL, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('notifymsg',    'str',   $notifymsg,    NULL, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('toggle',       'str',   $toggle,       NULL, XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('notify',       'isset', $notify,       NULL, XARVAR_DONT_SET)) return;
            if (!xarVarFetch('killactive',   'checkbox', $killactive,  false, XARVAR_DONT_SET)) return;
            if (!isset($notify)) $notify = array();
            for ($i=0; $i<$rolesCount; $i++) $roles[$i]['notify'] = in_array($roles[$i]['uid'],$notify);
            $status = '';
            if (!empty($delete)) {
                // Old method
                if (!xarVarFetch('uid', 'int', $uid, NULL, XARVAR_DONT_SET)) return;

                // New method
                if (!empty($delete) && !isset($uid)) {
                    $uid = reset(array_keys($delete));

                }
                if (isset($uid)) {
                    for($i=0; $i < $rolesCount; $i++) {
                        if ($roles[$i]['uid'] == $uid) {
                            array_splice($roles,$i,1);
                            $group =  xarFindGroup($uid);
                            if (FALSE !== $group) {
                                $msg = xarML('Group "#(1)" was deleted from access list.', $group);
                                $status = 'status';
                            } else {
                                 $msg = xarML('Group with UID "#(1)" could not be found. The apparent group was removed from the access list.', $uid);
                                 $status = 'error';
                            }
                            break;
                        }
                    }
                // Write the configuration to disk
                $lockdata = array('roles'     => $roles,
                                  'message'   => $lockedoutmsg,
                                  'locked'    => $toggle,
                                  'notifymsg' => $notifymsg,
                                  'killactive' => $killactive);
                xarModSetVar('roles', 'lockdata', serialize($lockdata));

                xarTplSetMessage($msg,$status);
                }
            }  elseif (!empty($add)) {
                if (!xarVarFetch('newname', 'str', $newname, NULL, XARVAR_DONT_SET)) return;
                if (isset($newname)) {
                    $r = xaruFindRole($newname);
                    if (!$r) $r = xarFindRole($newname);
                    if($r) {
                        $newuid  = $r->getID();
                        $newname = $r->isUser() ? $r->getUser() : $r->getName();
                    } else {
                        $newuid = 0;
                        if (trim($newname) != '') {
                            $msg = xarML('Group "#(1)" could not be found. Please check the spelling and the group exists.', $newname);
                        } else {
                             $msg = xarML('Group name was empty. Please enter a group name to add to the access list.');
                        }
                        $status = 'error';
                    }
                    $newelement = array('uid' => $newuid, 'name' => $newname , 'notify' => TRUE);
                    if ($newuid != 0 && !in_array($newelement,$roles)) {
                        $roles[] = $newelement;
                        $msg = xarML('Group "#(1)" added to access list.', $newname);
                        $status = 'status';
                    }

                // Write the configuration to disk
                $lockdata = array('roles'     => $roles,
                                  'message'   => $lockedoutmsg,
                                  'locked'    => $toggle,
                                  'notifymsg' => $notifymsg,
                                  'killactive' => $killactive);
                xarModSetVar('roles', 'lockdata', serialize($lockdata));
                xarTplSetMessage($msg,$status);
                }
            } elseif (!empty($save)) {

                $lockdata = array('roles'     => $roles,
                                  'message'   => $lockedoutmsg,
                                  'locked'    => $toggle,
                                  'notifymsg' => $notifymsg,
                                  'killactive' => $killactive);
                xarModSetVar('roles', 'lockdata', serialize($lockdata));
                $msg = xarML('Site lock settings were updated.');
                xarTplSetMessage($msg,'status');
                xarResponseRedirect(xarModURL('base', 'admin', 'restrictions',array('tab' => $data['tab'])));
            }  elseif (!empty($toggle_lock)) {

                // turn the site on or off
                $toggle = $toggle ? 0 : 1;

                // Find the users to be notified
                // First get the roles
                $rolesarray = array();
                $rolemaker = new xarRoles();
                for($i=0; $i < $rolesCount; $i++) {
                    if($roles[$i]['notify'] == 1) {
                        $rolesarray[] = $rolemaker->getRole($roles[$i]['uid']);
                    }
                }
                //Check each if it is a user or a group
                $notify = array();
                foreach($rolesarray as $roletotell) {
                    if ($roletotell->isUser()) $notify[] = $roletotell;
                    else $notify = array_merge($notify,$roletotell->getUsers());
                }
                $admin = $rolemaker->getRole(xarModGetVar('roles','admin'));
                $mailinfo = array('subject' => 'Site Lock',
                                  'from' => $admin->getEmail()
                );

            // We locked the site
                if ($toggle == 1) {

                    // Clear the active sessions
                    if ($killactive == TRUE)
                    {
                        $spared = array();
                        for($i=0; $i < $rolesCount; $i++) $spared[] = $roles[$i]['uid'];
                        if(!xarMod::apiFunc('roles','admin','clearsessions', $spared)) {
                            $msg = xarML('Could not log out active users.');
                            xarTplSetMessage($mailinfo['message'],'error');
                        } else {
                            $mailinfo['message'] = xarML('All logged in users not in the access list have been logged out.');
                            xarTplSetMessage($mailinfo['message'],'status');
                        }
                    }
                    $mailinfo['message'] = xarML('The site "#(1)" has been LOCKED.',xarModGetVar('themes','SiteName'));
                    xarTplSetMessage($mailinfo['message'],'status');

                } else {
            // We unlocked the site

                  $mailinfo['message'] = xarML('The site "#(1)"  has been UNLOCKED.',xarModGetVar('themes','SiteName') );
                  xarTplSetMessage($mailinfo['message'],'status');
                }

                $mailinfo['message'] .= "\n\n" . $notifymsg;

                // Send the mails
                 $badmails = 0;
                foreach($notify as $recipient) {
                    $mailinfo['info'] = $recipient->getEmail();
                    if (!xarMod::apiFunc('mail','admin','sendmail', $mailinfo)) {
                        $badmails ++;
                    }
                    $msg = xarML('Notification emails have been sent.');
                     xarTplSetMessage($msg,'status');
                    if ($badmails > 0) {
                        $msg = xarML('There were #(1) emails that had send problems.', $badmails);
                         xarTplSetMessage($msg,'alert');
                    }

                }

                // Write the configuration to disk
                $lockdata = array('roles'     => $roles,
                                  'message'   => $lockedoutmsg,
                                  'locked'    => $toggle,
                                  'notifymsg' => $notifymsg,
                                  'killactive' => $killactive);
                if (xarModSetVar('roles', 'lockdata', serialize($lockdata))) {
                    $msg = xarML(' Email and lock default messages were successfully updated.');
                     xarTplSetMessage($msg,'status');
                }

                if($badmails) {
                    return xarTplModule('base','user','errors',array('errortype' => 'mail_failed', 'var1' => $badmails));
                }
            }
             xarLogMessage('BASE: Sitelock settings were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
            break;
    }

    // Call updateconfig hooks
    //xarMod::callHooks('module','updateconfig','base', array('module' => 'base'));

    if (isset($data['returnurl'])) {
        xarResponseRedirect($data['returnurl']);
    } else {
        xarResponseRedirect(xarModURL('base', 'admin', 'restrictions',array('tab' => $data['tab'])));
    }
    return true;
}

?>