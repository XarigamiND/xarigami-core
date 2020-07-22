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
 * Handle the css  <xar:style  /> tag
 * @access public
 *
 * <xar:style scope="common" method="link" alternatedir="mystyle" base="style" file="style" fileext="css" alternate="false" rel="stylesheet" type="text/css" media="screen" title="Stylesheet Title"/>
 * <xar:style scope="theme" method="import" alternatedir="mystyle" base="style" file="style" fileext="css" alternate="false" rel="stylesheet" type="text/css" media="screen"/>
 * <xar:style scope="module" method="embed" type="text/css" media="screen" source="body { margin: 0; }"/>
 * <xar:style scope="block" method="link" alternatedir="mydir" file="style" rel="stylesheet" type="text/css" media="screen" module="blockmodule" title="Block Stylesheet"/>
 */
/**
 * @params array  $args array of optional parameters<br/>
 *         string $args[scope]          scope of style, one of common!theme(default)|module|block|script<br/>
 *         string $args[method]         style method, one of link(default)|import|embed<br/>
 *         string $args[alternatedir]   alternative base folder to look in, falling back to...<br/>
 *         string $args[base]           base folder to look in, default depends on scope<br/>
 *         string $args[file]           name of file required for link or embed methods<br/>
 *         string $args[filext]         extension to use for file(s), optional, default "css"<br/>
 *         string $args[source]         source code, required for embed method, default null<br/>
 *         string $args[alternate]      switch to set rel="alternate stylesheet", optional true|false(default)<br/>
 *         string $args[rel]            rel attribute, optional, default "stylesheet"<br/>
 *         string $args[type]           link/style type attribute, optional, default "text/css"<br/>
 *         string $args[media]          media attribute, optional, default "screen"<br/>
 *         string $args[title]          title attribute, optional, default ""<br/>
 *         string $args[condition]      conditionals for ie browser, optional, default null<br/>
 *         string $args[module]         module for module|block scope, optional, default current module<br/>
 *         integer $args[weight]         weight assigned to css, the lower the weight the earlier css is loaded <br/>
 *         bool  $args[aggregate]      aggregate ==FALSE will not aggregate the CSS
 *         string $args[version]        version appended to css <br />
 * @throws none
 * @return string of code needed to show the css tags in the BL template
 */
function themes_userapi_register($args)
{
    xarCache::addStyle($args);
    if (!class_exists('xarCss')) sys::import('modules.themes.xarclass.xarcss');
    $obj = new xarCss($args);
    return $obj->run_output();
}
?>