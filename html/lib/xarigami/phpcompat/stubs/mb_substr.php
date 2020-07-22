<?php
/**
 * Stub mb_string
 *
 * @package core
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage PHP Version Compatibility Library
 * @copyright (C) 2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Jo Dalle Nogare
 */
 
/**
 * Stub for mb_substr() function
 *
 * @see _mb_substr()
 */
function mb_substr($str, $start, $len = '', $encoding='UTF-8')
{
    require_once dirname(__FILE__) . '/functions/_mb_substr.php';
    return _mb_substr($str, $start, $len, $encoding);
}
?>