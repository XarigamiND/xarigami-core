<?php
/**
 * Update theme information
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update theme information
 *
 * @author Marty Vance
 * @param $args['regid'] the id number of the theme to update
 * @param $args['displayname'] the new display name of the theme
 * @param $args['description'] the new description of the theme
 * @return bool true on success, false on failure
 */
function themes_adminapi_update($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        throw new EmptyParameterException(null,$msg);
    }
    if (!isset($updatevars)) {
        $msg = xarML('Empty updatevars (#(1)).', $updatevars);
        throw new EmptyParameterException(null,$msg);
    }

// Security Check
    if (!xarSecurityCheck('AdminTheme',0,'All',"All:All:$regId")) return xarResponseForbidden();

    // Get theme name
    $themeInfo = xarThemeGetInfo($regid);
    $themename = $themeInfo['name'];

    foreach($updatevars as $uvar){
        $updated = xarThemeSetVar($themename, $uvar['name'], $uvar['prime'], $uvar['value'], $uvar['description']);
        if (!isset($updatevars)) {
            $msg = xarML('Unable to update #(1) variable #(2)).', $themename, $uvar['name']);
            throw new BadParameterException(null,$msg);
        }

    }

    return true;
}

?>