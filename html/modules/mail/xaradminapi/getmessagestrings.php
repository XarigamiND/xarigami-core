<?php
/**
 * Get message
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */

/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['module'] module directory in var/messaging
 * @return array of strings of file contents read
 */
function mail_adminapi_getmessagestrings($args)
{
    extract($args);
    if (!isset($template)) {
         throw new BadParameterException(null,xarML('No template name was given.'));
    }

    if(!isset($module)){
        list($module) = xarRequest::getInfo();
    }

    $messaginghome = sys::varpath() . "/messaging/" . $module;
    if (!file_exists($messaginghome . "/" . $template . "-subject.xd")) {
        throw new FileNotFoundException($messaginghome);
    }
    $string = '';
    $fd = fopen($messaginghome . "/" . $template . "-subject.xd", 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $subject = $string;
    fclose($fd);

    if (!file_exists($messaginghome . "/" . $template . "-message.xd")) {
        throw new FileNotFoundException($messaginghome);
    }
    $string = '';
    $fd = fopen($messaginghome . "/" . $template . "-message.xd", 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $message = $string;
    fclose($fd);

    return array('subject' => $subject, 'message' => $message);
}

?>