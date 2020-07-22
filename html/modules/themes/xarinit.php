<?php
/**
 * Themes initialization
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

// Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the themes module
 * @param none $
 * @return bool
 * @throws DATABASE_ERROR
 */
function themes_init()
{
    // Get database information
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $sitePrefix = xarDB::$prefix;
    $systemPrefix = xarDB::$sysprefix;

    $tables['themes'] = $systemPrefix . '_themes';
    $tables['theme_vars'] = $sitePrefix . '_theme_vars';
    // Create tables
    /**
     * Here we create all the tables for the theme system
     *
     * prefix_themes       - basic theme info
     * prefix_theme_states - table to hold states for unshared themes
     * prefix_theme_vars   - theme variables table
     */
    // prefix_themes
    /**
     * CREATE TABLE xar_themes (
     *   xar_id int(11) NOT NULL auto_increment,
     *   xar_name varchar(64) NOT NULL default '',
     *   xar_regid int(10) unsigned NOT NULL default '0',
     *   xar_directory varchar(64) NOT NULL default '',
     *   xar_mode smallint(6) NOT NULL default '1',
     *   xar_author varchar(64) NOT NULL default '',
     *   xar_homepage varchar(64) NOT NULL default '',
     *   xar_email varchar(64) NOT NULL default '',
     *   xar_description varchar(255) NOT NULL default '',
     *   xar_contactinfo varchar(255) NOT NULL default '',
     *   xar_publishdate varchar(32) NOT NULL default '',
     *   xar_license varchar(255) NOT NULL default '',
     *   xar_version varchar(10) NOT NULL default '',
     *   xar_xaraya_version varchar(10) NOT NULL default '',
     *   xar_bl_version varchar(10) NOT NULL default '',
     *   xar_class int(10) unsigned NOT NULL default '0',
     *   PRIMARY KEY  (xar_id)
     * )TYPE=MyISAM;
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => FALSE, 'increment' => true, 'primary_key' => true),
        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => FALSE),
        'xar_regid' => array('type' => 'integer', 'unsigned' => true, 'null' => FALSE, 'default' => '0'),
        'xar_directory' => array('type' => 'varchar', 'size' => 64, 'null' => FALSE),
        'xar_mode' => array('type' => 'integer', 'null' => FALSE, 'default' => '1'),
        'xar_author' => array('type' => 'varchar', 'size' => 64, 'null' => FALSE),
        'xar_homepage' => array('type' => 'varchar', 'size' => 64, 'null' => FALSE),
        'xar_email' => array('type' => 'varchar', 'size' => 64, 'null' => FALSE),
        'xar_description' => array('type' => 'varchar', 'size' => 255, 'null' => FALSE),
        'xar_contactinfo' => array('type' => 'varchar', 'size' => 255, 'null' => FALSE),
        'xar_publishdate' => array('type' => 'varchar', 'size' => 32, 'null' => FALSE),
        'xar_license' => array('type' => 'varchar', 'size' => 255, 'null' => FALSE),
        'xar_version' => array('type' => 'varchar', 'size' => 10, 'null' => FALSE),
        'xar_xaraya_version' => array('type' => 'varchar', 'size' => 10, 'null' => FALSE),
        'xar_bl_version' => array('type' => 'varchar', 'size' => 10, 'null' => FALSE),
        'xar_class' => array('type' => 'integer', 'unsigned' => true, 'null' => FALSE, 'default' => '0'),
         'xar_state' => array('type' => 'integer', 'null' => FALSE, 'default' => '0')
        );

    $query = xarDBCreateTable($tables['themes'], $fields);
    $result = $dbconn->Execute($query);
    if(!$result) return;

    // prefix_theme_vars
    /**
     * CREATE TABLE xar_theme_vars (
     *   xar_id int(11) NOT NULL auto_increment,
     *   xar_themeName varchar(64) NOT NULL default '',
     *   xar_name varchar(64) NOT NULL default '',
     *   xar_prime int(1) NOT NULL default 1,
     *   xar_description varchar(255) NOT NULL default '',
     *   xar_value longtext,
     *   PRIMARY KEY  (xar_id)
     * ) TYPE=MyISAM;
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => FALSE, 'increment' => true, 'primary_key' => true),
        'xar_themeName' => array('type' => 'varchar', 'size' => 64, 'null' => FALSE),
        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => FALSE),
        'xar_prime' => array('type' => 'integer', 'null' => FALSE, 'default' => 1),
        'xar_description' => array('type' => 'varchar', 'size' => 255, 'null' => FALSE),
        'xar_value' => array('type' => 'text', 'size' => 'longtext'),
        'xar_config' => array('type'=> 'text')
        );

    $query = xarDBCreateTable($tables['theme_vars'], $fields);

    $res = $dbconn->Execute($query);
    if (!$res) return;

    $index = array('name'   =>  'i_'.$sitePrefix.'_theme_vars_name_themename',
            'fields' => array('xar_name', 'xar_themeName'),
            'unique' => true);

    $query = xarDBCreateIndex($tables['theme_vars'], $index);
    $result = $dbconn->Execute($query);

    xarModSetVar('themes', 'default', 'default');

    // Make sure we dont miss empty variables (which were not passed thru)
    // FIXME: how would these values ever be passed in?
    if (empty($selstyle)) $selstyle = 'plain'; //deprecated
    if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;

    xarModSetVar('themes', 'hidecore', $hidecore);
    xarModSetVar('themes', 'selstyle', $selstyle);//deprecated
    xarModSetVar('themes', 'selfilter', $selfilter);

    xarModSetVar('themes', 'selclass', 'all');
    xarModSetVar('themes', 'useicons', TRUE);
    xarModSetVar('themes', 'selpreview', FALSE);

    xarModSetVar('themes', 'SiteName', 'Your Xarigami Site');
    xarModSetVar('themes', 'SiteSlogan', 'Your Site Slogan');
    xarModSetVar('themes', 'SiteCopyRight', '&#169; Copyright 2012 ');
    xarModSetVar('themes', 'SiteTitleSeparator', ' - ');
    xarModSetVar('themes', 'SiteTitleOrder', 'default');
    xarModSetVar('themes', 'SiteFooter', '<a href="http://xarigami.com"><img src="modules/base/xarimages/xarigami.gif" alt="Powered by Xarigami" class="xar-noborder" /></a>');
    xarModSetVar('themes', 'ShowPHPCommentBlockInTemplates', 0);
    xarModSetVar('themes', 'ShowTemplates', 0);
    //Moved here in 1.1.x series
    xarModSetVar('themes', 'usedashboard', 0);
    xarModSetVar('themes', 'dashtemplate', 'dashboard');
    xarModSetVar('themes', 'adminpagemenu', 1);

    // register complete set of css tags is now encapsulated in the module's api function
    if(!xarMod::apiFunc('themes', 'css', 'registercsstags', array())) {
        return FALSE;
    }

    /* Create the Block Instances */
    $systemPrefix = xarDB::$sysprefix;
    $themesTable         = $systemPrefix . '_themes';
    $blockGroupsTable    = $systemPrefix . '_block_groups';
    $blockTypesTable     = $systemPrefix . '_block_types';
    $blockInstancesTable = $systemPrefix . '_block_instances';

    $query1 = "SELECT DISTINCT xar_name FROM $themesTable";
    $query2 = "SELECT DISTINCT xar_regid FROM $themesTable";
    $instances = array(array('header' => 'Theme Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Theme ID:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('themes','Themes',$instances);

    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'themes'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'themes'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'themes'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('themes','Block',$instances);

    xarRegisterMask('ViewThemes','All','themes','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('AdminTheme','All','themes','All','All','ACCESS_ADMIN');

     /* This init function brings authsystem to version 0.9x; (jojodee - not sure of exact prior version to 1.0)
     * run the upgrades for the rest of the initialisation */
    return themes_upgrade('1.0');
}

/**
 * Upgrade the themes theme from an old version
 *
 * @param string oldversion $ the old version to upgrade from
 * @return bool
 */
function themes_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '1.0':
            if (!xarMod::registerHook('item', 'usermenu', 'GUI', 'themes', 'user', 'usermenu')) {
                return FALSE;
            }

        case '1.1':
            if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type',
                array('modName' => 'themes', 'blockType' => 'meta'))) return;

        case '1.2':
        case '1.3.0':
            // register complete set of css tags is now encapsulated in the module's api function
            if(!xarMod::apiFunc('themes', 'css', 'registercsstags', array())) {
                return FALSE;
            }

            // Ensure the meta blocktype is registered
            if(!xarMod::apiFunc('blocks','admin','block_type_exists',array('modName' => 'themes','blockType' => 'meta'))) {
                if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type',
                                    array('modName' => 'themes',
                                          'blockType' => 'meta'))) return;
            }
        case '1.7.0':
            xarModSetVar('themes', 'selclass', 'all');
            xarModSetVar('themes', 'useicons', TRUE);
            xarModSetVar('themes', 'selpreview', FALSE);

        case '1.7.1':
            xarModSetVar('themes', 'cssaggregate', FALSE);
            xarModSetVar('themes', 'cssoptimize', FALSE);
            xarModSetVar('themes', 'jsaggregate', FALSE);
            xarModSetVar('themes', 'jsoptimize', FALSE);
            xarModSetVar('themes', 'csscachedir', './var/cache/styles');

        case '1.8.0':
        case '1.8.1':
            xarModSetVar('themes','dynamic', TRUE);

        case '1.8.3':
            if( !xarMod::apiFunc('themes', 'css', 'registercsstags', array())) return FALSE;

        case '1.8.4':
            //update to signify theme vars
            xarRegisterMask('EditTheme','All','themes','All','All','ACCESS_EDIT');
            xarRegisterMask('ModerateTheme','All','themes','All','All','ACCESS_MODERATE');
            xarRegisterMask('AddTheme','All','themes','All','All','ACCESS_ADD');

        case '1.9.1':
            if( !xarMod::apiFunc('themes', 'css', 'registercsstags', array())) return FALSE;

        case '1.9.2':
            xarModSetVar('themes','showbreadcrumbs', TRUE);
            xarModSetVar('themes','showmodheader', TRUE);
            xarModSetVar('themes','showuserbreadcrumbs', TRUE);
            xarModSetVar('themes','showusermodheader', TRUE);
            xarModSetVar('themes', 'cachefilenumber', 200);

        case '1.9.3':
            xarModSetVar('themes', 'admintheme', '');
            xarModSetVar('themes', 'useadmintheme', FALSE);

        break;
    }
    // Update successful
    return TRUE;
}

/**
 * Delete the themes theme
 *
 * @param none
 * @return bool FALSE This module cannot be deleted
 */
function themes_delete()
{
    // this module cannot be removed
    return FALSE;
}

?>