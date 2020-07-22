<?php
/**
 * Send mail
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 * @author John Cox
 */

/**
 * This is a private utility function that is called to send mail
 * It is used by public functions sendmail() and sendhtmlmail()
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param  $ 'info' is the email address we are sending (required)
 * @param  $ 'name' is the name of the email receipitent (optional)
 * @param  $ 'recipients' is an array of recipients (required) // NOTE: $info or $recipients is required, not both
 * @param  $ 'ccinfo' is the email address we are sending (optional)
 * @param  $ 'ccname' is the name of the email recipient (optional)
 * @param  $ 'ccrecipients' is an array of cc recipients (optional)
 * @param  $ 'bccinfo' is the email address we are sending (required)
 * @param  $ 'bccname' is the name of the email recipient (optional)
 * @param  $ 'bccrecipients' is an array of bcc recipients (optional)
 * @param  $ 'subject' is the subject of the email (required)
 * @param  $ 'message' is the body of the email (required)
 * @param  $ 'htmlmessage' is the html body of the email
 * @param  $ 'priority' is the priority of the message
 * @param  $ 'encoding' is the encoding of the message
 * @param  $ 'wordwrap' is the column width of the message
 * @param  $ 'from' is who the email is from
 * @param  $ 'fromname' is the name of the person the email is from
 * @param  $ 'frombehalf' name used as From in 'on behalf of' email (optional)
 * @param  $ 'attachName' is the name of an attachment to a message
 * @param  $ 'attachPath' is the path of the attachment
 * @param  $ 'htmlmail' is set to true for an html email
 * @param  $ 'usetemplates' set to true to use templates in xartemplates
 * @param  $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi__sendmail($args)
{
    if (xarModGetVar('mail', 'suppresssending') ==1) return true;
// Get arguments from argument array

    extract($args);

    // Check for required arguments
    $invalid = array();
    if (!isset($info) && !isset($recipients)) {
        $invalid[] = 'info/recipients';
    }
    if (!isset($subject)) {
        $invalid[] = 'subject';
    }
    if (!isset($message)) {
        $invalid[] = 'message';
    }

    if (count($invalid) > 0) {
        $msg = xarML('Wrong arguments to mail_adminapi', join(', ', $invalid), 'admin', '_sendmail', 'Mail');
        throw new BadParameterException(null,$msg);
    }

    if (!empty($when) && $when > time() && xarMod::isAvailable('scheduler')) {
        if (xarMod::apiFunc('mail','admin','_queuemail', $args)) {
            // we're done here
            return true;
        }
    }

    // Global search and replace %%text%%
    $replace = xarMod::apiFunc('mail', 'admin','replace',
                             array('message'        => $message,
                                   'subject'        => $subject,
                                   'htmlmessage'    => $htmlmessage));

    $subject = $replace['subject'];
    $message = $replace['message'];
    $htmlmessage = $replace['htmlmessage'];


    // Bug 4219 calls this out for the silly safe mode.  That said, I am not sure we want
    // to be doing this since mail could be from a user on the site.
    // so it be commented out for the time being.
    //ini_set("sendmail_from", $from);

    include_once 'modules/mail/xarclass/class.phpmailer.php';
    $mail = new phpmailer();
    $mail->PluginDir = 'modules/mail/xarclass/';
    $mail->ClearAllRecipients();

    //check for 'on behalf emails'
    //Issue xgami-000347

    if (xarModGetVar('mail','onbehalf')) {
        //was going to comment this testing out but it's required in too many sites
        // so that we can customise to an extent where the mail is from - eg different @domain.com emails
        //get the website host
        $domainname = parse_url(xarServer::getCurrentURL(),PHP_URL_HOST);
        //get the email host (bit after the @)
        //perhaps this is too simple - review?
        $pattern = '/((?:[\w]+\.)+)([a-zA-Z]{2,4})/';
        $senderemaildomain = preg_match($pattern,$from,$match);
        //let's take the first greediest match
        $senderdomain = is_array($match)?$match[0]:'';
        //check against the domainname - will cater for 3 part domains
        //TODO - but not if in email and not in site domain name
        //if not from the domain do the 'on behalf of'
        if (!stristr($domainname,$senderdomain)) {
               //set email from
            $newfrom = xarModGetVar('mail','adminmail');
            $newfromname = isset($frombehalf) && !empty($frombehalf) ? $frombehalf : xarModGetVar('mail','adminname');
             //set sender name
            $onbehalf = isset($fromname) ? xarML('on behalf of ').$fromname : xarML('on behalf of ').$from;
            $newfromname = $newfromname.' '.$onbehalf;
            $mail->AddReplyTo($from, $fromname);
            $fromname = $newfromname;
            $from = $newfrom;
        }

    }



    // Set default language path to English.  This is necessary as
    // phpmailer will set an invalid path to the language directory
    // and throw an error.
    $mail->SetLanguage("en", "modules/mail/xarclass/language/");

    // Get type of mail server
    $serverType = xarModGetVar('mail', 'server');

    switch($serverType) {
        case 'smtp':
            $mail->IsSMTP(); // telling the class to use SMTP
            $mail->Host = xarModGetVar('mail', 'smtpHost'); // SMTP server
            $mail->Port = xarModGetVar('mail', 'smtpPort'); // SMTP Port default 25.
            $mail->Helo = xarServer::getVar('SERVER_NAME'); // identification string sent to MTA at smtpHost

            // the smtp server might require authentication
            if (xarModGetVar('mail', 'smtpAuth')) {
                $mail->SMTPAuth = true; // turn on SMTP authentication
                $mail->Username = xarModGetVar('mail', 'smtpUserName'); // SMTP username
                $mail->Password = xarModGetVar('mail', 'smtpPassword'); // SMTP password
            }
            break;

        case 'sendmail':
            $mail->IsSendmail();
            $mail->Sendmail = xarModGetVar('mail', 'sendmailpath'); // Use the correct path to sendmail
            break;

        case 'qmail':
            $mail->IsQmail();
            break;

        case 'mail':
            $mail->IsMail();
            break;
    }

    $mail->WordWrap = $wordwrap;
    $mail->Priority = $priority;
    $mail->Encoding = $encoding;
    $mail->CharSet = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    $mail->From = $from;
    $mail->Sender = $from;
    $mail->FromName = $fromname;

    if (xarModGetVar('mail', 'replyto')) {
        $mail->AddReplyTo(xarModGetVar('mail', 'replytoemail'), xarModGetVar('mail', 'replytoname'));
    }

    // The parameters below are the bare minimum sent to the API.
    // $info = Where its being mailed to
    // $recipients = array of recipients -- meant to replace $info/$name
    // $subject = The subject of the mail
    // $message = The body of the email
    // $name = name of person receiving email (not required)
    if (xarModGetVar('mail','redirectsending')==1) {

        $mail->ClearAddresses();
        $recipients = array();
        $redirectaddress = xarModGetVar('mail','redirectaddress');
        if (!empty($redirectaddress)) {
            $info = $redirectaddress;
            $name = xarML('Xarigami Mail Debugging');
        } else {
            return true;
        }
    }
    if (!empty($recipients)) {
        foreach($recipients as $k=>$v) {
            if (!is_numeric($k) && !is_numeric($v)) {
                // $recipients[$info] = $name describes $recipients parameter
                $mail->AddAddress($k, $v);
            } else if (!is_numeric($k)) {
                // $recipients[$info] = (int) describes $recipients parameter
                $mail->AddAddress($k);
            } else {
                // $recipients[(int)] = $info describes $recipients parameter
                $mail->AddAddress($v);
            }// if
        }// foreach
    } else {
        if (!empty($info)) {
            if (!empty($name)) {
                $mail->AddAddress($info, $name);
            } else {
                $mail->AddAddress($info);
            }
        }
    }// if

    // Add a "CC" address
    if (xarModGetVar('mail','redirectsending')==1) {
        $mail->ClearCCs();
        $ccrecipients = array();
    }
    if (!empty($ccrecipients)) {
        foreach($ccrecipients as $k=>$v) {
            if (!is_numeric($k) && !is_numeric($v)) {
                // $recipients[$info] = $name describes $recipients parameter
                $mail->AddCC($k, $v);
            } else if (!is_numeric($k)) {
                // $recipients[$info] = (int) describes $recipients parameter
                $mail->AddCC($k);
            } else {
                // $recipients[(int)] = $info describes $recipients parameter
                $mail->AddCC($v);
            }// if
        }// foreach
    } else {
        if (!empty($ccinfo)) {
            if (!empty($ccname)) {
                $mail->AddCC($ccinfo, $ccname);
            } else {
                $mail->AddCC($ccinfo);
            }
        }
    }// if

    // Add a "BCC" address
    if (xarModGetVar('mail','redirectsending') == 1) {
        $mail->ClearBCCs();
        $bccrecipients = array();
    }
    if (!empty($bccrecipients)) {
        foreach($bccrecipients as $k=>$v) {
            if (!is_numeric($k) && !is_numeric($v)) {
                // $recipients[$info] = $name describes $recipients parameter
                $mail->AddBCC($k, $v);
            } else if (!is_numeric($k)) {
                // $recipients[$info] = (int) describes $recipients parameter
                $mail->AddBCC($k);
            } else {
                // $recipients[(int)] = $info describes $recipients parameter
                $mail->AddBCC($v);
            }// if
        }// foreach
    } else {
        if (!empty($bccinfo)) {
            if (!empty($bccname)) {
                $mail->AddBCC($bccinfo, $bccname);
            } else {
                $mail->AddBCC($bccinfo);
            }
        }
    }// if

    // Set subject
    $mail->Subject = $subject;

    // Set IsHTML - this is true for HTML mail
    $mail->IsHTML($htmlmail);

    $mailShowTemplates  = xarModGetVar('mail', 'ShowTemplates');

    // If mailShowTemplates is undefined, then the modvar is missing for some reason
    // If so, we assume off, since the GUI will also show off in this case
    if (!isset($mailShowTemplates)) {
        xarModSetVar('mail','ShowTemplates',false);
        $mailShowTemplates = false;
    }

    // go ahead and override the show *theme* templates value,
    // using the mail modules settings instead :-)
    $oldShowTemplates = xarModGetVar('themes', 'ShowTemplates');
    xarModSetVar('themes', 'ShowTemplates', $mailShowTemplates);

    // Check if this is HTML mail and set Body appropriately
    if ($htmlmail) {
        // Sets the text-only body of the message.
        // This automatically sets the email to multipart/alternative.
        // This body can be read by mail clients that do not have HTML email
        // capability such as mutt. Clients that can read HTML will view the normal Body.
        if (!empty($message)) {
            if ($usetemplates) {
                $mail->AltBody = xarTplModule('mail',
                                              'admin',
                                              'sendmail',
                                              array('message'=>$message),
                                              'text');
            } else {
                $mail->AltBody = $message;
            }
        }
        // HTML message body
        if ($usetemplates) {
            $mail->Body = xarTplModule('mail',
                                       'admin',
                                       'sendmail',
                                       array('htmlmessage'=>$htmlmessage),
                                       'html');
        } else {
            $mail->Body = $htmlmessage;
        }
    } else {
        if ($usetemplates) {
            $mail->Body = xarTplModule('mail',
                                       'admin',
                                       'sendmail',
                                       array('message'=>$message),
                                       'text');
        } else {
            $mail->Body = $message;
        }
    }

    // Set the showTemplates back to what it was previously
    xarModSetVar('themes', 'ShowTemplates', $oldShowTemplates);

    // We are now setting up the advance options that can be used by the modules
    // Add Attachment will look to see if there is a var passed called
    // attachName and attachPath and attach it to the message

    if (isset($attachPath) && !empty($attachPath)) {
        if (isset($attachName) && !empty($attachName)) {
            $mail->AddAttachment($attachPath, $attachName);
        } else {
            $mail->AddAttachment($attachPath);
        }
    }

    // Send the mail, or send an exception.
    $result = true;
    if (!$mail->Send()) {
        $msg = xarML('The message was not sent. Mailer Error: #(1)',$mail->ErrorInfo);
        xarTplSetMessage($msg,'error');
        xarLogMessage('MAIL ERROR '.$msg);
        //jojo - TODO: don't fail here - stops scheduler. We need to log the message and handle it someway
        //throw new BadParameterException(null,$msg);
        $result = false;
    }

    // Clear all recipients for next email
    $mail->ClearAddresses();

    // Clear all ccrecipients for next email
    $mail->ClearCCs();

    // Clear all bccrecipients for next email
    $mail->ClearBCCs();

    // Clear all attachments for next email
    $mail->ClearAttachments();

    return $result;

}

?>
