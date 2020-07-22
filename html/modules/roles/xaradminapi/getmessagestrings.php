<?php
/**
 * Get message
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['module'] module directory in var/messaging
 * @return array of strings of file contents read
 */
function roles_adminapi_getmessagestrings($args)
{
    extract($args);
    if (!isset($template)) {
        $msg = xarML('No template name was given.');
        throw new EmptyParameterException(null,$msg);
    }

//FIXME: the default is always roles
    if(!isset($module)){
        list($module) = xarRequest::getInfo();
    }

    $messaginghome = sys::varpath() . "/messaging/" . $module;
    if (!file_exists($messaginghome . "/" . $template . "-subject.xd")) {
        throw new FileNotFoundException($messaginghome . "/" . $template . "-subject.xd");
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
        throw new FileNotFoundException($messaginghome . "/" . $template . "-subject.xd");
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
