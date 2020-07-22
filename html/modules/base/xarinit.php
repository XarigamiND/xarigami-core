<?php
/**
 * Base Module Initialisation
 *
 * @package modules
 * @copyright (C) 2005-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Load Table Maintainance API
 */
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the base module
 *
 * @return bool
 * @throws DATABASE_ERROR
 */
function base_init()
{
    // Get database information
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $systemPrefix = xarDB::$sysprefix;

    /*********************************************************************
    * First we create the meta-table that will contain the definition of
    * all Xarigami tables
    *********************************************************************/
    $tablesTable = $systemPrefix . '_tables';
    /*********************************************************************
    * CREATE TABLE xar_tables (
    *   xar_tableid int(11) NOT NULL auto_increment,
    *   xar_table varchar(100) NOT NULL default '',
    *   xar_field varchar(100) NOT NULL default '',
    *   xar_type varchar(100) NOT NULL default '',
    *   xar_size varchar(100) NOT NULL default '',
    *   xar_default varchar(255) NOT NULL default '',
    *   xar_null tinyint(1) default NULL,
    *   xar_unsigned tinyint(1) default NULL,
    *   xar_increment tinyint(1) default NULL,
    *   xar_primary_key tinyint(1) default NULL,
    *   PRIMARY KEY  (xar_tableid)
    * )
    *********************************************************************/
    $fields = array(
    'xar_tableid'     => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_table'       => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_field'       => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_type'        => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_size'        => array('type'=>'varchar','size'=>64,'default'=>'','null'=>false),
    'xar_default'     => array('type'=>'varchar','size'=>254,'default'=>'','null'=>false),
    'xar_null'        => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
    'xar_unsigned'    => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
    'xar_increment'   => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
    'xar_primary_key' => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false)
    );
    // xar_width,
    // xar_decimals,

    $query = xarDBCreateTable($tablesTable,$fields);

    $result = $dbconn->Execute($query);
    if (!$result) return;

    /*********************************************************************
    * Here we create non module associated tables
    *
    * prefix_config_vars   - system configuration variables
    * prefix_session_info  - Session table
    * prefix_template_tags - module template tag registry
    *********************************************************************/
    $sessionInfoTable = $systemPrefix . '_session_info';
    /*********************************************************************
    * CREATE TABLE xar_session_info (
    *  xar_sessid varchar(32) NOT NULL default '',
    *  xar_ipaddr varchar(20) NOT NULL default '',
    *  xar_firstused int(11) NOT NULL default '0',
    *  xar_lastused int(11) NOT NULL default '0',
    *  xar_uid int(11) NOT NULL default '0',
    *  xar_vars blob,
    *  xar_remembersess int(1) default '0',
    *  PRIMARY KEY  (xar_sessid)
    * )
    *********************************************************************/
    $fields = array(
    'xar_sessid'       => array('type'=>'varchar','size'=>254,'null'=>false,'primary_key'=>true),
    'xar_ipaddr'       => array('type'=>'varchar','size'=>40,'null'=>false),
    'xar_firstused'    => array('type'=>'integer','null'=>false,'default'=>'0'),
    'xar_lastused'     => array('type'=>'integer','null'=>false,'default'=>'0'),
    'xar_uid'          => array('type'=>'integer','null'=>false,'default'=>'0'),
    'xar_vars'         => array('type'=>'blob'),
    'xar_remembersess' => array('type'=>'integer','size'=>'tiny','default'=>'0')
    );

    $query = xarDBCreateTable($sessionInfoTable,$fields);

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array('name'   => 'i_'.$systemPrefix.'_session_uid',
                   'fields' => array('xar_uid'),
                   'unique' => false);

    $query = xarDBCreateIndex($sessionInfoTable,$index);

    $result = $dbconn->Execute($query);
    if(!$result) return;

    $index = array('name'   => 'i_'.$systemPrefix.'_session_lastused',
                   'fields' => array('xar_lastused'),
                   'unique' => false);

    $query = xarDBCreateIndex($sessionInfoTable,$index);

    $result = $dbconn->Execute($query);
    if(!$result) return;

    /*********************************************************************
    * Here we install the configuration table and set some default
    * configuration variables
    *********************************************************************/
    $configVarsTable  = $systemPrefix . '_config_vars';
    /*********************************************************************
    * CREATE TABLE xar_config_vars (
    *  xar_id int(11) unsigned NOT NULL auto_increment,
    *  xar_name varchar(64) NOT NULL default '',
    *  xar_value longtext,
    *  PRIMARY KEY  (xar_id),
    *  KEY xar_name (xar_name)
    * )
    *********************************************************************/

    $fields = array(
    'xar_id'    => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_name'  => array('type'=>'varchar','size'=>64,'null'=>false),
    'xar_value' => array('type'=>'text','size'=>'long')
    );

    $query = xarDBCreateTable($configVarsTable,$fields);

    $result = $dbconn->Execute($query);
    if (!$result) return;

    // config var name should be unique
    $index = array('name'   => 'i_'.$systemPrefix.'_config_name',
                   'fields' => array('xar_name'),
                   'unique' => true);

    $query = xarDBCreateIndex($configVarsTable,$index);

    $result = $dbconn->Execute($query);
    if (!$result) return;

    sys::import('xarigami.caching.core');
    // Start Configuration Unit
    $systemArgs = array();
    // change this loadlevel to the proper level
    $whatToLoad = XARCORE_SYSTEM_DATABASE;

    // Start Variable Utils
    xarVars::init($systemArgs, $whatToLoad);

    $allowableHTML = array (
        '!--'=>2, 'a'=>2, 'b'=>2, 'blockquote'=>2,'br'=>2, 'center'=>2,
        'div'=>2, 'em'=>2, 'font'=>0, 'hr'=>2, 'i'=>2, 'img'=>0, 'li'=>2,
        'marquee'=>0, 'ol'=>2, 'p'=>2, 'pre'=> 2, 'span'=>0,'strong'=>2,
        'tt'=>2, 'ul'=>2, 'table'=>2, 'td'=>2, 'th'=>2, 'tr'=> 2);

    xarConfigSetVar('Site.Core.AllowableHTML',$allowableHTML);
    /****************************************************************
    * Set System Configuration Variables
    *****************************************************************/
    xarConfigSetVar('System.Core.TimeZone', 'UTC');
    xarConfigSetVar('System.Core.VersionNum', XARCORE_VERSION_NUM);
    xarConfigSetVar('System.Core.VersionId', XARCORE_VERSION_ID);
    xarConfigSetVar('System.Core.VersionSub', XARCORE_VERSION_SUB);
    $allowedAPITypes = array();
    xarConfigSetVar('System.Core.AllowedAPITypes',$allowedAPITypes);
    // set BlockLayout Version here
    xarConfigSetVar('System.Core.BLVersionNum', XAR_BL_VERSION_NUM);
    /*****************************************************************
    * Set site configuration variables
    ******************************************************************/
    xarConfigSetVar('Site.BL.ThemesDirectory','themes');
    xarConfigSetVar('Site.BL.CacheTemplates',true);
    xarConfigSetVar('Site.Core.FixHTMLEntities',true);
    xarConfigSetVar('Site.Core.TimeZone', 'UTC');
    xarConfigSetVar('Site.Core.EnableShortURLsSupport', false);

    // when installing via https, we assume that we want to support that :)
    $HTTPS = xarServer::getVar('HTTPS');
    /* jojodee - monitor this fix.
       Localized fix for installer where HTTPS shows incorrectly as being on in
       some environments. Fix is ok as long as we dont access directly
       outside of installer. Consider setting config vars at later point rather than here.
    */
    $REQ_URI = parse_url(xarServer::getVar('HTTP_REFERER'));
    // IIS seems to set HTTPS = off for some reason (cfr. xarServerGetProtocol)
    if (!empty($HTTPS) && $HTTPS != 'off' && $REQ_URI['scheme'] == 'https') {
        xarConfigSetVar('Site.Core.EnableSecureServer', true);
    } else {
        xarConfigSetVar('Site.Core.EnableSecureServer', false);
    }

    xarConfigSetVar('Site.Core.DefaultModuleName', 'base');
    xarConfigSetVar('Site.Core.DefaultModuleType', 'user');
    xarConfigSetVar('Site.Core.DefaultModuleFunction', 'main');
    xarConfigSetVar('Site.Core.LoadLegacy', false);
    xarConfigSetVar('Site.Session.SecurityLevel', 'Medium');
    xarConfigSetVar('Site.Session.Duration', 7);
    xarConfigSetVar('Site.Session.InactivityTimeout', 90);
    // use current defaults in sys.lib().'xarigami/xarSession.php
    xarConfigSetVar('Site.Session.CookieName', 'XARIGAMISID');
    xarConfigSetVar('Site.Session.CookiePath', '/');
    xarConfigSetVar('Site.Session.CookieDomain', '');
    xarConfigSetVar('Site.Session.RefererCheck', '');
    xarConfigSetVar('Site.MLS.TranslationsBackend', 'xml2php');
    // FIXME: <marco> Temporary config vars, ask them at install time
    xarConfigSetVar('Site.MLS.MLSMode', 'SINGLE');
    xarConfigSetVar('Site.MLS.Enabled',true); //must always be true during installation
    // The installer should now set the default locale based on the
    // chose language, let's make sure that is true
    if(!xarConfigGetVar('Site.MLS.DefaultLocale')) {
        xarConfigSetVar('Site.MLS.DefaultLocale', 'en_US.utf-8');
        $allowedLocales = array('en_US.utf-8');
        xarConfigSetVar('Site.MLS.AllowedLocales', $allowedLocales);
    }
    // Minimal information for timezone offset handling (see also Site.Core.TimeZone)
    xarConfigSetVar('Site.MLS.DefaultTimeOffset', 0);

    $authModules = array('authsystem');
    xarConfigSetVar('Site.User.AuthenticationModules',$authModules);

    $templateTagsTable = $systemPrefix . '_template_tags';
    /*********************************************************************
    * CREATE TABLE xar_template_tags (
    *  xar_id int(11) NOT NULL auto_increment,
    *  xar_name varchar(255) NOT NULL default '',
    *  xar_module varchar(255) default NULL,
    *  xar_handler varchar(255) NOT NULL default '',
    *  xar_data text,
    *  PRIMARY KEY  (xar_id)
    * )
    *********************************************************************/
    $fields = array(
    'xar_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
    'xar_name'    => array('type'=>'varchar','size'=>255,'null'=>false),
    'xar_module'  => array('type'=>'varchar','size'=>255,'null'=>true),
    'xar_handler' => array('type'=>'varchar','size'=>255,'null'=>false),
    'xar_data'    => array('type'=>'text')
     );

    $query = xarDBCreateTable($templateTagsTable,$fields);

    $result = $dbconn->Execute($query);
    if (!$result) return;

    // {ML_dont_parse sys::lib().'xarigami/xarMod.php'}
    sys::import('xarigami.xarMod');

    // Start Modules Support
    $systemArgs = array('enableShortURLsSupport' => false,
                        'generateXMLURLs' => false);
    xarMod::init($systemArgs, $whatToLoad);

    /**************************************************************
    * Install modules table and insert the modules module
    **************************************************************/
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'modules', 'initfunc'  => 'init'))) {
        return;
    }

    //jojodee - Now add the module entries to the module table
    // This is a bit premature but as setting vars with xarModVar involves geting base module info
    // none will be successful unless we have the specific module in the module table
    $modulesTable = $systemPrefix .'_modules';

       // Insert authsystem module entry and set to active
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'authsystem', 42, 'authsystem', '1.0.0', 1, 'Core Authentication', 'System', 1, 0,3)";
    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Insert base module entry
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'base', 68, 'base', '0.1.0', 1, 'Core Admin', 'Global', 1, 1,3)";

    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Insert installer module entry
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'installer', 200, 'installer', '1.0.0', 1, 'Core Utility', 'Global', 0, 0, 3)";

    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Insert blocks module entry
    // Update the version number as required
    $seqId = $dbconn->GenId($modulesTable);
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'blocks', 13, 'blocks', '1.0.0', 1, 'Core Utility', 'Global', 1, 0, 3)";
    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Insert themes module entry
    $seqId = $dbconn->GenId($modulesTable);
    // Version number will be updated from the modules/themes/xarversion.php script
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'themes', 70, 'themes', '1.4.0', 1, 'Core Utility', 'Global', 1, 0, 3)";
    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

   // Insert roles module entry
    $seqId = $dbconn->GenId($modulesTable);
    // Version number will be updated from the modules/roles/xarversion.php script
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'roles', 27, 'roles', '1.1.0', 1, 'Core Utility', 'Users & Groups', 1, 1, 3)";
    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // Insert privileges module entry
    $seqId = $dbconn->GenId($modulesTable);
    // Version number will be updated from the modules/privileges/xarversion.php script
    $query = "INSERT INTO $modulesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state
     ) VALUES (?, 'privileges', 1098, 'privileges', '1.0.1', 1, 'Core Utility', 'Users & Groups', 1, 0,3)";
    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    /****************************************************************
    * Install roles module and set up default roles
    ****************************************************************/
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'roles',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }

    /**************************************************************
    * Install privileges module and setup default privileges
    **************************************************************/
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'privileges',
                                 'initfunc'  => 'init'))) {
        return NULL;
    }


    /**************************************************************
    * Install the blocks module
    **************************************************************/
    // the installation of the blocks module depends on the modules module
    if (!xarInstallAPIFunc('initialise',
                           array('directory'=>'blocks', 'initfunc'=>'init'))) {
        return;
    }

    /**************************************************************
    * Install the authsystem module
    **************************************************************/
    if (!xarInstallAPIFunc('initialise',
                           array('directory'=>'authsystem', 'initfunc'=>'init'))) {
        return;
    }
    /**************************************************************
    * Install the themes module
    **************************************************************/

    if (!xarInstallAPIFunc('initialise',
                           array('directory'=>'themes', 'initfunc'=>'init'))) {
        return;
    }


   //jojodee - Now add the default installation themes
    $themesTable = $systemPrefix .'_themes';

     $seqId = $dbconn->GenId($themesTable);
    // Insert default theme entry
    $seqId = 1;
    $query = "INSERT INTO $themesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_mode,xar_author, xar_homepage, xar_email,
              xar_description, xar_contactinfo, xar_publishdate, xar_license, xar_version, xar_xaraya_version, xar_bl_version, xar_class, xar_state)
              VALUES (?, 'default', 1105, 'default', '1','Xarigami Team', 'http://xarigami.org','http://xarigami.com',
              'Default System Theme for Xarigami Framework','http://xarigami.com', '08/03/2012', 'GPL','2.0.0','1.0','1.0',0, 3)";

    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    //set defaul theme var
    xarModSetVar('themes','default','default');
    // Fill language list(?)

     $seqId = $dbconn->GenId($themesTable);
    // Insert default theme entry
    $seqId = 2;
    $query = "INSERT INTO $themesTable
              (xar_id, xar_name, xar_regid, xar_directory, xar_mode,xar_author, xar_homepage, xar_email,
              xar_description, xar_contactinfo, xar_publishdate, xar_license, xar_version, xar_xaraya_version, xar_bl_version, xar_class, xar_state)
              VALUES (?, 'installtheme', 996, 'installtheme', '1','Doug Daulton, rev 2 by AndyV', 'http://xarigami.com','contactus@xarigami.com',
              'Default System Installer for Xarigami Framework','xarigami', '01/15/2003', 'GPL','3.0.0','1.0','1.0',0,3)";

    $result = $dbconn->Execute($query,array($seqId));
    if (!$result) return;

    // TODO: move this to some common place in Xarigami ?
    // Register BL user tags
    // Include a JavaScript file in a page
    xarTplRegisterTag(
        'base', 'base-include-javascript', array(),
        'base_javascriptapi_handlemodulejavascript'
    );
    // Render JavaScript in a page
    xarTplRegisterTag(
        'base', 'base-render-javascript', array(),
        'base_javascriptapi_handlerenderjavascript'
    );

    // Render time since string
    xarTplRegisterTag('base', 'base-timesince', array(),
                      'base_userapi_handletimesincetag');

    // TODO: is this is correct place for a default value for a modvar?
    xarModSetVar('base', 'AlternatePageTemplate', 'homepage');


    // Initialisation successful
    return true;
}

/**
 * Upgrade the base module from an old version
 *
 * @param string oldVersion
 * @return bool
 */
function base_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '0.1':
        // compatability upgrade, nothing to be done
        break;
    }
    return true;
}

/**
 * Delete the base module
 *
 * @param none
 * @return bool false, as this module cannot be removed
 */
function base_delete()
{
  //this module cannot be removed
  return false;
}

?>
