<?php
/**
 * Get a specific message
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
 * Get a specific message
 *
 * Standard function of a module to retrieve a specific item
 *
 * @author the Mail module development team
 * @param  $args ['mid'] id of mail item to get
 * @returns array
 * @return item array, or false on failure
 * @raise BAD_PARAM, DATABASE_ERROR, NO_PERMISSION
 */
function mail_adminapi_get($args)
{
    extract($args);

    // validate vars
    if (!isset($mid) || !is_numeric($mid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'item ID', 'admin', 'get', 'Mail');
        throw new BadParameterException(null,$msg);
    }

    // prepare for database interaction
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $queuetable = $xartable['mail_queue'];

    // retrieve the item
    $query = "SELECT * FROM $queuetable WHERE xar_mid = ?";
    $result = $dbconn->Execute($query,array($mid));

    // check errors or non-existent message
    if (!$result) return;
    if ($result->EOF) {
        $result->Close();
        $msg = xarML('This message does not exist');
        throw new BadParameterException(null,$msg);
    }

    // retrieve values from result set
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
    $message = array(
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
        'message'         => $message,
        'htmlmessage'     => $htmlmessage,
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

    $result->Close();

    return $message;
}
?>