<?php
/**
 * Upgrade a theme
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2008-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Upgrade a theme
 *
 * @param regid registered theme id
 * @return bool true
 * @throws BAD_PARAM
 */
function themes_adminapi_upgrade($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
         throw new BadParameterException(null,$msg);
    }

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    if (empty($themeInfo)) {
        xarSession::setVar('errormsg', xarML('No such theme'));
        return false;
    }

    $res = xarMod::apiFunc('themes', 'admin', 'setstate',
                        array('regid' => $regid, 'state' => XARTHEME_STATE_INACTIVE));
    if (!isset($res)) return;

    // Get the new version information...
    $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
    if (!isset($themeFileInfo)) return;


   // Note the changes in the database...
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

     $sql = "UPDATE $xartable[themes]
            SET xar_version = ?, xar_class = ?
            WHERE xar_regid = ?";
    $bindvars = array($themeFileInfo['version'],
                      $themeFileInfo['class'],
                      $regid);

    $result = $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    // Message
    $msg =  xarML('The theme - #(1) -  has been upgraded, now inactive.',$themeFileInfo['name']);
    xarTplSetMessage($msg,'alert');
    // Success
    return true;
}

?>