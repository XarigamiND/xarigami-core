<?php
/**
 * Initialise the mail module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @subpackage Xarigami Mail module
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 * @author John Cox
 */
/**
 * Initialise the mail module
 *
 * @author John Cox <niceguyeddie@xaraya.com>
 * @access public
 * @param none $
 * @return true on success or void or false on failure
 * @throws 'DATABASE_ERROR'
 * @todo nothing
 */
function mail_init()
{

    xarModSetVar('mail', 'server', 'mail');
    xarModSetVar('mail', 'replyto', '0');
    xarModSetVar('mail', 'wordwrap', '78');
    xarModSetVar('mail', 'priority', '3');
    xarModSetVar('mail', 'smtpPort', '25');
    xarModSetVar('mail', 'smtpHost', 'Your SMTP Host');
    xarModSetVar('mail', 'encoding', '8bit');
    xarModSetVar('mail', 'html', false);

    // when a module item is created
    if (!xarMod::registerHook('item', 'create', 'API',
            'mail', 'admin', 'hookmailcreate')) {
        return false;
    }
    // when a module item is deleted
    if (!xarMod::registerHook('item', 'delete', 'API',
            'mail', 'admin', 'hookmaildelete')) {
        return false;
    }
    // when a module item is changed
    if (!xarMod::registerHook('item', 'update', 'API',
            'mail', 'admin', 'hookmailchange')) {
        return false;
    }

    xarRegisterMask('EditMail','All','mail','All','All','ACCESS_EDIT');
    xarRegisterMask('AddMail','All','mail','All','All','ACCESS_ADD');
    xarRegisterMask('DeleteMail', 'All','mail','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminMail','All','mail','All','All','ACCESS_ADMIN');

    /* This init function brings authsystem to version 0.01 run the upgrades for the rest of the initialisation */
    return mail_upgrade('0.1');
}

/**
 * Activate the mail module
 *
 * @access public
 * @param none $
 * @return bool
 * @throws DATABASE_ERROR
 */
function mail_activate()
{
    return true;
}

/**
 * Upgrade the mail module from an old version
 *
 * @author John Cox <niceguyeddie@xaraya.com>
 * @access public
 * @param  $oldVersion
 * @return true on success or false on failure
 * @throws no exceptions
 * @todo create separate xar_mail_queue someday
 * @todo allow mail gateway functionality
 */
function mail_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '0.1':
    case '0.1.0':
        // clean up double hook registrations
        xarMod::unregisterHook('item', 'update', 'API', 'mail', 'admin', 'hookmailchange');
        xarMod::registerHook('item', 'update', 'API', 'mail', 'admin', 'hookmailchange');
        $hookedmodules = xarMod::apiFunc('modules', 'admin', 'gethookedmodules',
                                       array('hookModName' => 'mail'));
        if (isset($hookedmodules) && is_array($hookedmodules)) {
            foreach ($hookedmodules as $modname => $value) {
                foreach ($value as $itemtype => $val) {
                    xarMod::apiFunc('modules','admin','enablehooks',
                                  array('hookModName' => 'mail',
                                        'callerModName' => $modname,
                                        'callerItemType' => $itemtype));
                }
            }
        }

    case '0.1.1':
        xarModSetVar('mail', 'ShowTemplates', false);
        xarModSetVar('mail', 'suppresssending', false);
        xarModSetVar('mail', 'redirectsending', false);
        xarModSetVar('mail', 'redirectaddress', '');
    case '0.1.2':
        xarModSetVar('mail', 'loopmail', false);
        xarModSetVar('mail', 'onbehalf', false);

    case '0.1.3':
    case '0.1.4':
        xarModSetVar('mail', 'itemsperpage', 20);
        xarModSetVar('mail', 'throttlemax', 0);
        xarModSetVar('mail', 'throttlespan', '1h');
         // add the mail queue table
        xarDBLoadTableMaintenanceAPI();
        $dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $queuetable = $xartable['mail_queue'];

       $fields = array(
        'xar_mid'           => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
        'xar_info'          => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_name'          => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_recipients'    => array('type'=>'text','null'=>false),
        'xar_ccinfo'        => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_ccname'        => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_ccrecipients'  => array('type'=>'text','null'=>false),
        'xar_bccinfo'       => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_bccname'       => array('type'=>'varchar','size'=>100,'default'=>'0','null'=>false),
        'xar_bccrecipients' => array('type'=>'text','null'=>false),
        'xar_subject'       => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_message'       => array('type'=>'blob','size'=>'long'),
        'xar_htmlmessage'   => array('type'=>'blob','size'=>'long'),
        'xar_priority'      => array('type'=>'integer','size'=>'tiny','default'=>'3','null'=>false),
        'xar_encoding'      => array('type'=>'varchar','size'=>16, 'default'=>'','null'=>false),
        'xar_wordwrap'      => array('type'=>'integer','null'=>false,'default'=>78),
        'xar_from'          => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_fromname'      => array('type'=>'varchar','size'=>100,'default'=>'','null'=>false),
        'xar_usetemplates' => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
        'xar_when'          => array('type'=>'float','size'=>'decimal','width'=>'10','decimals'=>'0','null'=>false),
        'xar_attachName'    => array('type'=>'text','size'=>'long','null'=>false),
        'xar_attachPath'    => array('type'=>'text', 'size'=>'long','null'=>false),
        'xar_htmlmail'      => array('type'=>'integer','size'=>'tiny','default'=>'0','null'=>false),
        'xar_queued'        => array('type'=>'float','size'=>'decimal','width'=>'10','decimals'=>'0','null'=>false),
        'xar_sent'          => array('type'=>'float','size'=>'decimal','width'=>'10','decimals'=>'0','null'=>false),
        );

        $query = xarDBCreateTable($queuetable,$fields);

        $result = $dbconn->Execute($query);
        if (!$result) return;
        /*
        //if  Data dict is causing probs on some mysql installs
        $datadict = xarDB::newDataDict($dbconn, 'ALTERTABLE');
        // define the fields in our queue table
        $fields = "
            xar_mid           I         AUTO       PRIMARY,
            xar_info          C(100)    NotNull    DEFAULT '',
            xar_name          C(100)    NotNull    DEFAULT '',
            xar_recipients    TEXT      NotNull,
            xar_ccinfo        C(100)    NotNull    DEFAULT '',
            xar_ccname        C(100)    NotNull    DEFAULT '',
            xar_ccrecipients  TEXT      NotNull,
            xar_bccinfo       C(100)    NotNull    DEFAULT '',
            xar_bccname       C(100)    NotNull    DEFAULT '',
            xar_bccrecipients TEXT      NotNull,
            xar_subject       C(100)    NotNull    DEFAULT '',
            xar_message       B         NotNull,
            xar_htmlmessage   B         NotNull,
            xar_priority      I1        NotNull    DEFAULT 3,
            xar_encoding      C(16)     NotNull    DEFAULT '',
            xar_wordwrap      I4        NotNull    DEFAULT 78,
            xar_from          C(100)    NotNull    DEFAULT '',
            xar_fromname      C(100)    NotNull    DEFAULT '',
            xar_usetemplates  I1        NotNull    DEFAULT 0,
            xar_when          N         NotNull    DEFAULT 0,
            xar_attachName    XL        NotNull,
            xar_attachPath    XL        NotNull,
            xar_htmlmail      I1        NotNull    DEFAULT 0,
            xar_queued        N         NotNull    DEFAULT 0,
            xar_sent          N         NotNull    DEFAULT 0
        ";
        // create the table
        $result = $datadict->changeTable($queuetable, $fields);
        if (!$result) return;
        */
    case '0.2.0': //current version

        break;
    }
    return true;
}

/**
 * Delete the mail module
 *
 * @author John Cox <niceguyeddie@xaraya.com>
 * @access public
 * @param no $ parameters
 * @return true on success or false on failure
 * @todo restore the default behaviour prior to 1.0 release
 */
function mail_delete()
{
  // Get database information
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['mail_queue']);
    if (empty($query)) return; // throw back
    // Drop the table and send exception if returns false.
    $result = $dbconn->Execute($query);
    if (!$result) return;
    //remove all Mod vars
    xarModDelAllVars('mail');

    // Remove Masks and Instances
    xarRemoveMasks('mail');
    xarRemoveInstances('mail');

    return true;
}

?>