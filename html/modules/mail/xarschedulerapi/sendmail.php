<?php
/**
 * Send queued/scheduled mails via Scheduler
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */
/**
 * send queued/scheduled mails (executed by the scheduler module)
 *
 * @author mikespub
 * @access public
 */
function mail_schedulerapi_sendmail($args)
{
    $log = xarML('Processing mail queue...') . "\n\n";

   /**
    * Sending mail can take a while.  Plus, there could be multiple overlapping
    * trigger events.  Thus, we loop one at a time, tracking how many total
    * messages have been sent across all triggers for the designated interval.
    */

    // prepare for throttling
    $throttlemax = xarModGetVar('mail', 'throttlemax');
    if (empty($throttlemax)) {
        $timespan = 0;
    } else {
        $spans = xarMod::apiFunc('mail', 'admin', 'gettimespans');
        $throttlespan = xarModGetVar('mail', 'throttlespan');
        // set default if not found
        if (empty($throttlespan)) {
            $throttlespan = '1h';
            xarModSetVar('mail', 'throttlespan', $throttlespan);
        }

        // convert throttle span into seconds
        $num = substr($throttlespan, 0, 1);
        $unit = substr($throttlespan, 1);
        $seconds = array(
            'n' => 60,
            'h' => 3600,
            'd' => 86400,
            'w' => 604800,

            // months are trickier b/c they vary in length.  we use here the average
            // seconds per month in a 4-year span (thus accounting for leap year)
            'm' => 2629800
        );
        $timespan = $num * $seconds[$unit];
    }

    // prepare db
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $queuetable = $xartable['mail_queue'];

    // setup queries
    $query_throttle = "
        SELECT COUNT(1)
        FROM $queuetable
        WHERE xar_sent <= ?
        AND xar_sent >= ?
    ";
    $query_getMID = "
        SELECT xar_mid
        FROM $queuetable
        WHERE xar_when <= ?
        AND xar_sent = ?
        ORDER BY xar_queued ASC
    ";
    $query_setStatus = "
        UPDATE $queuetable
        SET xar_sent = ?
        WHERE xar_mid = ?
    ";

    while (true) {

        // get current time
        list($usec,$sec) = explode(' ',microtime());
        $now = (float) $sec + (float) $usec;

        // for throttling, all we need to know is if we have room to send one more.
        if ($timespan > 0) {
            // get the number we've sent in the last time span
            $bindvars = array($now, $now - $timespan);
            $result = $dbconn->Execute($query_throttle, $bindvars);
            if (!$result) return;
            list($quota) = $result->fields;

            // break if we're at that number already
            if ($quota >= $throttlemax) {
                $log .= "\n";
                $log .= xarML('    Sending limit (#(1) messages per #(2)) has been reached.',
                    $throttlemax, $spans[$throttlespan]) . "\n";
                $log .= xarML('    Remaining messages will stay in queue for later delivery.') . "\n";
                break;
            }
        }

        // get next message ID to be sent
        $bindvars = array($now, 0);
        $result = $dbconn->SelectLimit($query_getMID, 1, 0, $bindvars);
        if (!$result) return;

        // exit this loop when we run out of messages
        if ($result->EOF) break;

        // retrieve mail ID
        list($mid) = $result->fields;

        // retrieve the message
        $message = xarMod::apiFunc('mail', 'admin', 'get', array('mid' => $mid));

        // send the message
        $log .= xarML('    Message no. #(1): ', $mid);
        if (xarMod::apiFunc('mail', 'admin', '_sendmail', $message)) {
            $log .= xarML('successfully sent');

            // update current time
            list($usec,$sec) = explode(' ',microtime());
            $status = (float) $sec + (float) $usec;

        } else {

            $log .= xarML('sending failed');
            // TODO: log the reason that it failed
            $status = -1;
        }

        // update "sent" status
        $bindvars = array($status, $mid);
        $result = $dbconn->Execute($query_setStatus, $bindvars);
        if (!$result) return;

        $log .= "\n";
    }
    $log .= "\n";
    $log .= xarML('Finished processing mail queue');
    $result->close();

    // here is where we would remove sent mails from the queue, but
    // we can't do that if we're using throttling b/c we need to know
    // how many mails we've sent in the last X units of time.


    return $log;
}

?>