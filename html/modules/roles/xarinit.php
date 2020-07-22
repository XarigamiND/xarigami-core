<?php
/**
 * Initialise the roles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Initialise the roles module
 *
 * @access public
 * @param none $
 * @return bool
 * @throws DATABASE_ERROR
 */
function roles_init()
{
    // Get database setup
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $sitePrefix = xarDB::$prefix;
    $tables['roles'] = $sitePrefix . '_roles';
    $tables['rolemembers'] = $sitePrefix . '_rolemembers';
    // prefix_roles
    /**
     * CREATE TABLE xar_roles (
     *    xar_uid int(11) NOT NULL auto_increment,
     *    xar_name varchar(100) NOT NULL default '',
     *    xar_type int(11) NOT NULL default '0',
     *    xar_users int(11) NOT NULL default '0',
     *    xar_uname varchar(100) NOT NULL default '',
     *    xar_email varchar(100) NOT NULL default '',
     *    xar_pass varchar(100) NOT NULL default '',
     *    xar_date_reg datetime NOT NULL default '0000-00-00 00:00:00',
     *    xar_valcode varchar(35) NOT NULL default '',
     *    xar_state int(3) NOT NULL default '0',
     *    xar_auth_module varchar(100) NOT NULL default '',
     *    PRIMARY KEY  (xar_uid)
     * )
     */

    $query = xarDBCreateTable($tables['roles'],
        array('xar_uid' => array('type' => 'integer',
                'null' => false,
                'default' => '0',
                'increment' => true,
                'primary_key' => true),
            'xar_name' => array('type' => 'varchar',
                'size' => 255,
                'null' => false,
                'default' => ''),
            'xar_type' => array('type' => 'integer',
                'null' => false,
                'default' => '0'),
            'xar_users' => array('type' => 'integer',
                'null' => false,
                'default' => '0'),
            'xar_uname' => array('type' => 'varchar',
                'size' => 255,
                'null' => false,
                'default' => ''),
            'xar_email' => array('type' => 'varchar',
                'size' => 255,
                'null' => false,
                'default' => ''),
            'xar_pass' => array('type' => 'varchar',
                'size' => 100,
                'null' => false,
                'default' => ''),
            'xar_date_reg' => array('type' => 'varchar',
                'size' => 100,
                'null' => false,
                'default' => '0000-00-00 00:00:00'),
            'xar_valcode' => array('type' => 'varchar',
                'size' => 35,
                'null' => false,
                'default' => ''),
            'xar_state' => array('type' => 'integer',
                'null' => false,
                'default' => '3'),
            'xar_auth_module' => array('type' => 'varchar',
                'size' => 100,
                'null' => false,
                'default' => '')));

    if (!$dbconn->Execute($query)) return;

    // role type is used in all group look-ups (e.g. security checks)
    $index = array('name' => 'i_' . $sitePrefix . '_roles_type',
        'fields' => array('xar_type')
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;
    // username must be unique (for login) + don't allow groupname to be the same either
    $index = array('name' => 'i_' . $sitePrefix . '_roles_uname',
        'fields' => array('xar_uname'),
        'unique' => true
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;
    // allow identical "display names" here??
    $index = array('name' => 'i_' . $sitePrefix . '_roles_name',
        'fields' => array('xar_name'),
        'unique' => false
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;
    // allow identical e-mail here (???) + is empty for groups !
    $index = array('name' => 'i_' . $sitePrefix . '_roles_email',
        'fields' => array('xar_email'),
        'unique' => false
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;
    // role state is used in many user lookups
    $index = array('name' => 'i_' . $sitePrefix . '_roles_state',
        'fields' => array('xar_state'),
        'unique' => false
        );
    $query = xarDBCreateIndex($tables['roles'], $index);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // prefix_rolemembers
    /**
     * CREATE TABLE xar_rolemembers (
     *    xar_uid int(11) NOT NULL default '0',
     *    xar_parentid int(11) NOT NULL default '0'
     * )
     */

    $query = xarDBCreateTable($tables['rolemembers'],
        array('xar_uid' => array('type' => 'integer',
                'null'  => false,
                'default' => '0'),
            'xar_parentid' => array('type' => 'integer',
                'null' => false,
                'default' => '0')));
    if (!$dbconn->Execute($query)) return;

    $index = array('name' => 'i_' . $sitePrefix . '_rolememb_id',
        'fields' => array('xar_uid','xar_parentid'),
        'unique' => true);
    $query = xarDBCreateIndex($tables['rolemembers'], $index);
    if (!$dbconn->Execute($query)) return;

    $index = array('name' => 'i_' . $sitePrefix . '_rolememb_parentid',
        'fields' => array('xar_parentid'),
        'unique' => false);
    $query = xarDBCreateIndex($tables['rolemembers'], $index);
    if (!$dbconn->Execute($query)) return;
    //Database Initialisation successful

# --------------------------------------------------------
#
# Register hooks
#
    if (!xarMod::registerHook('item', 'search', 'GUI',
            'roles', 'user', 'search')) {
        return false;
    }
    if (!xarMod::registerHook('item', 'usermenu', 'GUI',
            'roles', 'user', 'usermenu')) {
        return false;
    }
    xarMod::apiFunc('modules', 'admin', 'enablehooks',
        array('callerModName' => 'roles', 'hookModName' => 'roles'));
    // This won't work because the dynamicdata hooks aren't registered yet when this is
    // called at installation --> put in xarinit.php of dynamicdata instead
    //xarMod::apiFunc('modules','admin','enablehooks',
    // array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));

    /* This init function brings our module to version 1.1.0, run the upgrades for the rest of the initialisation */
    xarLogMessage ('ROLES: initialization about to go to upgrade');
    return roles_upgrade('1.1.0');
}

function roles_activate()
{

    return true;
}

/**
 * Upgrade the roles module from an old version
 *
 * @access public
 * @param string oldVersion $
 * @return bool true on success
 * @throws DATABASE_ERROR
 */
function roles_upgrade($oldVersion)
{
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $sitePrefix = xarDB::$prefix;
    $tables['roles'] = $rolesTable=$sitePrefix . '_roles';
    $tables['rolemembers'] = $roleMembersTable = $sitePrefix . '_rolemembers';

    // Upgrade dependent on old version number
    switch ($oldVersion) {
        case '1.01':
            break;
        case '1.1.0':

            // delete the old roles modvars
                xarModDelVar('roles', 'allowregistration');
                xarModDelVar('roles', 'rolesperpage');
                xarModDelVar('roles', 'uniqueemail'); //this really should be in roles to avoid non core dependencies
                xarModDelVar('roles', 'askwelcomeemail');
                xarModDelVar('roles', 'askvalidationemail');
                xarModDelVar('roles', 'askdeactivationemail');
                xarModDelVar('roles', 'askpendingemail');
                xarModDelVar('roles', 'askpasswordemail');
                xarModDelVar('roles', 'lockouttime');
                xarModDelVar('roles', 'lockouttries');
                xarModDelVar('roles', 'minage');
                xarModDelVar('roles', 'disallowednames');
                xarModDelVar('roles', 'disallowedemails');
                xarModDelVar('roles', 'disallowedips');

            // create one new roles modvar
            xarModSetVar('roles', 'locale', '');
            xarModSetVar('roles', 'userhome','');
            xarModSetVar('roles', 'userlastlogin','');
            xarModSetVar('roles', 'primaryparent','');
            $sitetimezone =  xarConfigGetVar('Site.Core.TimeZone');
            xarModSetVar('roles', 'usertimezone',$sitetimezone);
            xarModSetVar('roles', 'setuserhome',false);
            xarModSetVar('roles', 'setprimaryparent',false);
            xarModSetVar('roles', 'setpasswordupdate',false);
            xarModSetVar('roles', 'setuserlastlogin',false);
            xarModSetVar('roles', 'setusertimezone',false);
            xarModSetVar('roles', 'displayrolelist',false);
            xarModSetVar('roles', 'usereditaccount',true);
            xarModSetVar('roles', 'allowuserhomeedit',false);
            xarModSetVar('roles', 'loginredirect', true);
            xarModSetVar('roles', 'allowexternalurl', false);

            //fall through
        case '1.1.1':
            $emails = "none@none.com\npresident@whitehouse.gov\nnone@invalid.tld";
            $disallowedemails = serialize($emails);
            xarModSetVar('roles', 'disallowedemails', $disallowedemails);

            $lockdata = array('roles' => array( array('uid' => 4,
                                              'name' => 'Administrators',
                                              'notify' => TRUE)),
                                  'message' => '',
                                  'locked' => 0,
                                  'notifymsg' => '',
                                  'killactive' => FALSE);
            xarModSetVar('roles', 'lockdata', serialize($lockdata));

            // save the uids of the default roles for later
            //this cannot go here as the roles have not been created yet till priv setup
            /*
            $role = xarFindRole('Everybody');
            xarModSetVar('roles', 'everybody', $role->getID());
            $role = xarFindRole('Anonymous');
            xarConfigSetVar('Site.User.AnonymousUID', $role->getID());
            // set the current session information to the right anonymous uid
            xarSession_setUserInfo($role->getID(), 0);
            $role = xarFindRole('Admin');
            if (!isset($role)) {
                $role=xarUFindRole('Admin');
            }
            xarModSetVar('roles', 'admin', $role->getID());
            */
            xarModSetVar('roles', 'uniqueemail', true);
            $disallowed = xarModGetVar('roles', 'disallowedemails');
            if (!isset($disallowed)) {
                   xarModSetVar('roles','disallowedemails',''); //let's set it so it doesn't error else leave content to upgrade.php
            }

            //fall through
        case '1.1.2':
             //jojodee - due to the problem with activate function recalling vars with every mod upgrade - moving these vars to here
            //at worst these will be reset but no worse than upgrading and calling activate again
            xarModSetVar('roles', 'defaultauthmodule', 42); //Setting a default in case it was removed earlier
            xarModSetVar('roles', 'defaultregmodule', '');
            xarModSetVar('roles', 'itemsperpage', 20);
            xarModSetVar('roles', 'rolesdisplay', 'tabbed');
            xarModSetVar('roles', 'usersendemails', false);
            xarModSetVar('roles', 'requirevalidation', true);

            $lockdata= unserialize(xarModGetVar('roles', 'lockdata'));
            $lockdata['killactive']= false; // this was missed in some upgrades versions
            xarModSetVar('roles','lockdata',serialize($lockdata));
            xarModSetVar('roles', 'firstloginurl','');
            xarModSetVar('roles', 'advpasswordreset',false);
            xarModSetVar('roles', 'resetexpiry',0);
            //fall through

        case '1.1.3':
             xarModSetVar('roles', 'passrequirements', '/([a-zA-Z]*)[0-9]+([a-zA-Z]*)/');
             xarModSetVar('roles', 'minpasslength', 5);
             xarModSetVar('roles', 'maxpasslength', 0);
             xarModSetVar('roles', 'passhelptext', 'Your password must contain only alphanumeric (a-z,A-Z,0-9) characters and include at least one number');
             xarModSetVar('roles', 'usernameurls', false);
        case '1.1.4':
              xarModSetVar('roles', 'useModuleAlias',false);
              xarModSetVar('roles','aliasname','');
        case '1.1.5' :
              //bring these back to roles :)
              $disallowednames = "Admin\nRoot\nLinux";
              xarModSetVar('roles', 'disallowednames',serialize($disallowednames));
              $disallowedips = "";
              xarModSetVar('roles', 'disallowedips',serialize($disallowedips));
              xarModSetVar('roles','requiredisplayname',true);

        case '1.1.6' :
              xarModSetVar('roles','uniquedisplay',false); //unique display name
              xarModSetVar('roles','defaultproxy',0);    //can do the proxy login
              xarModSetVar('roles','requirelogin',true);
              xarModSetVar('roles','proxygroup',5);  //can be proxied
              xarModSetVar('roles', 'memberliststate', 0); //do not show member list or menu list in the Roles menu
              xarTplRegisterTag(
                'roles', 'roles-avatar', array(),
                'roles_userapi_renderavatar'
                );

        case '1.1.7' :
            //done in upgrade.php due to table updates and roles table not created at this time
        case '1.1.8' :
           //In upgrade in installer (xaradmin.php) until we up the version of this roles module
            $defaulttimezone = xarConfigGetVar('Site.Core.TimeZone');
            $timezoneinfo = new DateTimezone($defaulttimezone);
            //this is a serialized value of timezone and offset
            $datetime = new DateTime();
            $offset = $timezoneinfo->getOffset($datetime);
            $timeinfoarray = array('timezone' => $defaulttimezone, 'offset' => $offset/(60*60)); //need it in hours not sec
            xarModSetVar('roles', 'usertimezone',serialize($timeinfoarray));
            xarModSetVar('roles', 'setuserlastvisit',false);
            xarModSetVar('roles', 'usehtmlmail',false);
            xarModSetVar('roles', 'anonurl','');
        break;
    }
    // Update successful
    return true;
}

/**
 * Delete the roles module
 *
 * @access public
 * @param none $
 * @return bool false, this module cannot be removed
 * @throws DATABASE_ERROR
 */
function roles_delete()
{
    // this module cannot be removed
    return false;

    /**
     * Drop the tables
     */
    // Get database information
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $query = xarDBDropTable($tables['roles']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    $query = xarDBDropTable($tables['rolemembers']);
    if (empty($query)) return; // throw back
    if (!$dbconn->Execute($query)) return;

    /**
     * Remove modvars, instances and masks
     */
    xarModDelAllVars('roles');
    xarRemoveMasks('roles');
    xarRemoveInstances('roles');

    // Deletion successful
    return true;
}

?>