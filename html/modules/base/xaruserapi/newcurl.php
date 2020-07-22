<?php
/**
 * Return a newCurl object
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Return a new xarCurl object.
 * $args are passed directly to the class.
 */
function base_userapi_newcurl($args)
{
    sys::import('modules.base.xarclass.xarCurl');
    return new xarCurl($args);
}

?>