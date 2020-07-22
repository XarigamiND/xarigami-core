<?php
/**
 * Regenerate theme list
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Regenerate theme list
 *
 * @author Xarigami Core Development Team
 * @param none
 * @return bool true on success, false on failure
 * @throws NO_PERMISSION
 */
function themes_adminapi_regenerate()
{
// Security Check
  //  if(!xarSecurityCheck('AdminTheme')) return;
    //set some vars we use here at the beginning
    $sitethemedir = xarConfigGetVar('Site.BL.ThemesDirectory');

    //Finds and updates missing themes
    if (!xarMod::apiFunc('themes','admin','checkmissing')) {return;}
    $thememessage = '';

    $varchanges = array();
    //Get all themes in the filesystem
    $fileThemes = xarMod::apiFunc('themes','admin','getfilethemes');
    if (!isset($fileThemes)) return;

    // Get all themes in DB
    $dbThemes = xarMod::apiFunc('themes','admin','getdbthemes');
    if (!isset($dbThemes)) return;
    //Setup database object for module insertion
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $themes_table = $xartable['themes'];

    // See if we have gained any themes since last generation,
    // or if any current themes have been upgraded
    foreach ($fileThemes as $name => $themeinfo) {
        foreach ($dbThemes as $dbtheme) {
             // Bail if 2 themes have the same regid but not the same name
            //jojo - this means we have to get to the database to correct it ...
            //Situations:
            // 1. Renamed a theme - we want to still use the same theme, but the name was wrong and changed, the directory is the same ...
            // 2. Renamed a theme - we want to still use the same theme, but the name was wrong and changed, the directory is different ..

            if(($themeinfo['regid'] == $dbtheme['regid']) && ($themeinfo['name'] != $dbtheme['name'])) {

                if (($dbtheme['directory'] == $themeinfo['directory']) && is_dir(xarConfigGetVar('Site.BL.ThemesDirectory').'/'.$themeinfo['directory']).'/')
                {
                  //set the message before we change the variables
                   $thememessage = xarML("The theme named '#(1)' was updated to '#(2)' in the database because someone changed the name in the filesystem, but left the directories and regids the same.", $dbtheme['name'],$themeinfo['name'] );
                   //looks like we changed the name only - let's update the theme in the database with the new name and keep the directory
                   $sql = "UPDATE $themes_table SET xar_name = ? WHERE xar_regid = ?";
                   $result = $dbconn->Execute($sql, array($themeinfo['name'], $themeinfo['regid']));
                   if (!$result) {return;}
                   //now update our arrays
                   $dbtheme['name'] = $themeinfo['name'];
                   $dbThemes[$themeinfo['name']] = $dbtheme;
                } else {
                    $msg = xarML('The same registered ID (#(1)) was found belonging to a #(2) theme in the file system and a registered #(3) theme in the database. Please correct this and regenerate the list.', $dbtheme['regid'], $themeinfo['name'], $dbtheme['name']);
                    throw new BadParameterException(null,$msg);
                }
            }
            // Bail if 2 themes have the same name but not the same regid
            if(($themeinfo['name'] == $dbtheme['name']) && ($themeinfo['regid'] != $dbtheme['regid'])) {
                $msg = xarML('The theme #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.', $themeinfo['name'], $themeinfo['regid'], $dbtheme['regid']);
                throw new BadParameterException(null,$msg);
            }
        }

       if (empty($dbThemes[$name])) {
            // New theme - we could set the state here to installed
            if (empty($themeinfo['xar_version'])){
                $themeinfo['xar_version'] = '1.0.0';
            }

            $themeId = $dbconn->GenId($themes_table);
            $sql = "INSERT INTO $xartable[themes]
                      (xar_id,
                       xar_name,
                       xar_regid,
                       xar_directory,
                       xar_author,
                       xar_homepage,
                       xar_email,
                       xar_description,
                       xar_contactinfo,
                       xar_publishdate,
                       xar_license,
                       xar_version,
                       xar_xaraya_version,
                       xar_bl_version,
                       xar_class,
                       xar_state)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $bindvars = array($themeId,
                              $themeinfo['name'],
                              $themeinfo['regid'],
                              $themeinfo['directory'],
                              $themeinfo['author'],
                              $themeinfo['homepage'],
                              $themeinfo['email'],
                              $themeinfo['description'],
                              $themeinfo['contact'],
                              $themeinfo['publish_date'],
                              $themeinfo['license'],
                              $themeinfo['version'],
                              $themeinfo['xar_version'],
                              $themeinfo['bl_version'],
                              $themeinfo['class'],
                              XARTHEME_STATE_UNINITIALISED
                           );
                            $result = $dbconn->Execute($sql,$bindvars);
               if (!$result) return;

            /*
            $set = xarMod::apiFunc('themes','admin','setstate',
                                array('regid' => $themeinfo['regid'],
                                      'state' => XARTHEME_STATE_UNINITIALISED));
            if (!isset($set)) return;
            */
        } else {
        /*
          // BEGIN bugfix (561802) - cmgrote
            if ($dbThemes[$name]['version'] != $themeinfo['version'] && $dbThemes[$name]['state'] != XARTHEME_STATE_UNINITIALISED) {
                    $set = xarMod::apiFunc('themes','admin','setstate',
                                        array('regid' => $dbThemes[$name]['regid'], 'state' => XARTHEME_STATE_UPGRADED));
                    if (!isset($set)) die('upgrade');
                }
         }
         */
            if ($dbThemes[$name]['version'] != $themeinfo['version']) {
                // The version strings are different.
                // Compare the versions, only going down to three levels. Only the first three
                // levels are significant for upgrades.
                $vercompare = xarMod::apiFunc('base', 'versions', 'compare',
                    array(
                        'ver1'=>$dbThemes[$name]['version'],
                        'ver2'=>$themeinfo['version'],
                        'levels' => 3
                    )
                );

                // Check if database version is less than (or equal to) the file version
                // i.e. that the module is not being downgraded.
                if ($vercompare >= 0) {
                    // The new version is either the same (to 3 levels) or higher.
                    // Automatically update the theme version for uninstalled themes or
                    // where the version number is equivalent (but could be a different format)
                    //or if the theme is a System theme - like installer
                    $issystem = FALSE;
                    $isdefault = FALSE;
                    $defaulttheme = xarModGetVar('themes','default');
                    $defaultid = xarThemeGetIdFromName($defaulttheme);
                    if ($defaultid == $dbThemes[$name]['regid'] ) $isdefault = TRUE;
                    if ($dbThemes[$name]['class']  == 1) $issystem = TRUE;

                    if ($dbThemes[$name]['state'] == XARTHEME_STATE_UNINITIALISED ||
                        $dbThemes[$name]['state'] == XARTHEME_STATE_MISSING_FROM_UNINITIALISED ||
                        $dbThemes[$name]['state'] == XARTHEME_STATE_ERROR_UNINITIALISED ||
                        $vercompare == 0  || $issystem || $isdefault)
                    {
                        $issystem = FALSE;
                        $isdefault = FALSE;
                        //now file system
                        if ($defaultid == $themeinfo['regid']) $isdefault = TRUE;
                        if ($themeinfo['class'] == 1) $issystem = TRUE;

                        if ($isdefault === TRUE || $issystem === TRUE) {
                                 // Update the module version number
                            $sql = "UPDATE $themes_table SET xar_version = ? WHERE xar_regid = ?";
                            $result = $dbconn->Execute($sql, array($themeinfo['version'], $themeinfo['regid']));
                            if (!$result) {return;}
                        }
                    } else {
                        // Else set the module state to upgraded
                        $dbThemes[$name]['state'] = XARTHEME_STATE_UPGRADED;
                        $set = xarMod::apiFunc('themes', 'admin', 'setstate',
                                array(
                                    'regid' => $themeinfo['regid'],
                                    'state' => XARTHEME_STATE_UPGRADED
                                )
                            );

                        if (!isset($set)) {return;}
                    }

                } else {
                    // The database version is greater than the file version.
                    // We can't deactivate or remove the theme as the user will
                    // lose all of their data, so the theme should be placed into
                    // a holding state until the user has updated the files for
                    // the theme and the theme version is the same or greater
                    // than the db version.

                    // Check if error state is already set
                    if (($dbThemes[$name]['state'] == XARTHEME_STATE_ERROR_UNINITIALISED) ||
                        ($dbThemes[$name]['state'] == XARTHEME_STATE_ERROR_INACTIVE) ||
                        ($dbThemes[$name]['state'] == XARTHEME_STATE_ERROR_ACTIVE) ||
                        ($dbThemes[$name]['state'] == XARTHEME_STATE_ERROR_UPGRADED)) {
                        // Continue to next module
                        continue;
                    }

                    // Clear cache to make sure we set the correct states
                    if (xarCoreCache::isCached('Theme.Infos', $themeinfo['regid'])) {
                        xarCoreCache::delCached('Theme.Infos', $themeinfo['regid']);
                    }

                    // Set error state
                    $themestate = XARTHEME_STATE_ANY;
                    switch ($dbThemes[$name]['state']) {
                        case XARTHEME_STATE_UNINITIALISED:
                            $themestate = XARTHEME_STATE_ERROR_UNINITIALISED;
                            break;
                        case XARTHEME_STATE_INACTIVE:
                            $themestate = XARTHEME_STATE_ERROR_INACTIVE;
                            break;
                        case XARTHEME_STATE_ACTIVE:
                            $themestate = XARTHEME_STATE_ERROR_ACTIVE;
                            break;
                        case XARTHEME_STATE_UPGRADED:
                            $themestate = XARTHEME_STATE_ERROR_UPGRADED;
                            break;
                    }
                    if ($themestate != XARTHEME_STATE_ANY) {
                        $set = xarMod::apiFunc(
                            'themes', 'admin', 'setstate',
                            array(
                                'regid' => $dbThemes[$name]['regid'],
                                'state' => $themestate
                            )
                        );
                        if (!isset($set)) {return;}

                        // Continue to next module
                        continue;
                    }
                }
            }

            // From here on we have something in the file system or the db
            $newstate = XARTHEME_STATE_ANY;
            switch ($dbThemes[$name]['state']) {
                case XARTHEME_STATE_MISSING_FROM_UNINITIALISED:
                case XARTHEME_STATE_ERROR_UNINITIALISED:
                    $newstate = XARTHEME_STATE_UNINITIALISED;
                    break;
                case XARTHEME_STATE_MISSING_FROM_INACTIVE:
                case XARTHEME_STATE_ERROR_INACTIVE:
                    $newstate = XARTHEME_STATE_INACTIVE;
                    break;
                case XARTHEME_STATE_MISSING_FROM_ACTIVE:
                case XARTHEME_STATE_ERROR_ACTIVE:
                    $newstate = XARTHEME_STATE_ACTIVE;
                    break;
                case XARTHEME_STATE_MISSING_FROM_UPGRADED:
                case XARTHEME_STATE_ERROR_UPGRADED:
                    $newstate = XARTHEME_STATE_UPGRADED;
                    break;
            }
            if ($newstate != XARTHEME_STATE_ANY) {
                $set = xarMod::apiFunc(
                    'themes', 'admin', 'setstate',
                    array(
                        'regid' => $dbThemes[$name]['regid'],
                        'state' => $newstate
                    )
                );
            }

            //has any other theme data changed and needs updating to the db?
            $updatearray = array('name','author','homepage','email','description','contact','publish_date','license','xar_version','bl_version','class');
            $updaterequired = false;
            foreach ($updatearray as $fieldname) {
                if ($dbThemes[$name][$fieldname] != $themeinfo[$fieldname]) {
                    $updaterequired = true;
                }
            }
            if ($updaterequired) {
                //update all these fields to the database
                $updatemodule = xarMod::apiFunc('themes','admin','updateproperties',
                          array('regid' => $dbThemes[$name]['regid'],
                                // 'name'  => $themeinfo['name'], //jojo- aim for this but not now, could have consequences unless we've cleaned up for regid/name/dir usage
                                'author' => $themeinfo['author'],
                                'homepage' => $themeinfo['homepage'],
                                'email' =>  $themeinfo['email'],
                                'description' =>  $themeinfo['description'],
                                'contactinfo' =>  $themeinfo['contact'],
                                'publishdate' =>  $themeinfo['publish_date'],
                                'license' =>  $themeinfo['license'],
                                'xar_version' =>  $themeinfo['xar_version'],
                                'bl_version' =>  $themeinfo['bl_version'],
                                'class' =>  $themeinfo['class']
                            )
                    );
            }
        }
        
        // Themevars regen can work in the install bootstrap (initializing dynamic data first)
        if (xarModIsAvailable($themeinfo['name'],'theme')) {
            $varchanges[$themeinfo['name']] = xarModAPIFunc('themes','admin','regenthemevars',array('themename'=>$name));
        }
        
    }
    return $varchanges;
}

?>