<?php
/**
 * Send email to a user
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Send email to a user
 *
 * @author  John Cox
 * @access  public
 * @param   int  uid is the uid of the user being sent
 * @param   string phase
 * @param   string return_url Set this url if you want to return to that url after the function has been finished
 * @return  mixed Array with data, true on success or void on failure
 * @throws  XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION'
 * @todo    handle empty subject and/or message?
 */
function roles_user_email($args)
{
    // we can only send emails to other members if we are logged in
    if(!xarUserIsLoggedIn())
    {
       $msg = xarML('You are not logged in, sending emails is not allowed.');
       return xarResponseForbidden($msg);
    }

    extract($args);

    if (!xarVarFetch('uid',   'id', $uid)) return;
    if (!xarVarFetch('phase', 'enum:modify:confirm', $phase, 'modify', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'str:1', $return_url, NULL, XARVAR_NOT_REQUIRED)) {return;}

    // If this validation fails, then do NOT send an e-mail, but
    // re-present the form to the user with an error message. Don't redirect,
    // just ensure the state is pulled back the start ('modify').
    $valid_flag = true;
    $error_message = '';
    $valid_flag &= xarVarFetch('subject', 'html:restricted', $subject);
    $valid_flag &= xarVarFetch('message', 'html:restricted', $message);

    if (!$valid_flag) {
        // The input failed validation.

        // Ensure we don't sent the e-mail.
        $phase = 'modify';

        // Catch the error message.
        //$error_message = xarErrorRender('text');

    }

    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;

    switch(strtolower($phase)) {
        case 'modify':
        default:
            // Get user information
            $data = xarMod::apiFunc(
                'roles', 'user', 'get',
                array('uid' => $uid)
            );

            if ($data == false) return;

            $data['subject'] = $subject;
            $data['message'] = $message;
            $data['error_message'] = $error_message;
            if (!empty($return_url))
                $data['return_url'] = $return_url;

            $data['authid'] = xarSecGenAuthKey('roles');

            xarTplSetPageTitle(xarML('Mail User'));
            break;

        case 'confirm':
            // Bug 3342: don't allow arbitrary sender and recipient name details to be passed in.
            //if (!xarVarFetch('fname','str:1:100',$fname)) return;
            //if (!xarVarFetch('femail','str:1:100',$femail)) return;
            //if (!xarVarFetch('name', 'str:1:100', $name)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            // Security Check
            if (!xarSecurityCheck('ReadRole')) return;

            // If the sender details have not been passed in to $args, then
            // fetch them from the current user now.
            if (!isset($fname) || !iseet($femail)) {
                // Get details of the sender.
                $fname = xarUserGetVar('name');
                $femail = xarUserGetVar('email');
            }

            list($message) = xarMod::callHooks('item', 'transform', $uid, array($message));

            // Get user information
            $data = xarMod::apiFunc('roles', 'user', 'get', array('uid' => $uid));

            if ($data == false) return;

            if (!xarMod::apiFunc(
                'mail', 'admin', 'sendmail',
                array(
                    'info'     => $data['email'],
                    'name'     => $data['name'],
                    'subject'  => $subject,
                    'message'  => $message,
                    'from'     => $femail,
                    'fromname' => $fname
                )
            )) return;

            if (!empty($return_url)) {
                xarResponseRedirect($return_url);
            }
            else {
                // lets update status and display updated configuration
                xarResponseRedirect(xarModURL('roles', 'user', 'view'));
            }

            break;
    }

    return $data;
}

?>