<?php
/**
 * Stub json_decode
 * @package PHP Version Compatibility Library
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/**
 * Stub for the json_decode() function
 */
function json_encode($string)
{
    require_once dirname(__FILE__) . '/functions/_json_encode.php';
    return _json_encode($string);
}
?>