<?php
/**
 * Test the email settings
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Test the email settings
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_sendtest()
{
    // Get parameters from whatever input we need
    if (!xarVarFetch('message', 'str:1:', $message,'', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('subject', 'str:1', $subject,'', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('email', 'email', $email, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('name', 'str:1', $name, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('emailcc', 'email', $emailcc, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('namecc', 'str:1', $namecc, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('emailbcc', 'email', $emailbcc, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('namebcc', 'str:1', $namebcc, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('recipients', 'str:1', $recipients, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ccrecipients', 'str:1', $ccrecipients, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('bccrecipients', 'str:1', $bccrecipients, '', XARVAR_NOT_REQUIRED)) return;
    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;

    // Security check
    if (!xarSecurityCheck('AdminMail',0)) return xarResponseForbidden();

    // Argument check
    $invalid = array();
    if (empty($email) && empty($recipients)) {
        $invalid['email'] = xarML('Email or Recipient list is required');
        $invalid['recipients'] = $invalid['email'];
    }
    if (empty($subject)) {
        $invalid['subject'] = xarML('Subject is required');
    }
    if (empty($message)) {
        $invalid['message'] = xarML('Message is required');
    }

    if (count($invalid) > 0) {
        $args =  array('invalid'=>$invalid,
                        'message'=>$message,
                        'email' =>$email,
                        'recipients'=>$recipients,
                        'subject'=>$subject,
                        'name'=>$name,
                        'emailcc'=>$emailcc,
                        'namecc'=>$namecc,
                        'emailbcc'=>$emailbcc,
                        'namebcc'=>$namebcc,
                        'ccrecipients'=>$ccrecipients,
                        'bccrecipients'=>$bccrecipients,
                        'authid'=> xarSecGenAuthKey()
                        );
        return xarTplModule('mail','admin','compose',$args,'compose');

    }
    if (empty($name)) {
        $name = xarModGetVar('mail', 'adminname');
    }

    if (!xarVarFetch('when', 'str:1', $when, '', XARVAR_NOT_REQUIRED)) return;
    if (!empty($when)) {
        $when .= ' GMT';
        $when = strtotime($when);
        $when -= xarMLS_userOffset() * 3600;
    } else {
        $when = 0;
    }

    if (isset($bccrecipients) && !empty($bccrecipients)) {
        $bccrecipientarray=explode(';',$bccrecipients);
        if (is_array($bccrecipientarray)) {
            foreach ($bccrecipientarray as $recipientkey=>$v) {
                $bcctemp[]=explode(',',$v);
            }
            foreach ($bcctemp as $recipient=>$values) {
                $bccrec[$values[0]]=isset($values[1])?$values[1]:'';
            }
        }
    $bccrecipients=$bccrec;
    }


      // process CC Recipient list
    $ccrecipientarray=array();
    $ccrec=array();
    $cctemp=array();
    if (!empty($ccrecipients)) {
        $ccrecipientarray=explode(';',$ccrecipients);
        if (is_array($ccrecipientarray)) {
            foreach ($ccrecipientarray as $recipientkey=>$v) {
                $cctemp[]=explode(',',$v);
            }
            foreach ($cctemp as $recipient=>$values) {
                $ccrec[$values[0]]=isset($values[1])?$values[1]:'';
            }
       }
    $ccrecipients=$ccrec;
    }


  // process Recipient list
    $recipientarray=array();
    $rec=array();
    $temp=array();
    if (!empty($recipients)) {
        $recipientarray=explode(';',$recipients);
        if (is_array($recipientarray)) {
            foreach ($recipientarray as $recipientkey=>$v) {
                $temp[]=explode(',',$v);
            }
            foreach ($temp as $recipient=>$values) {
                $rec[$values[0]]=isset($values[1])?$values[1]:'';
            }
       }
    $recipients=$rec;

    }


    $htmlmessage = $message;
    $args = array('info' => $email,
                'name' => $name,
                'ccinfo' => $emailcc,
                'ccname' => $namecc,
                'bccinfo' => $emailbcc,
                'bccname' => $namebcc,
                'recipients' => $recipients,
                'ccrecipients'=> $ccrecipients,
                'bccrecipients' => $bccrecipients,
                'subject' => $subject,
                'message' => $message,
                'htmlmessage' => $htmlmessage,
                'when' => $when);

    if (!xarMod::apiFunc('mail','admin', 'sendmail', $args)) {
        xarTplSetMessage(xarML('The test email was not successful. Mail send method had problems.'),'error');
    } else {
        xarLogMessage('MAIL: A test mail message was sent by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
        xarTplSetMessage(xarML('Test email was successfully queued.'),'status');
    }
    xarResponseRedirect(xarModURL('mail', 'admin', 'compose'));
    // Return
    return true;
}
?>
