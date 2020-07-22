<?php
/**
 * Utility function to count the number of items held by this module
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
 * Utility function to count the number of items held by this module
 *
 * @author the Mail module development team
 * @returns integer
 * @return number of items held by this module
 * @param boolean $args['sent'] (optional) Whether to count only sent mails.  If $args['sent']
 *                              is not provided, 'sent' status is disregarded.
* @param int $args['sentstatus'] Select based on sent field empty or not . 0 Failed , 1- Mail Sent, 2- queued
 * @raise DATABASE_ERROR
 */
function mail_adminapi_countitems($args)
{
    extract($args);

    // prepare for database interaction
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $queuetable = $xartable['mail_queue'];

    $query = "SELECT COUNT(1) FROM $queuetable WHERE 1";
    $bindvars = array();

    //
    if (isset($sent)) {
        if ($sent) {
            $query .= " AND xar_sent > 0";
         } else {
            $query .= " AND xar_sent = 0";
        }
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
    
    $result = $dbconn->Execute($query,array());
    if (!$result) return;
    list($numitems) = $result->fields;
    $result->Close();

    return $numitems;
}

?>