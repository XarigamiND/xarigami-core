<?php
/**
 * Handle additional styles tag
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 * @author Andy Varganov <andyv@xaraya.com>
 */

/**
 * Format : <xar:additional-styles /> without params
 * Typical use in the head section is: <xar:additional-styles />
 *
 * @author Andy Varganov
 * @param none
 * @return string
 */
function themes_cssapi_delivercss($args)
{
    $args['method'] = 'render';
    $args['base'] = 'theme';

    $argstring = 'array(';
    foreach ($args as $key => $value) {
        $argstring .= "'" . $key . "' => '" . $value . "',";
    }
    $argstring .= ")";
    return "echo xarMod::apiFunc('themes', 'user', 'deliver',$argstring);\n";
}

?>