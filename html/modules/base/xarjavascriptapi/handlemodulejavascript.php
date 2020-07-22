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
 * Handle <xar:base-include-javascript ...> form field tags
 * Format : <xar:base-include-javascript definition="$definition" /> with $definition an array
 *       or <xar:base-include-javascript filename="thisname.js" module="modulename" position="head|body" />
 *               Default modulename is the module in which the tag is called.
 *               Default position is 'head'
 *               filename is mandatory if type is not given.
 *       or <xar:base-include-javascript type="code" code="thissource" position="head|body"/>
 *
 * Example:
 * The following tag is included in an 'articles' template. The file 'myfile.js'
 * can be located in either themes/<current>/modules/articles/includes or
 * modules/articles/xartemplates/includes:
 *
 *    <xar:base-include-javascript filename="myfile.js"/>
 *
 * @author Jason Judge
 ** @param array $args['definition']     Form field definition or the type, position, ...
 ** @param string $args['code']          String containing JS code
 ** @param string $args['filename']      Name of the js file or list of files separated by commas
 **  @param string $args['module']        Name of module containing the file
 ** @param string $args['position']      Position to place the js (default= 'head')
 ** @param string $args['index']         Unique index
 ** @param string $args['type']          Type of event ('src','code','onload', 'onmouseup', etc.)
  * @param string $args['libname']      Library name  points to scripts/[libname]
 * $param string $args['libfile']       Name of the library/framework file if different to libname
 * @param string $args['plugin']        Name of the plugin, points to scripts/[$libname]/[$plugin]
 * @param string $args['pluginfile']    Name of the plugin filename(s) if different to $plugin as name or comma separated list
 * @param string $args['style']         Name of the plug style file or files to load (comma separated) looks in the plugin dir or
 * @param string $args['weight']        Optional parameter specifying weighting of js - equivalent to load order
 * @return string code to generate for this tag
 */


function base_javascriptapi_handlemodulejavascript($args)
{
if (isset($args['code']) && !empty($args['code'])) $args['code'] = addslashes($args['code']);
$out = "xarMod::apiFunc('themes', 'user', 'registerjs',\n";
    $out .= " array(\n";
    foreach ($args as $key => $val) {
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= " '$key' => \"$val\",\n";
        } else {
            $out .= " '$key' => '$val',\n";
        }
    }
    $out .= "));";

    return $out;

}

?>