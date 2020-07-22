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
 * @author Paul Crovella
 */
/**
 * Stub for the file_get_contents() function
 *
 * @see _file_get_contents()
 */

function file_get_contents($filename, $use_include_path = false, $resource_context = null)
{
    require_once dirname(__FILE__) . '/functions/_file_get_contents.php';
    return _file_get_contents($filename, $use_include_path, $resource_context);
}

?>