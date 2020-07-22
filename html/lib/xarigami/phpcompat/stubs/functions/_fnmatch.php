<?php
/**
 * @package PHP Version Compatibility Library
 * @copyright (C) 2004 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Jason Judge
 */

/**
 * Mimics the fnmatch() function introduced in PHP 4.3.0
 *
 * @link http://www.php.net/manual/en/function.fnmatch.php
 */

function _fnmatch($pattern, $file)
{
    $re = preg_replace(array('/([^?*\[\]])/', '/\?/', '/\*+/'), array('\\\\$1', '.', '.*'), $pattern);
    if (substr($re, 0, 2) != '.*') {$re = '^' . $re;}
    if (substr($re, -2, 2) != '.*') {$re .= '$';}
    $newpattern = '/'.$re.'/';
    return (preg_match($newpattern, $file) ? true : false);
}

?>