<?php
/**
 * Update module information
 *
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2009-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update module information
 *
 * @param $args['regid'] the id number of the module to update
 * @param $args['displayname'] the new display name of the module
 * @param admincapable the whether the module shows an admin menu
 * @param usercapable the whether the module shows a user menu
 * @returns bool
 * @return true on success, false on failure
 */
function themes_adminapi_updateproperties($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
         throw new EmptyParameterException(null,$msg);
    }

// Security Check
    if(!xarSecurityCheck('AdminTheme',0,'All',"All:All:$regid")) return xarResponseForbidden();

    // Clear cache to make sure we get newest values
    if (xarCoreCache::isCached('Theme.Infos', $regid)) {
        xarCoreCache::delCached('Theme.Infos', $regid);
    }
    //Get module info
    $themeInfo = xarThemeGetInfo($regid);

    $defaulttheme = xarModGetVar('themes','default');

    if (isset($name) && ($themeInfo['name'] == $name)) {
        //We need to reset the default theme name
        //assume if we got to here then the name change is supposed to go ahead
        xarModSetVar('themes','default',$name);
    }

  //Set up database object
    $dbconn =  xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $themestable = $xartable['themes'];
    $setarray= array();
    $bindvars = array();
    if (isset($class)) {
        $setarray[]= 'xar_class = ?';
        $bindvars[] = $class;
        $modInfo['class'] = $class;
    }
    if (isset($name)) {
        $setarray[]= 'xar_name = ?';
        $bindvars[] = $name;
        $modInfo['name'] = $name;
    }
    if (isset($author)) {
        $setarray[]= 'xar_author = ?';
        $bindvars[] = $author;
        $themeInfo['author'] = $author;
    }
    if (isset($homepage)) {
        $setarray[]= 'xar_homepage = ?';
        $bindvars[] = $homepage;
        $themeInfo['homepage'] = $homepage;
    }
    if (isset($email)) {
        $setarray[]= 'xar_email = ?';
        $bindvars[] = $email;
        $themeInfo['email'] = $email;
    }
    if (isset($description)) {
        $setarray[]= 'xar_description = ?';
        $bindvars[] = $description;
        $themeInfo['description'] = $description;
    }
    if (isset($contactinfo)) {
        $setarray[]= 'xar_contactinfo = ?';
        $bindvars[] = $contactinfo;
        $themeInfo['contactinfo'] = $contactinfo;
    }
    if (isset($publish_date)) {
        $setarray[]= 'xar_publishdate = ?';
        $bindvars[] = $publishdate;
        $themeInfo['publishdate'] = $publishdate;
    }
    if (isset($license)) {
        $setarray[]= 'xar_license = ?';
        $bindvars[] = $license;
        $themeInfo['license'] = $license;
    }
    if (isset($xar_version)) {
        $setarray[]= 'xar_xaraya_version = ?';
        $bindvars[] = $xar_version;
        $themeInfo['xar_version'] = $xar_version;
    }
    if (isset($bl_version)) {
        $setarray[]= 'xar_bl_version = ?';
        $bindvars[] = $bl_version;
        $themeInfo['bl_version'] = $bl_version;
    }
    if (isset($version)) {
        $setarray[]= 'xar_version = ?';
        $bindvars[] = $version;
        $themeInfo['version'] = $version;
    }
        if (count($setarray) > 0) {
            $set = join(',',$setarray);
        } else {
            $set = '';
        }
    $bindvars[] = $regid;

    $query = "UPDATE  $themestable
              SET $set
              WHERE xar_regid = ?";

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) {return;}

    $result->Close();
    // We have updated the db now update the cache with new info

    xarCoreCache::setCached('Theme.Infos',$regid,$themeInfo);

    $result->Close();
    return true;
}

?>