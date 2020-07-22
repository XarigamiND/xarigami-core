<?php
/**
 * Activate a theme if it has an active function
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Activate a theme if it has an active function, otherwise just set the state to active
 * @access public
 * @param regid theme's registered id
 * @return bool true
 * @throws BAD_PARAM
 */
function themes_adminapi_activate($args)
{
    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $themeInfo = xarThemeGetInfo($regid);

    // Update state of theme
    $res = xarMod::apiFunc('themes', 'admin', 'setstate',
                        array('regid' => $regid,
                              'state' => XARTHEME_STATE_ACTIVE));

   xarLogMessage('THEMES: A theme with Regid '.$regid.' was activated by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}
?>