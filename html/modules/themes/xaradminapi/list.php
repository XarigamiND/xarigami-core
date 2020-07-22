<?php
/**
 * Obtain list of themes
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Obtain list of themes
 *
 * @author Marty Vance
 * @param none
 * @return array of known themes
 * @throws NO_PERMISSION
 */
function themes_adminapi_list()
{
// Security Check
    if(!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    // Obtain information
    $themeList = xarMod::apiFunc('themes','admin','getthemeList',
                          array('filter'     => array('State' => XARTHEME_STATE_ANY)));
    //throw back
    if (!isset($themeList)) return;

    return $themeList;
}

?>