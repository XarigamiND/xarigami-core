<?php
/**
 * Send emails to users
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
 * Send emails to users by mailtype
 *
 * Ex: Lost Password, Confirmation
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] array of uid of the user(s) array($uid => '1')
 * @param $args['mailtype'] type of the message to send (confirmation, deactivation, ...)
 * @param $args['message'] the message of the mail (optionnal)
 * @param $args['subject'] the subject of the mail (optionnal)
 * @param $args['pass'] new password of the user (optionnal)
 * @param $args['ip'] ip adress of the user (optionnal)
 * @returns bool
 * @return true on success, false on failures
 * @throws BAD_PARAM
 */
function roles_adminapi_senduseremail($args)
{

    // Send Email
    extract($args);
    if ((!isset($uid)) || (!isset($mailtype))) {
        $msg = xarML('Wrong arguments to roles_adminapi_senduseremail. uid: #(1) type: #(2)',$uid,$mailtype);
        throw new BadyParameterException(null,$msg);
    }

    // Get the predefined email if none is defined
    $strings = xarMod::apiFunc('roles','admin','getmessagestrings', array('module' => 'roles','template' => $mailtype));

    $vars  = xarMod::apiFunc('roles','admin','getmessageincludestring', array('module' => 'roles','template' => 'message-vars'));

    if (!isset($subject)) $subject = xarTplCompileString($vars . $strings['subject']);
    if (!isset($message)) $message = xarTplCompileString($vars . $strings['message']);

    //Get the common search and replace values
    //if (is_array($uid)) {
        foreach ($uid as $userid => $val) {
            ///get the user info
            $user = xarMod::apiFunc('roles','user','get', array('uid' => $userid));
            if (!isset($pass)) $pass = '';
            if (!isset($ip)) $ip = '';
            if (isset($user['valcode'])) $validationlink = xarServer::getBaseURL() . "val.php?v=".$user['valcode']."&u=".$userid;
            else $validationlink = '';

            //user specific data
            $data = array('myname' => $user['name'],
                          'name' => $user['name'],
                          'myusername' => $user['uname'],
                          'username' => $user['uname'],
                          'myemail' => $user['email'],
                          'email' => $user['email'],
                          'mystate' => $user['state'],
                          'state' => $user['state'],
                          'mypassword' => $pass,
                          'password' => $pass,
                          'myipaddress' => $ip,
                          'ipaddress' => $ip,
                          'myvalcode' => $user['valcode'],
                          'valcode' => $user['valcode'],
                          'myvalidationlink' => $validationlink,
                          'validationlink' => $validationlink,
                          'recipientname' => $user['name']);

            // retrieve the dynamic properties (if any) for use in the e-mail too
            if (xarMod::isHooked('dynamicdata','roles')) {
                // get the Dynamic Object defined for this module and item id
                $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                         array('module' => 'roles',
                                               // we know the item id now...
                                               'itemid' => $userid));
                if (isset($object) && !empty($object->objectid)) {
                    // retrieve the item itself
                    $itemid = $object->getItem();
                    if (!empty($itemid) && $itemid == $userid) {
                        // get the Dynamic Properties of this object
                        $properties =& $object->getProperties();
                        foreach (array_keys($properties) as $key) {
                            // add the property name/value to the search/replace lists
                            if (isset($properties[$key]->value)) {
                                $data[$key] = $properties[$key]->value; // we'll use the raw value here, not ->showOutput();
                            }
                        }
                    }
                }
            }

            $subject = xarTplString($subject, $data);
            $message = xarTplString($message, $data);
            // TODO Make HTML Message.
            // Send confirmation email

            $useHtml = xarModGetVar('roles', 'usehtmlmail');
            if (empty($useHtml)) $useHtml = false;

            if ($useHtml) {
                // Send it as HTML
                if (!xarMod::apiFunc('mail', 'admin', 'sendhtmlmail',
                               array('info' => $user['email'],
                                     'name' => $user['name'],
                                     'subject' => $subject,
                                     'message' => $message,
                                     'htmlmessage' => $message))) return false;
            } else {
                // Send it as plain text
                if (!xarMod::apiFunc('mail', 'admin', 'sendmail',
                               array('info' => $user['email'],
                                     'name' => $user['name'],
                                     'subject' => $subject,
                                     'message' => $message))) return false;
            }
    }
    xarLogMessage('ROLES: An email of type '.$mailtype.' was sent to user '. $userid.'  by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);

    return true;
}

?>
