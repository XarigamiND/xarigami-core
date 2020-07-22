<?php
/**
 * Get all themes in the database
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
 * Get all themes in the database
 * @param none
 * @return array of themes in the database
 */
function themes_adminapi_getdbthemes($args)
{

    extract($args);

    // Check for $regId
    $themeregid = 0;
    if (isset($regId)) {
        $themeregid = $regId;
    }
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $themetable = $xartable['themes'];
    $dbThemes = array();

    // Get all themes in DB

   $sql = "SELECT xar_regid,
                  xar_name,
                  xar_directory,
                  xar_mode,
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
                  xar_state
                 FROM $xartable[themes] ";


    if ($themeregid) {
        $sql .= " WHERE xar_regid = $themeregid";
    }


    $result = $dbconn->Execute($sql);

    if (!$result) return;


    while(!$result->EOF) {
        list($themeregid, $name, $directory,$mode, $author,$homepage,$email,$description,$contactinfo,
        $publishdate,$license,$version,$xar_version, $bl_version,$class,$state) = $result->fields;
        //Get Theme Info
        $themeInfo = xarThemeGetInfo($themeregid);

        if (!isset($themeInfo)) return;

        //Push it into array (should we change to index by regid instead?)
        $dbThemes[$name] = array('name'     => $name,
                                 'regid'    => $themeregid,
                                 'version'  => $version,
                                 'mode'     =>  $mode,
                                 'directory'=> $directory,
                                 'author'   => $author,
                                 'homepage' => $homepage,
                                 'email'    => $email,
                                 'description'  => $description,
                                 'contact'  => $contactinfo,
                                 'publish_date'  => $publishdate,
                                 'license'      => $license,
                                 'version'      => $version,
                                 'xar_version'  =>$xar_version,
                                 'bl_version'   => $bl_version,
                                 'class'        => $class,
                                 'state'        => $state
                                    );
        $result->MoveNext();
    }
    $result->Close();

    return $dbThemes;
}

?>