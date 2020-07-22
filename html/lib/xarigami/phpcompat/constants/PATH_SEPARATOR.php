<?php
/**
 * Constant PATH_SEPARATOR
 *
 * @package PHP Version Compatibility Library
 * @copyright (C) 2004 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Paul Crovella
 */

/**
 * Defines the PATH_SEPARATOR constant introduced in PHP 4.3.0-RC2
 *
 * @link http://php.net/ref.dir
 */
if(!defined('PATH_SEPARATOR')) {
    if (strtoupper(substr(PHP_OS, 0    , 3)) == 'WIN') {
        $path_separator = ';';
    } else {
        $path_separator = ':';
    }
    define('PATH_SEPARATOR', $path_separator);
}
?>
