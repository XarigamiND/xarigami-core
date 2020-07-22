<?php
/**
 * Show some predefined output field in a template
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
// TODO: move this to some common place in Xarigami (base module ?)
 * show some predefined output field in a template
 *
 * @param array $args array containing the definition of the field (type, name, value, ...)
 * @return string containing the HTML (or other) text to output in the BL template
 */
function dynamicdata_userapi_showoutput($args)
{
    $property = Dynamic_Property_Master::getProperty($args);
    $output = $property->showOutput($args);

    return $output;

    // TODO: output from some common hook/utility modules
}

?>