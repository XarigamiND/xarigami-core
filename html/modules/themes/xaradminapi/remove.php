<?php
/**
 * Remove a theme
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Remove a theme
 *
 * @param $args['regid'] the id of the theme
 * @returns bool
 * @return true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function themes_adminapi_remove($args)
{
    // Get arguments from argument array
    extract($args);

    // Security Check
    if(!xarSecurityCheck('AdminTheme',0)) return xarResponseForbidden();

    // Remove variables and theme
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    $uservartable = $tables['module_uservars'];

    // Get theme information
    $themeInfo = xarThemeGetInfo($regid);
    $defaultTheme = xarModGetVar('themes','default');

    // Bail out if we're trying to remove the default theme
    if ($defaultTheme == $themeInfo['name'] ) {
        $msg = 'The theme you are trying to remove is the current default theme. Select another default theme first, then try again.';
        xarTplSetMessage($msg,'error');
        //throw new ForbiddenOperationException(null, $msg);
    }

    // Bail out if we're trying to remove while one of our users
    // has it set to their default theme
    $mvid = xarModGetVarId('themes','default');
    //jojo - fixed this, it was using default thmeme name.
    // we need to remove uservar identified by uservar and theme name - not default theme name
    $sql = "SELECT COUNT(*) FROM $uservartable WHERE xar_mvid=? AND xar_value = ?";
    $result = $dbconn->Execute($sql, array($mvid, $themeInfo['name']));
    if(!$result) return;
    // count should be zero
    $count = $result->fields[0];
    if ($count != 0 ){
        if (($confirm == FALSE) && ($checkedforvars ==0)) {
            xarResponseRedirect(xarModURL('themes','admin','remove',array('id'=>$regid,'checedforvars'=>1,'count'=>$count)));
           // return $templatedata;
        } else {
            if (($checkedforvars == 1) && ($confirm == TRUE)) {
                // delete the user vars for this theme and continue to remove theme
                  $sql = "DELETE FROM $uservartable WHERE xar_mvid=? AND xar_value = ?";
                        $result = $dbconn->Execute($sql, array($mvid, $themeInfo['name']));
                        if(!$result) {
                           $msg = xarML('There was a problem removing the uservars for theme #(1) #(2)', $regid, $themeInfo['name']);
                                        throw new BadParameterException(null,$msg);
                        }
            } else {
                //don't delete the user vars and do not remove the theme
               //just return
               xarResponseRedirect(xarModURL('themes','admin','list'));
            }
        }
    }


    // Get theme database info
    xarThemeDBInfoLoad($themeInfo['name'], $themeInfo['directory']);

    // Delete any theme variables that the theme cleanup function might
    // have missed
    $sql = "DELETE FROM $tables[theme_vars] WHERE xar_themeName = ?";
    $result = $dbconn->Execute($sql,array($themeInfo['name']));
    if (!$result) return;

    // Delete the theme from the themes table
    $sql = "DELETE FROM $tables[themes] WHERE xar_regid = ?";
    $result = $dbconn->Execute($sql,array($regid));
    if (!$result) return;

     //Get current theme mode to update the proper table
    //$themeMode  = $themeInfo['mode'];

  xarLogMessage('THEMES: A theme with Regid '.$regid.' and name '.$themeInfo['name'].' was removed by user '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);

    return true;
}

?>