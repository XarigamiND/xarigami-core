<?php
/**
 * Stub html_entity_decode
 * @package PHP Version Compatibility Library
 * @copyright (C) 2004 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Jo Dalle Nogare
 */

/**
 * Stub for the html_entity_decode() function
 *
 * @see _html_entity_decode()
 * @internal quote_style not supported and defaults to ENT_COMPAT
 */
function html_entity_decode($string)
{
    require_once dirname(__FILE__) . '/functions/_html_entity_decode.php';
    return _html_entity_decode($string);
}

?>