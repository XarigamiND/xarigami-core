<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * /

/**
 * Handle render javascript form field tags
 * Handle <xar:base-render-javascript ...> form field tags
 * Format : <xar:base-render-javascript definition="$definition" /> with $definition an array
 *       or <xar:base-render-javascript position="head|body|whatever|" type="code|src|whatever|"/>
 * Default position is ''; default type is ''.
 * Typical use in the head section is: <xar:base-render-javascript position="head"/>
 *
 * @author Jason Judge
 * @param array $args['definition']     Form field definition or the type, position, ...
 * @param string $args['position']      Position to fetch the js
 * @param string $args['index']         Unique index
 * @param string $args['type']          Type of event ('onload', 'onmouseup', etc.)
 * @return string empty string
 */
function base_javascriptapi_handlerenderjavascript($args)
{

    $argstring = 'array(';
    foreach ($args as $key => $value) {
        $argstring .= "'" . $key . "' => '" . $value . "',";
    }
    $argstring .= ")";
    return "echo xarMod::apiFunc('themes', 'user', 'renderjs',$argstring);\n";
}
?>