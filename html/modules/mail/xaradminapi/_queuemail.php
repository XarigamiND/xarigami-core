<?php
/**
 * Queue mail
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 * @author John Cox 
 */

/**
 * This is a private utility function that is called to queue mail
 * It is used by the private function _sendmail() and should not be
 * called directly. 
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param  $ 'info' is the email address we are sending (required)
 * @param  $ 'name' is the name of the email receipitent (optional)
 * @param  $ 'recipients' is an array of recipients (required) // NOTE: $info or $recipients is required, not both
 * @param  $ 'ccinfo' is the email address we are sending (optional)
 * @param  $ 'ccname' is the name of the email receipitent (optional)
 * @param  $ 'ccrecipients' is an array of cc recipients (optional)
 * @param  $ 'bccinfo' is the email address we are sending (required)
 * @param  $ 'bccname' is the name of the email receipitent (optional)
 * @param  $ 'bccrecipients' is an array of bcc recipients (optional)
 * @param  $ 'subject' is the subject of the email (required)
 * @param  $ 'message' is the body of the email (required)
 * @param  $ 'htmlmessage' is the html body of the email
 * @param  $ 'priority' is the priority of the message
 * @param  $ 'encoding' is the encoding of the message
 * @param  $ 'wordwrap' is the column width of the message
 * @param  $ 'from' is who the email is from
 * @param  $ 'fromname' is the name of the person the email is from
 * @param  $ 'attachName' is the name of an attachment to a message
 * @param  $ 'attachPath' is the path of the attachment
 * @param  $ 'htmlmail' is set to true for an html email
 * @param  $ 'usetemplates' set to true to use templates in xartemplates
 * @param  $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi__queuemail($args)
{
    extract($args);

    // we use microtime in case someone sends lots of identical mails :)
    list($usec,$sec) = explode(' ',microtime());
    $queued = (float) $sec + (float) $usec;

    // if scheduler not set, ignore any $when that's provided.
    $job = xarMod::apiFunc('scheduler','user','get',
        array('module' => 'mail', 'type' => 'scheduler', 'func' => 'sendmail')
    );

   if (empty($job) || empty($job['interval']) || ($job['interval'] =='0t')) {
        $when = $queued;
    }

    // sanitize args for database storage
    $recipients = @serialize($recipients);
    $ccrecipients = @serialize($ccrecipients);
    $bccrecipients = @serialize($bccrecipients);
    // if zlib is available, compress the message bodies
    if (function_exists('gzcompress')) {
        $message = gzcompress($message);
        $htmlmessage = gzcompress($htmlmessage);
    }
    $sent = 0;
    if (!$usetemplates) $usetemplates = 0;
    if (!$when) $when = 0;
    if (!$htmlmail) $htmlmail = 0;

    // prepare for database interaction
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $queuetable = $xartable['mail_queue'];

    // insert into database
    $query = "
        INSERT INTO $queuetable (
            xar_info, xar_name, xar_recipients, xar_ccinfo, xar_ccname, xar_ccrecipients,
            xar_bccinfo, xar_bccname, xar_bccrecipients, xar_subject, xar_message,
            xar_htmlmessage, xar_priority, xar_encoding, xar_wordwrap, xar_from, xar_fromname,
            xar_usetemplates, xar_when, xar_attachName, xar_attachPath, xar_htmlmail,
            xar_queued, xar_sent
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ";
    $bindvars = array(
        $info, $name, $recipients, $ccinfo, $ccname, $ccrecipients, $bccinfo, $bccname,
        $bccrecipients, $subject, $message, $htmlmessage, $priority, $encoding, $wordwrap,
        $from, $fromname, $usetemplates, $when, $attachName, $attachPath, $htmlmail, $queued,
        $sent
    );
    $result = $dbconn->Execute($query,$bindvars);

    // check errors
    if (!$result) return;

    // we're done!
    return true;
}

?>