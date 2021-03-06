<?php
/**
 * The HTML logger
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 * @subpackage logging
 *
*/

/**
 * Simple logger is the parent class
 *
 */
sys::import('xarigami.log.loggers.simple');

/**
 * HTMLLoggger
 *
 * Implements a logger to a HTML file
 *
 * @package logging
 */
class xarLogger_html extends xarLogger_simple
{
    /**
      * Set up the configuration of the specific Log Observer.
      *
      * @param  array $conf  with
      *               'name'         => string      The filename of the logfile.
      *               'maxLevel'     => int         Maximum level at which to log.
      *               'mode'         => string      File mode of te log file (optional)
      *               'timeFormat'   => string      Time format to be used in the file (optional)
      * @access public
     **/
     function setConfig (array $conf)
     {
         parent::setConfig($conf);
         $this->_fileheader = '<?xml version="1.0" encoding="utf-8"?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
            <head><title>Xarigami HTML Logger</title></head>
            <body><br />
                <table border="1" width="100%" cellpadding="2" cellspacing="0">
                    <tr align="center">
                        <th>Time</th>
                        <th>Logging Level</th>
                        <th>Message</th>
                    </tr>';
        $this->_buffer     = "\r\n".'<tr style="background-color:#e3e3e3;"><th>New Page View</th><th colspan="2">'.$_SERVER["REQUEST_URI"].'</th></tr>';
    }

    /**
     * Writes a line to the logfile
     *
     * @param  string  $message   The line to write
     * @param  integer $level     The level of priority of this line/msg
     * @access private
    **/
    function _formatMessage($message, $level)
    {
        return sprintf("\r\n<tr align=\"center\"><td>%s</td><td>%s</td><td>%s</td></tr>",
                                     $this->getTime(),
                                     $this->levelToString($level),
                                     nl2br(htmlspecialchars($message)).'<br />');
    }
}

?>
