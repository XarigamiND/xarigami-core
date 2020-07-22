<?php
/**
 * Stub file_get_contents
 *
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
 * Stub for the fnmatch() function
 *
 * @see _fnmatch()
 */

function fnmatch($pattern, $string)
{
    require_once dirname(__FILE__) . '/functions/_fnmatch.php';
    return _fnmatch($pattern, $string);
}

?>