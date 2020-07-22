<?php
/**
 * Get message templates
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
 * @param $args['module'] module directory in var/messaging
 * @return array of template names and labels
 */
function mail_adminapi_getmessagetemplates($args)
{
    extract($args);

    if (empty($module)) {
        list($module) = xarRequest::getInfo();
    }

    $messaginghome = sys::varpath(). "/messaging/" . $module;
    if (!file_exists($messaginghome)) {
        throw new FileNotFoundException($messaginghome);
    }
    $dd = opendir($messaginghome);
    $templates = array();
    while (($filename = readdir($dd)) !== false) {
        if (!is_dir($messaginghome . "/" . $filename)) {
            $pos = strpos($filename,'-message.xd');
            if (!($pos === false)) {
                $templatename = substr($filename,0,$pos);
                $templatelabel = ucfirst($templatename);
                $templates[] = array('key' => $templatename, 'value' => $templatelabel);
            }
        }
    }
    closedir($dd);

    return $templates;
}

?>