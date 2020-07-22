<?php
/**
 * send mail
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
 * This is a utility function that is called to send mail
 * from any module
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param  string $ 'info' is the email address we are sending (required)
 * @param  string $ 'name' is the name of the email recipient
 * @param  array  $ 'recipients' is an array of recipients (required) // NOTE: $info or $recipients is required, not both
 * @param  string $ 'ccinfo' is the email address we are sending (optional)
 * @param  string $ 'ccname' is the name of the email recipient (optional)
 * @param  array  $ 'ccrecipients' is an array of cc recipients (optional)
 * @param  string $ 'bccinfo' is the email address we are sending (optional)
 * @param  string $ 'bccname' is the name of the email receipitent (optional)
 * @param  array  $ 'bccrecipients' is an array of bcc recipients (optional)
 * @param  string $ 'subject' is the subject of the email (required)
 * @param  string $ 'message' is the body of the email (required)
 * @param  string $ 'htmlmessage' is the html body of the email
 * @param  $ 'priority' is the priority of the message
 * @param  $ 'encoding' is the encoding of the message
 * @param  $ 'wordwrap' is the column width of the message
 * @param  string $ 'from' is who the email is from
 * @param  string $ 'fromname' is the name of the person the email is from
 * @param  string $ 'frombehalf' name used as From in 'on behalf of' email (optional)
 * @param  string $ 'attachName' is the name of an attachment to a message
 * @param  string $ 'attachPath' is the path of the attachment
 * @param  string $ 'usetemplates' set to true to use templates in xartemplates (default = true)
 * @param  int $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi_sendmail($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
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
        $msg = xarML('Wrong arguments to mail_adminapi', join(', ', $invalid), 'admin', 'sendmail', 'Mail');
        xarLogMessage('MAIL MODULE ERROR '.$msg);
        throw new BadParameterException($msg);
    }

    // Check if HTML mail has been configured by the admin
    // and send to sendhtmlmail()
    if (xarModGetVar('mail', 'html')) {
        return xarMod::apiFunc('mail', 'admin', 'sendhtmlmail', $args);
    } else {

        //Issue xgami-000346
        //we should be able to loop here, not be overly too many emails on a cc and bc list
        //if there is only one email se don't enter this loop
        if ((!empty($recipients) || !empty($bccinfo) || !empty($ccinfo) || !empty($ccrecipients) || !empty($bccrecipients) || !empty($recipients))
            && xarModGetVar('mail','loopmail')) {

            //get original recipients first
            $originallist = array();
            $recipients = isset($recipients) && is_array($recipients) ? $recipients : array();
            $ccrecipients = isset($ccrecipients) && is_array($ccrecipients) ? $ccrecipients : array();
            $bccrecipients = isset($bccrecipients) && is_array($bccrecipients)? $bccrecipients : array();
            if (isset($info) && !empty($info)) {
                $originallist[$info] = isset($name) ? $name: '';
            }else {
                $originallist = array();
            }
            //we need to get recipient list too else we will duplicate
            if (isset($recipients) && !empty($recipients)) {
                $originallist= array_merge($recipients,$originallist);
            }
            //process each of the cc or bc emails individually
            //first put the bc and cc names into the relevant lists
            if (!empty($bccinfo)) {
                $bccrecipients[$bccinfo]=isset($bccname)?$bccname:'';
            }
            if (!empty($ccinfo)) {
                $ccrecipients[$ccinfo]=isset($ccname)?$ccname:'';
            }
            //we can put them all in one array for easier processing
            //review - perhaps we need to save - how long would it take?
            //still assuming cc and bc list is not too long ...
            $ccbclist = array_merge($ccrecipients, $bccrecipients);
            $sendlist = array_merge($ccbclist,$originallist);

            foreach ($sendlist as $emailaddy=>$emailname) {
                $emailname = isset($emailname) && !empty($emailname) ?$emailname : $emailaddy;

                xarMod::apiFunc('mail', 'admin', 'sendmail',
                array('info'          => $emailaddy,
                      'name'          => $emailname,
                      'recipients'    => array(),
                      'ccinfo'        => '',
                      'ccname'        => '',
                      'ccrecipients'  => array(),
                      'bccinfo'       => '',
                      'bccname'       => '',
                      'bccrecipients' => array(),
                      'subject'       => $subject,
                      'message'       => $message,
                      'htmlmessage'   => isset($htmlmessage)?$htmlmessage: $message,
                      'priority'      => isset($priority)?$priority: xarModGetVar('mail','priority'),
                      'encoding'      => isset($encoding)?$encoding: xarModGetVar('mail','encoding'),
                      'wordwrap'      => isset($wordwrap)?$wordwrap: xarModGetVar('mail','wordwrap'),
                      'from'          => isset($from)?$from :  xarModGetVar('mail', 'adminmail'),
                      'fromname'      => isset($fromname)?$fromname :  xarModGetVar('mail', 'adminname'),
                      'frombehalf'    => isset($frombehalf) ? $frombehalf:'',
                      'usetemplates'  => isset($usetemplates) ?$usetemplates:true,
                      'when'          => isset($when)?$when: NULL,
                      'attachName'    => isset($attachName)?$attachName: '',
                      'attachPath'    => isset($attachPath)?$attachPath: '',
                      'htmlmail'      => false)
                      );
            }
            return true;
        }


        // Check info
        if (!isset($info)){
            $info = '';
        }
        // Check name
        if(!isset($name)) {
            $name='';
        }
        // Check recipients
        if (!isset($recipients)) {
            $recipients = array();
        }
        // Check CC info/name
        if (!isset($ccinfo)) {
            $ccinfo = '';
        }
        if (!isset($ccname)) {
            $ccname = '';
        }
        if (!isset($ccrecipients)) {
            $ccrecipients = array();
        }
        // Check BCC info/name
        if (!isset($bccinfo)) {
            $bccinfo = '';
        }
        if (!isset($bccname)) {
            $bccname = '';
        }
        if (!isset($bccrecipients)) {
            $bccrecipients = array();
        }
        // If htmlmessage is empty, then set to message
        if (empty($htmlmessage)) {
            $htmlmessage = $message;
        }
        // Check from
        if (empty($from)) {
            $from = xarModGetVar('mail', 'adminmail');
        }
        // Check fromname
        if (empty($fromname)) {
            $fromname = xarModGetVar('mail', 'adminname');
        }
        // Check wordwrap
        if (!isset($wordwrap)) {
            $wordwrap = xarModGetVar('mail', 'wordwrap');
        }
        // Check priority
        if (!isset($priority)) {
            $priority = xarModGetVar('mail', 'priority');
        }
        // Check encoding
        if (!isset($encoding)) {
            $encoding = xarModGetVar('mail', 'encoding');
        }
        // Check if using mail templates - default is true
        if (!isset($usetemplates)) {
            $usetemplates = true;
        }
        // Check if headers/footers have been configured by the admin
        $textheadfoot = xarModGetVar('mail', 'textuseheadfoot');
        if (!empty($textheadfoot)) {
            $header = xarModGetVar('mail', 'textheader');
            if (!empty($header)) {
                $message = $header . $message;
            }
            $footer = xarModGetVar('mail', 'textfooter');
            if (!empty($footer)) {
                $message .= $footer;
            }
        }
        // Check if we want delayed delivery of this mail message
        if (!isset($when)) {
            $when = null;
        }
        if (!isset($attachName)) {
            $attachName = '';
        }
        if (!isset($attachPath)) {
            $attachPath = '';
        }
        $mailargs = array('info'          => $info,
                          'name'          => $name,
                          'recipients'    => $recipients,
                          'ccinfo'        => $ccinfo,
                          'ccname'        => $ccname,
                          'ccrecipients'  => $ccrecipients,
                          'bccinfo'       => $bccinfo,
                          'bccname'       => $bccname,
                          'bccrecipients' => $bccrecipients,
                          'subject'       => $subject,
                          'message'       => $message,
                          'htmlmessage'   => $message, // set to $message
                          'priority'      => $priority,
                          'encoding'      => $encoding,
                          'wordwrap'      => $wordwrap,
                          'from'          => $from,
                          'fromname'      => $fromname,
                          'frombehalf'    => isset($frombehalf) ? $frombehalf :'',
                          'usetemplates'  => $usetemplates,
                          'when'          => $when,
                          'attachName'    => $attachName,
                          'attachPath'    => $attachPath,
                          'htmlmail'      => false);

        //if we have scheduling then we queue the job
        // see if we have a scheduler job
        $job = array();
        $jobint = '';
        if (xarMod::isAvailable('scheduler')) {
            $job = xarMod::apiFunc('scheduler','user','get',
                array('module' => 'mail', 'type' => 'scheduler', 'func' => 'sendmail')
            );

           $jobint = empty($job['interval']) || ($job['interval'] == '0t') ?'':$job['interval'] ;
        }
        if (!empty($job) && !empty($jobint)) {
            // Queue everything first, and then decide whether to send.
            if (!xarMod::apiFunc('mail', 'admin', '_queuemail', $mailargs)) {
                $msg = xarML('Failed queueing message for delivery!');
                throw new BadParameterException($msg);
            }

            return true;

        } else {
            //no scheduling or throttling
            // Call private sendmail
            return xarMod::apiFunc('mail', 'admin', '_sendmail',$mailargs);
        }
    }
}

?>