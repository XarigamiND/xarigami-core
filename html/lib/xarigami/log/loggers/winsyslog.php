<?php
/**
 * Windows system log
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
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

/**
 * Class to handle winsys logggin
 *
 * @package logging
 */
class xarLogger_winsyslog extends xarLogger_syslog
{
    /**
     * Converts a XARLOG_LEVEL* constant into a syslog LOG_* constant.
     *
     * This function exists because, under Windows, not all of the LOG_*
     * constants have unique values.  Instead, the XARLOG_LEVEL_* were introduced
     * for global use, with the conversion to the LOG_* constants kept local to
     * to the syslog driver.
     *
     * @param int $level     XARLOG_LEVEL_* value to convert to LOG_* value.
     *
     * @return  The LOG_* representation of $priority.
     *
     * @access private
     */
    function _toSyslog($level)
    {
        static $levels = array(
            XARLOG_LEVEL_EMERGENCY => 1, //ERROR
            XARLOG_LEVEL_ALERT     => 1, //ERROR
            XARLOG_LEVEL_CRITICAL  => 1, //ERROR
            XARLOG_LEVEL_ERROR     => 1, //ERROR
            XARLOG_LEVEL_WARNING   => 1, //ERROR
            XARLOG_LEVEL_NOTICE    => 6, //INFO
            XARLOG_LEVEL_INFO      => 6, //INFO
            XARLOG_LEVEL_DEBUG     => 6  //INFO
        );

        return $levels[$level];
    }
}
?>
