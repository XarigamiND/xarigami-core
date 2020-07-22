<?php
/**
 * Get themes from filesystem
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
 * Get themes from filesystem
 * @param none
 * @return array an array of themes from the file system
 */
function themes_adminapi_getfilethemes($args)
{
    extract($args);

    $themeregid = 0;
    if (isset($regId)) {
        $themeregid = $regId;
    }
    $fileThemes = array();
    $dh = opendir(xarConfigGetVar('Site.BL.ThemesDirectory'));

    while ($themeOsDir = readdir($dh)) {
        switch ($themeOsDir) {
            case '.':
            case '..':
            case '_MTN':
            case 'CVS':
            case 'SCCS':
            case 'PENDING':
                break;
            default:
                //jojodee -  remove hard coded theme path
                if (is_dir(xarConfigGetVar('Site.BL.ThemesDirectory')."/$themeOsDir")) {
                    // no xartheme.php, no theme
                    $themeFileInfo = xarTheme_getFileInfo($themeOsDir);
                    if (!isset($themeFileInfo)) {
                        continue;
                    }
                    // Found a directory
                    $name         = $themeFileInfo['name'];
                    $displayname  = $themeFileInfo['displayname'];
                    $regId        = $themeFileInfo['id'];
                    $directory    = $themeFileInfo['directory'];
                    $author       = $themeFileInfo['author'];
                    $homepage     = $themeFileInfo['homepage'];
                    $email        = $themeFileInfo['email'];
                    $description  = $themeFileInfo['description'];
                    $contact      = $themeFileInfo['contact'];
                    $license      = $themeFileInfo['license'];
                    $version      = $themeFileInfo['version'];
                    $xar_version  = $themeFileInfo['xar_version'];
                    $bl_version   = $themeFileInfo['bl_version'];
                    $class        = $themeFileInfo['class'];
                    $publish_date = $themeFileInfo['publish_date'];

                    if (!isset($regId)) {
                        xarSession::setVar('errormsg', "Theme '$name' doesn't seem to have a registered theme ID defined in xarversion.php - skipping...\nPlease register your theme at http://www.xaraya.com/index.php?module=release&func=addid if you haven't done so yet, and add \$themeversion['id'] = 'your ID'; in xarversion.php");
                        continue;
                    }

                    if (!isset($regId) || xarVarPrepForOS($directory) != $themeOsDir) {
                        xarSession::setVar('errormsg', "Theme '$name' exists in ".xarConfigGetVar('Site.BL.ThemesDirectory')."/$themeOsDir but should be in "
                        .xarConfigGetVar('Site.BL.ThemesDirectory').
                        "/$directory according to themes/$themeOsDir/xartheme.php... Skipping this theme until resolved.");
                        continue;
                    }
                    //Defaults
                    if (!isset($version)) {
                         $version = 1.0;
                    }

                    if (!isset($xar_version)) {
                        $xar_version = 1.0;
                    }

                    if (!isset($bl_version)) {
                        $bl_version = 1.0;
                    }

                   if (!isset($class)) {
                        $class = '2';
                    }
                    if (!isset($name)) {
                        $name = $directory;
                    }

                    //Check for duplicates
                    foreach ($fileThemes as $theme) {
                        if($regId == $theme['regid']) {
                            $msg = xarML('The same registered ID (#(1)) was found in two different themes, #(2) and #(3). Please remove one of the themes and regenerate the list.', $regId, $name, $theme['name']);
                            new BadParameterException(null,$msg);
                        }
                        if($name == $theme['name']) {
                            $msg = xarML('The theme #(1) was found under two different registered IDs, #(2) and #(3). Please remove one of the themes and regenerate the list', $nameinfile, $regId, $theme['regid']);
                             new BadParameterException(null,$msg);
                        }
                    }

                    if ($themeregid == $regId) {
                        closedir($dh);
                        return array('name'              => $name,
                                      'displayname'      => $displayname,
                                      'regid'            => $regId,
                                      'directory'        => $directory,
                                      'author'           => $author,
                                      'homepage'         => $homepage,
                                      'email'            => $email,
                                      'description'      => $description,
                                      'contact'          => $contact,
                                      'publish_date'     => $publish_date,
                                      'license'          => $license,
                                      'version'          => $version,
                                      'xar_version'      => $xar_version,
                                      'bl_version'       => $bl_version,
                                      'class'            => $class);
                    }else{
                        $fileThemes[$name] =
                                array('name'             => $name,
                                      'displayname'      => $displayname,
                                      'regid'            => $regId,
                                      'directory'        => $directory,
                                      'author'           => $author,
                                      'homepage'         => $homepage,
                                      'email'            => $email,
                                      'description'      => $description,
                                      'contact'          => $contact,
                                      'publish_date'     => $publish_date,
                                      'license'          => $license,
                                      'version'          => $version,
                                      'xar_version'      => $xar_version,
                                      'bl_version'       => $bl_version,
                                      'class'            => $class);
                   }//if
              } // if
        } // switch
    } // while
    closedir($dh);

    return $fileThemes;
}

?>