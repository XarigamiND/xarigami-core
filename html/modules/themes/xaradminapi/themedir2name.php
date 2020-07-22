<?php
/**
 * Convert a theme directory to a theme name.
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
 * Convert a theme directory to a theme name.
 *
 * @author Roger Keays <r.keays@ninthave.net>
 * @param   directory of the theme
 * @return  the theme name in this directory, or false if theme is not
 *          found
 */
function themes_adminapi_themedir2name($args)
{
    $allthemes = xarMod::apiFunc('themes', 'admin', 'getfilethemes');
    foreach ($allthemes as $theme) {
        if ($theme['directory'] == $args['directory']) {
            return $theme['name'];
        }
    }
    return false;
}
?>