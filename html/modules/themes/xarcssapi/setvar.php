<?php
/**
 * Set skin vars anywhere in templates
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * Format : <xar:setskinvar [scope="theme"] [target="cssfile"] name="...">"#FF0000 --value--"<xar:setskinvar/> 
 *
 * @param none
 * @return string
 */
function themes_cssapi_setvar($args)
{
    if (!isset($args['target'])) $args['target'] = NULL;
    if (!isset($args['theme'])) $args['theme'] =  'xarTpl::getThemeName()';
    if (!isset($args['value'])) $args['value'] = 0;
    $target = $args['target'];
    $scope = $args['theme'];
    $name = $args['name'];
    $value = $args['value'];
    switch ($args['handler_type']) {
        case 'render':
            if ($target !== NULL) {
                return "xarSkinVars::set($scope, \"$name\", \"$value\", \"$target\");\n";
            } else {
                return "xarSkinVars::set($scope, \"$name\", \"$value\");\n";
            }
        case 'renderbegintag':
            return '$_____value_____ ';
        case 'renderendtag':
            if ($target !== NULL) {
                return "\nxarSkinVars::set($scope, \"$name\", ".'$_____value_____'.", \"$target\");\n";
            } else {
                return "\nxarSkinVars::set($scope, \"$name\", ".'$_____value_____'.");\n";
            }
    }
}

?>