<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Base JavaScript management functions
 * Include a section of inline JavaScript code in a page.
 * Used when a module needs to generate custom JS on-the-fly,
 * such as "var lang_msg = xarML('error - aborted');"
 *
 * @author Jason Judge
 * @param $args['position'] position on the page; generally 'head' or 'body'
 * @param $args['code'] the JavaScript code fragment
 * @param $args['index'] optional index in the JS array; unique identifier
 * @return bool true=success; null=fail
 */
function base_javascriptapi_moduleinline($args)
{
    extract($args);

    if (empty($code)) {
        return;
    }

    // Use a hash index to prevent the same JS code fragment
    // from being included more than once.
    if (empty($index)) {
        $index = md5($code);
    }

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    }
     $newweight = isset($weight)? $weight: 20;//code always loaded after files
    return xarTplAddJavaScript($position, 'code', $code, $index, $newweight);
}

?>