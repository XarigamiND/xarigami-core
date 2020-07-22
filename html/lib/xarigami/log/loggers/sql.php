<?php
/**
 * SQL based logger
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @subpackage logging
 */
/**
 * Make sure the base class is available
 *
 */
sys::import('xarigami.log.loggers.xarLogger');
// Modified from the original by the Xarigami Team

/**
 * The Log_sql class is a concrete implementation of the Log::
 * abstract class which sends messages to an SQL server.  Each entry
 * occupies a separate row in the database.
 *
 * We can create this in 2 ways: create upon errors when trying to insert the data (creates on first use)
 * Create on activation of the logger module
 *
 *
 * CREATE TABLE log_table (
 *  id          INT NOT NULL,
 *  ident       VARCHAR(32) NOT NULL,
 *  logtime     TIMESTAMP NOT NULL,
 *  priority    SMALLINT NOT NULL,
 *  userid      INT NOT NULL
 *  message     TINYTEXT
 * );
 *
 * @author  Jon Parise <jon@php.net>
 * @version $Revision: 1.21 $
 * @since   Horde 1.3
 * @package logging
 */
class xarLogger_sql extends xarLogger
{
    /**
     * String holding the database table to use.
     * @var string
     */
    public $_table;

    /**
     * Pointer holding the database connection to be used.
     * @var string
     */
    public $_dbconn;


    /**
    * Set up the configuration of the specific Log Observer.
    *
    * @param  array $conf  with
    *               'table  '     => string      The name of the logger table.
    * @access public
    */
    function setConfig(array $conf)
    {
        parent::setConfig($conf);
        $this->_table = $conf['table'];

    }


    /**
     * Inserts $message to the currently open database.  Calls open(),
     * if necessary.  Also passes the message along to any Log_observer
     * instances that are observing this Log.
     *
     * @param string $message  The textual message to be logged.
     * @param string $priority The priority of the message.  Valid
     *                  values are: PEAR_LOG_EMERG, PEAR_LOG_ALERT,
     *                  PEAR_LOG_CRIT, PEAR_LOG_ERR, PEAR_LOG_WARNING,
     *                  PEAR_LOG_NOTICE, PEAR_LOG_INFO, and PEAR_LOG_DEBUG.
     *                  The default is PEAR_LOG_INFO.
     * @return boolean  True on success or false on failure.
     * @access public
     */
    function notify($message, $level)
    {
        if (class_exists('xarDB') ) {
            // Abort early if the level of priority is above the maximum logging level.
            if (!$this->doLogLevel($level)) return false;

            // DB connection
            $dbconn = xarDB::$dbconn;
            $prefix = xarDB::$prefix;
            //table might not be prefixed with xar
            if (stristr($this->_table, $prefix) === FALSE) { //we know it's added in as xar_ so safe to replace?
                $this->_table = substr_replace($this->_table, $prefix, 0, 3);
            }
            $userid = xarSession::getVar('uid');
            /* Build the SQL query for this log entry insertion. */
            // Generate id
            $nextId = $dbconn->GenId($this->_table);
            // Query for insertion

            $query = "INSERT INTO $this->_table (id, ident, logtime, priority, message, uid)
                    VALUES (?,?,?,?,?,?)";
            $bindvars = array($nextId, $this->_ident, $this->getTime(),$level,$message,(int)$userid);
;
            // Execute
            try {
                $result = $dbconn->Execute($query,$bindvars);
                 if (!$result) {
                    return false;
                }
             } catch (Exception $e) {
                //jojo - we do not want the whole site to crash because of this if people have an old table
                //just continue for now until we fix the issues in logconfig
             }

        }
        return true;
    }
}
?>
