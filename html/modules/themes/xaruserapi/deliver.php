<?php
/**
 * Handle css tag
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Handle additional styles tag
 *
 * @access public
 * @params array   $args array of optional paramaters<br/>
 * @return string templated output required to show the css to render
 * @throws none
 */
function themes_userapi_deliver($args)
{

    if (!class_exists('xarCss')) sys::import('modules.themes.xarclass.xarcss');
    $obj = new xarCss($args);

    $styles = $obj->run_output();

    return xarTplModule('themes','user','additionalstyles',$styles);
}

?>