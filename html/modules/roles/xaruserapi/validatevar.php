<?php
/**
 * Validate a user variable
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

/**
 * validate a user variable
 * @access public
 * @author Jo Dalle Nogare
 * @author Jonathan Linowes
 * @author Damien Bonvillain
 * @author Gregor J. Rothfuss
 * @since 1.23 - 2002/02/01
 * @param var - the variable to validate
 * @param type - the type of the validation to perform
 * @param uid - optional existing registered user - required if checking registered user
 * @param isgroup -optional to signify group check
 * possible type, value:
    'ip' (no var required),
    'email', email str
    'username', username str
    'displayname', displayname str
    'agreetoterms', not empty (checkbox)
    'pass1', password str
    'pass2', password str
 * @return empty string if the validation was successful, or invalid message otherwise
 */
function roles_userapi_validatevar($args)
{
    extract($args);

    if (empty($type)) {
        $type = 'email';
    }
    if (!isset($uid) || is_null($uid)) {
        $uid = 0;
    }
    $siteadmin = xarModGetVar('roles','admin');
    $invalid = "";
    switch ($type) {
       case 'ip':

            // check if the IP address is banned, and if so, throw an exception :)
            if (!isset($var)) {
                $ip = xarSessionGetIPAddress();
            } else {
                $ip = $var;
            }
            $disallowedips = xarModGetVar('roles ','disallowedips');
            if (!empty($disallowedips)) {
                $disallowedips = unserialize($disallowedips);
                $disallowedips = explode("\r\n", $disallowedips);
                //ensure spaces etc do not create an empty key value, as sometimes an empty IP might be valid but match
                if (isset($disallowedips[0]) && empty($disallowedips[0])) unset($disallowedips[0]);
                if (in_array ($ip, $disallowedips)) {
                    $invalid = xarML('Your IP is on the banned list');
                }
            }
            break;
        case 'username':
            $username = $var;

            // check if the username is empty
            if (empty($username)) {
                $invalid = xarML('You must provide a preferred username to continue.');

            // check for spaces in the username
            } elseif (preg_match("/[[:space:]]/",$username)) {
                $invalid = xarML('There is a space in the username');
            // check for invalid chars in username eg colon cannot be used
            } elseif (strrpos($username,':') > 0) {
                $invalid = xarML('You cannot use colons in your username');
            // check the length of the username
            } elseif (strlen($username) > 255) {
                $invalid = xarML('Your username is too long.');

            // check for spaces in the username (again ?)
            } elseif (strrpos($username,' ') > 0) {
                $invalid = xarML('There is a space in your username');

            } else {
                // check for duplicate usernames
                $user = xarMod::apiFunc('roles', 'user', 'get',
                                array('uname' => $username));

                if (is_array($user)) {

                    if ($uid != $user['uid']) {
                        unset($user);
                        $invalid = xarML('That username is already taken.');
                    }
                }
                //now check for disallowed

                if (empty($invalid)) {
                    // check for disallowed usernames
                    $disallowednames = xarModGetVar('roles','disallowednames');
                    if (!empty($disallowednames)) {
                        $disallowednames = unserialize($disallowednames);
                        $disallowednames = explode("\r\n", $disallowednames);
                        //make sure the existing user doesn't already have that name
                        $existingusername = xarUserGetVar('uname');
                        if (in_array ($username, $disallowednames) && (xarUserGetVar('uid') != $siteadmin)) {
                            $invalid = xarML('That username is either reserved or not allowed on this website');
                        }
                    }
                }
            }
            break;

        case 'displayname':
            $displayname = $var;
            $isgroup = isset($isgroup) ? $isgroup: false;
            if (empty($displayname)){
                $invalid = xarML('You must provide a name value to continue.');
            } elseif (xarModGetVar('roles','uniquedisplay') || $isgroup) {
                //check if there are duplicate Display names
                $userdisplay = xarMod::apiFunc('roles','user','get',array('name' => $displayname, 'type'=>$isgroup));

                if ($userdisplay != FALSE) {
                    if ($uid != $userdisplay['uid']) {
                        unset($userdisplay);
                         $invalid = xarML('That name is taken, please choose another.');
                    }
                }
            }

            if (empty($invalid)) {
                // check for disallowed displaynames
                $disallowednames = xarModGetVar('roles','disallowednames');
                if (!empty($disallowednames)) {
                    $disallowednames = unserialize($disallowednames);
                    $disallowednames = explode("\r\n", $disallowednames);

                    if (in_array($displayname, $disallowednames) && (xarUserGetVar('uid') != $siteadmin)) {
                        $invalid = xarML('That display name is either reserved or not allowed on this website');
                    }
                }

            }
            break;

        case 'agreetoterms':
            // kind of dumb, but for completeness
            if (empty($var)){
                $invalid = xarML('You must agree to the terms and conditions of this website to register an account.');
            }
            break;

        case 'password':
        case 'pass1':
            $pw = $var;
            $minpasslength = xarModGetVar('roles', 'minpasslength');
            $maxpasslength = xarModGetVar('roles', 'maxpasslength');
            $passhelp = xarModGetVar('roles', 'passhelptext');

            if (empty($uid)) {
                if (empty($pw) && !empty($minpasslength)) {
                    $invalid = xarML('Your password must be a minimum of #(1) characters to continue.', $minpasslength);
                } elseif (strlen($pw) < $minpasslength) {
                    $invalid = xarML('Your password must be a minimum of #(1) characters in length.', $minpasslength);
                } elseif (!empty($maxpasslength) && (strlen($pw) > $maxpasslength)) {
                    $invalid = xarML('Your password must be a maximum of #(1) characters in length.', $maxpasslength);
                }
            } elseif (!empty($uid) && !empty($pw)) {
               if (strlen($pw) < $minpasslength) {
                    $invalid = xarML('Your password must be a minimum of #(1) characters in length.', $minpasslength);
                } elseif (!empty($maxpasslength) && (strlen($pw) > $maxpasslength)) {
                    $invalid = xarML('Your password must be a maximum of #(1) characters in length.', $maxpasslength);
                }
            }
            if (empty($invalid) && !empty($pw)) { //password is ok till now, check the regex
                $passregex = xarModGetVar('roles', 'passrequirements');

                if (!empty($passregex)) {
                    preg_match($passregex,$var,$matches);
                    if (empty($matches)){
                         $invalid = !empty($passhelp)?xarML($passhelp):xarML('There was an error in your password');
                    }
                }
            }
            break;
        case 'pass2':
            $pass1 = $var[0];
            $pass2 = $var[1];
            if (!is_null($uid) && !empty($uid)) { //existing user
                if (strcmp($pass1, $pass2) != 0) {
                    $invalid = xarML('The passwords do not match');
                }
            } else {
                if ((empty($pass1)) || (empty($pass2))) {
                    $invalid = xarML('You must enter the same password twice');
                } elseif ($pass1 != $pass2) {
                    $invalid = xarML('The passwords do not match');
                }
            }
            break;

        case 'email':
        default:
            $email = $var;
            if (empty($email)){
                $invalid = xarML('You must provide a valid email address to continue.');
            } else {
                //check the email for valid syntax
                // all characters must be 7 bit ascii
                $length = strlen($email);
                $idx = 0;
                while($length--) {
                   $c = $var[$idx++];
                   if(ord($c) > 127){
                      $invalid = xarML('There is an error in your email address');
                   }
                }
                $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
                if(preg_match($regexp,$email)) {
                   //it's ok
                } else {
                     $invalid = xarML('There is an error in your email address');
                }

                if (empty($invalid) && xarModGetVar('roles','uniqueemail')) { //
                    // check for duplicate email address
                    $user = xarMod::apiFunc('roles', 'user', 'get',
                               array('email' => $email));
                    $useruid = $user['uid'];
                    if (($user != FALSE) && (isset($uid) && $uid !=$useruid)) { //must pass uid if the user is registered
                        unset($user);
                        unset($useruid);
                        $invalid = xarML('That email address is already registered.');
                    }
                }

                if (empty($invalid)) {
                    // check for disallowed email addresses
                    $disallowedemails = xarModGetVar('roles','disallowedemails');
                    if (!empty($disallowedemails)) {
                        $disallowedemails = unserialize($disallowedemails);
                        $disallowedemails = explode("\r\n", $disallowedemails);
                        if (in_array ($email, $disallowedemails)) {
                            $invalid = xarML('That email address is either reserved or not allowed on this website');
                        }
                    }
                }
            }
            break;
        case 'email2':
            $email = $var[0];
            $email2 = $var[1];
            if ((empty($email)) || (empty($email2))) {
                $invalid = xarML('You must enter the same email address twice');
            } elseif ($email != $email2) {
                $invalid = xarML('The email addresses do not match');
            }
          break;
        case 'url':
            // all characters must be 7 bit ascii
            $length = strlen($var);
            $idx = 0;
            while($length--) {
               $c = $var[$idx++];
               if(ord($c) > 127){
                 $invalid = xarML('The URL is invalid');
               }
            }
            $regexp = '/^([!\$\046-\073=\077-\132_\141-\172~]|(?:%[a-f0-9]{2}))+$/i';
            if(!preg_match($regexp, $var)) {
                return false;
            }
            $url_array = @parse_url($var);
            if(empty($url_array)) {
                 $invalid = xarML('The URL is invalid');
            } else {
                return !empty($url_array['scheme']);
            }
            break;
    }
    return $invalid;
}
?>