<?php
/**
 * Get list of mail messages
 *
 * @package modules
 * @copyright (C) 2002-2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail Module
 * @link http://xaraya.com/index.php/release/36.html
 * @author Mail Module Development Team
 */

/**
* Get list of mail messages from the queue
*
* @author the Mail module development team
* @param integer $args['numitems'] (optional) the number of items to retrieve (default -1 = all)
* @param integer $args['startnum'] (optional) start with this item number (default 1)
* @param timestamp $args['when'] (optional) If set, retrieves all items with the xar_when
*                                field LESS THAN OR EQUAL TO the timestamp provided.
* @param timestamp $args['sent'] (optional) If set, retrieves all items with the xar_sent
*                                EQUAL TO the timestamp provided (typ. zero for mail sending)
* @param int $args['sentstatus'] Select based on sent field empty or not . 0 Failed , 1- Mail Sent, 2- queued
* @param boolean $args['body'] (optional, default=false) Causes getall() to include the
*                               message bodies along with just their sizes
* @returns array
* @return array of items, or false on failure
* @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
*/
function mail_adminapi_getall($args)
{
    extract($args);

    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = -1;
    }

    $invalid = array();
    if (!isset($startnum) || !is_numeric($startnum)) {
        $invalid[] = 'startnum';
    }
    if (!isset($numitems) || !is_numeric($numitems)) {
        $invalid[] = 'numitems';
    }
    if (isset($when) && !is_numeric($when)) {
        $invalid[] = 'when';
    }
    if (isset($sent) && !is_numeric($sent)) {
        $invalid[] = 'sent';
    }
    if (isset($sentstatus) && !in_array($sentstatus,array('0','1','2'))) {
        $invalid[] = 'sentstatus';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            join(', ', $invalid), 'admin', 'getall', 'Mail');
        throw new BadParameterException(null,$msg);
    }

    $items = array();
    if (!xarSecurityCheck('AdminMail')) return;
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $queuetable = $xartable['mail_queue'];

    $query = "SELECT * FROM $queuetable WHERE 1 ";
    $bindvars = array();
    if (isset($when)) {
        $query .= " AND xar_when <= ? ";
        $bindvars[] = $when;
    }
    if (isset($sent)) {
        $query .= " AND xar_sent = ? ";
        $bindvars[] = $sent;
    }
    if (isset($sentstatus)) {
        if ($sentstatus ==1) { //sent
            $query .= " AND xar_sent > 0 ";
        } elseif ($sentstatus ==2) {//not sent
            $query .= " AND xar_sent = 0 ";
        } else {
            $query .= " AND xar_sent = -1 ";
        }
        //no bindqueries for constants
    }    
    $query .= "ORDER BY xar_sent, xar_when, xar_queued";
    $result = $dbconn->SelectLimit($query, $numitems, $startnum-1, $bindvars);

    // check errors
    if (!$result) return;

    $messages = array();
    for (; !$result->EOF; $result->MoveNext()) {
        list(
            $mid, $info, $name, $recipients, $ccinfo, $ccname, $ccrecipients, $bccinfo, $bccname,
            $bccrecipients, $subject, $message, $htmlmessage, $priority, $encoding, $wordwrap,
            $from, $fromname, $usetemplates, $when, $attachName, $attachPath, $htmlmail, $queued,
            $sent
        ) = $result->fields;

        // process vars that need a lil' extra sumthin'
        $recipients    = @unserialize($recipients);
        $ccrecipients  = @unserialize($ccrecipients);
        $bccrecipients = @unserialize($bccrecipients);
        if (!is_array($recipients))    $recipients = array();
        if (!is_array($ccrecipients))  $ccrecipients = array();
        if (!is_array($bccrecipients)) $bccrecipients = array();
        // decompress messages
        if (function_exists('gzuncompress')) {
            $message = gzuncompress($message);
            $htmlmessage = gzuncompress($htmlmessage);
        }

        // assemble array for this message
        $messages[$mid] = array(
            'mid'             => $mid,
            'info'            => $info,
            'name'            => $name,
            'recipients'      => $recipients,
            'ccinfo'          => $ccinfo,
            'ccname'          => $ccname,
            'ccrecipients'    => $ccrecipients,
            'bccinfo'         => $bccinfo,
            'bccname'         => $bccname,
            'bccrecipients'   => $bccrecipients,
            'subject'         => $subject,

            // note: we only return message size here.  if the body is needed,
            // get() should be used
            'messagesize'     => strlen($message),
            'htmlmessagesize' => strlen($htmlmessage),

            'priority'        => $priority,
            'encoding'        => $encoding,
            'wordwrap'        => $wordwrap,
            'from'            => $from,
            'fromname'        => $from,
            'usetemplates'    => $usetemplates,
            'when'            => $when,
            'attachName'      => $attachName,
            'attachPath'      => $attachPath,
            'htmlmail'        => $htmlmail,
            'queued'          => $queued,
            'sent'            => $sent
        );

        if (isset($body) && $body) {
            $messages[$mid]['message'] = $message;
            $messages[$mid]['htmlmessage'] = $htmlmessage;
        }
    }
    $result->Close();

    return $messages;
}
?>