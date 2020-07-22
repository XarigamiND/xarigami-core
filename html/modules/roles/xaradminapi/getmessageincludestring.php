<?php
/**
 * Get message include string
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
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the template without .xd extension
 * @param $args['module'] module directory in var/messaging
 * @return string of file contents read
 */
function roles_adminapi_getmessageincludestring($args)
{
    extract($args);
    if (!isset($template)) {
         throw new BadParameterException(null,'No template name was given.');
    }

    if(!isset($module)){
        list($module) = xarRequest::getInfo();
    }

// Get the template that defines the substitution vars
    $messaginghome = sys::varpath() . "/messaging/roles";
    if (!file_exists($messaginghome . "/includes/" . $template . ".xd")) {
       throw new FileNotFoundException($messaginghome . "/includes/" . $template . ".xd");
    }
    $string = '';
    $fd = fopen($messaginghome . "/includes/" . $template . ".xd", 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    fclose($fd);
    return $string;
}

?>
