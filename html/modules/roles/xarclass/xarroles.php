<?php
/**
 * xarRoles class
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

sys::import('modules.roles.xarincludes.xarQuery');

define('ROLES_STATE_DELETED',0);
define('ROLES_STATE_INACTIVE',1);
define('ROLES_STATE_NOTVALIDATED',2);
define('ROLES_STATE_ACTIVE',3);
define('ROLES_STATE_PENDING',4);
define('ROLES_STATE_CURRENT',98);
define('ROLES_STATE_ALL',99);
define('ROLES_USERTYPE',0);
define('ROLES_GROUPTYPE',1);
/**
 * xarRoles: class for the role repository
 *
 * Represents the repository containing all roles
 *
 * @package modules
 * @subpackage Roles module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @throws none
 * @todo none
 */
class xarRoles
{
    public $allgroups = array();
    public $users = array();
    public $dbconn;
    public $rolestable;
    public $rolememberstable;


    function __construct()
    {
        $this->dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $this->rolestable = $xartable['roles'];
        $this->rolememberstable = $xartable['rolemembers'];
    }

    /**
     * Get the list of all the uservars
     */
    public static function getdynamicuservarnames()
    {
        // moved in xarModUserVars.
        return xarModUserVars::$duvns;
    }

    /**
     * getgroups: returns all the current groups.
     *
     * Returns an array of all the groups in the roles repository
     * The repository contains an entry for each user and group.
     * This function will initially load the groups from the db into an array and return it.
     * On subsequent calls it just returns the array .
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param none $
     * @return array of arrays representing all the groups
     * @throws none
     * @todo none
     */
    function getgroups()
    {
        // check if we already have the groups stored
        if ((!isset($this->allgroups)) || count($this->allgroups) == 0) {
            // set up the query and get the groups
            $q = new xarQuery('SELECT');
            $q->addtable($this->rolestable,'r');
            $q->addtable($this->rolememberstable,'rm');
            $q->join('r.xar_uid','rm.xar_uid');
            $q->addfield('r.xar_uid AS uid');
            $q->addfield('r.xar_name AS name');
            $q->addfield('r.xar_users AS users');
            $q->addfield('rm.xar_parentid AS parentid');
            $q->eq('r.xar_type',1);
            $q->eq('r.xar_state',ROLES_STATE_ACTIVE);
            $q->setorder('r.xar_name');
            if (!$q->run()) return;

            $this->allgroups = $q->output();
        }
        return $this->allgroups;
    }

    /**
     * getgroup: returns an array representing a group
     *
     * Returns an array of representing a group in the roles repository
     * The repository contains an entry for each user and group.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param integer $
     * @return array representing the group
     * @throws none
     * @todo none
     */
    function getgroup($uid)
    {
        foreach($this->getgroups() as $group) {
            if ($group['uid'] == $uid) return $group;
        }
        return FALSE;
    }

    /**
     * getsubgroups: get the children of a group that are groups themselves
     *
     * This function is useful for setting up trees
     * We don't include users in the tree because there are too many to display
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access private
     * @param none $
     * @return array representing the subgroups of a group
     * @throws none
     * @todo none
     */
    function getsubgroups($uid)
    {
        $subgroups = array();
        $groups = $this->getgroups();
        foreach($groups as $subgroup) {
            if ($subgroup['parentid'] == $uid) {
                $subgroups[] = $subgroup;
            }
        }
        return $subgroups;
    }

    /**
     * getRole: gets a single role
     *
     * Retrieves a single role (user or group) from the roles repository
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer $
     * @return role object
     * @throws none
     * @todo none
     */
    function getRole($uid)
    {
        $cacheKey = 'Roles.ByUid';
        if(xarCoreCache::isCached($cacheKey,$uid)) {
            return xarCoreCache::getCached($cacheKey,$uid);
        }
        // Need to get it from DB.
        // TODO: move caching to _lookuprole?
        $r = $this->_lookuprole('xar_uid',(int) $uid);
        xarCoreCache::setCached($cacheKey,$uid,$r);
        return $r;
    }

    /**
     * findRole: finds a single role based on its name
     *
     * Retrieves a single role object from the Roles repository
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @return role object
     * @throws none
     * @todo none
     */
    function findRole($name)
    {
        return $this->_lookuprole('xar_name',$name);
    }
    /**
     * findRole: finds a single role based on its username
     *
     * Retrieves a single role object from the Roles repository
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @return role object
     * @throws none
     * @todo none
     */
    function ufindRole($uname)
    {
        return $this->_lookuprole('xar_uname',$uname);
    }

    function _lookuprole($field,$value,$state=ROLES_STATE_ALL)
    {
        // retrieve the object's data from the repository
        // set up and execute the query
        $q = new xarQuery('SELECT',$this->rolestable);
        $q->eq($field,$value);
        if ($state == ROLES_STATE_CURRENT) {
            $q->ne('xar_state',ROLES_STATE_DELETED);
        } elseif ($state != ROLES_STATE_ALL) {
            $q->eq('xar_state',$state);
        }

        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;

        // set the data in an array
        $row = $q->row();
        if (empty($row)) return;

        $duvs = xarModUserVars::getDynVars($row['xar_uid']);
        $pargs = array(
            'uid' =>         $row['xar_uid'],
            'name' =>        $row['xar_name'],
            'type' =>        $row['xar_type'],
            'users' =>       $row['xar_users'],
            'uname' =>       $row['xar_uname'],
            'email' =>       $row['xar_email'],
            'pass' =>        $row['xar_pass'],
            'date_reg' =>    $row['xar_date_reg'],
            'val_code' =>    $row['xar_valcode'],
            'state' =>       $row['xar_state'],
            'auth_module' => $row['xar_auth_module'],
            'duvs'          => $duvs);
        // create and return the role object
        return new xarRole($pargs);
    }

    /**
     * makeMemberByName: makes a role a child of a group
     *
     * Creates an entry in the rolemembers table
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @param string $
     * @return boolean
     * @throws none
     * @todo create exceptions for bad input
     */
    function makeMemberByName($childname, $parentname)
    {
        // retrieve the parent's data from the repository
        $query = "SELECT * FROM $this->rolestable WHERE xar_name = ?";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($parentname));
        if (!$result) return;
        // create the parent object
        list($uid, $name, $type, $parentid, $uname, $email, $pass,
            $date_reg, $val_code, $state, $auth_module) = $result->fields;
        $pargs = array('uid' => $uid,
            'name' => $name,
            'type' => $type,
            'parentid' => $parentid,
            'uname' => $uname,
            'email' => $email,
            'pass' => $pass,
            'date_reg' => $date_reg,
            'val_code' => $val_code,
            'state' => $state,
            'auth_module' => $auth_module);
        $parent = new xarRole($pargs);
        // retrieve the child's data from the repository
        $query = "SELECT * FROM $this->rolestable
                  WHERE xar_name = ?";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($childname));
        if (!$result) return;
        // create the child object
        list($uid, $name, $type, $parentid, $uname, $email, $pass,
            $date_reg, $val_code, $state, $auth_module) = $result->fields;
        $pargs = array('uid' => $uid,
            'name' => $name,
            'type' => $type,
            'parentid' => $parentid,
            'uname' => $uname,
            'email' => $email,
            'pass' => $pass,
            'date_reg' => $date_reg,
            'val_code' => $val_code,
            'state' => $state,
            'auth_module' => $auth_module);
        $child = new xarRole($pargs);
        // done
        return $parent->addMember($child);
    }

    /**
     * isRoot: defines the root of the roles hierarchy
     *
     * Creates an entry in the rolemembers table
     * This is a convenience class for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @return boolean
     * @throws none
     * @todo create exceptions for bad input
     */
    function isRoot($rootname)
    {
        // get the data for the root object
        $query = "SELECT xar_uid
                  FROM $this->rolestable
                  WHERE xar_name = ?";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($rootname));
        if (!$result) return;
        // create the entry
        list($uid) = $result->fields;
        $query = "INSERT INTO $this->rolememberstable
                VALUES (?,0)";
        // Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query, array($uid))) return;
        // done
        return TRUE;
    }

    /**
     * makeUser: add a new role object to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     * This is a convenience method for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @param string $
     * @param string $
     * @return boolean
     * @throws none
     * @todo create exception handling for bad input
     */
    function makeUser($name, $uname, $email, $pass = 'xarigami', $datereg = '', $valcode = '', $state = ROLES_STATE_ACTIVE, $authmodule = '', $duvs=array())
    {
        // TODO: validate the email address
        if (empty($name) && empty($uname) || empty($email)) {
            $msg = xarML('You must enter a user name and a valid email address.',
                'roles');
            throw new DuplicateException(array(),$msg);
            xarSession::setVar('errormsg', _MODARGSERROR);
            return FALSE;
        }
        // Confirm that this group or user does not already exist
        $q = new xarQuery('SELECT',$this->rolestable);
        $q->eq('xar_uname',$uname);

        if (!$q->run()) return;
        if ($q->getrows() == 1) {
            $msg = xarML('This entry already exists.',
                'roles');
            throw new DuplicateException(array(),$msg);
            xarSession::setVar('errormsg', _GROUPALREADYEXISTS);
            return FALSE;
        }
        // create an ID for the user
        $nextId = $this->dbconn->genID($this->rolestable);

        // set up the query and create the entry
        $tablefields = array(
            array('name' => 'xar_uid',         'value' => $nextId),
            array('name' => 'xar_name',        'value' => $name),
            array('name' => 'xar_type',        'value' => 0),
            array('name' => 'xar_uname',       'value' => $uname),
            array('name' => 'xar_email',       'value' => $email),
            array('name' => 'xar_pass',        'value' => $pass),
            array('name' => 'xar_date_reg',    'value' => time()),
            array('name' => 'xar_valcode',     'value' => $valcode),
            array('name' => 'xar_state',       'value' => $state),
            array('name' => 'xar_auth_module', 'value' => $authmodule),
        );
        $q = new xarQuery('INSERT',$this->rolestable);
        $q->addfields($tablefields);
        if (!$q->run()) return;
        foreach($duvs as $key => $value) xarModSetUserVar($key, $value, $nextId);
        // set email option to FALSE
        xarModSetUserVar('roles','usersendemails', FALSE, $nextId);
        // done
        return TRUE;
    }
    /**
     * makeGroup: add a new role object to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     * This is a convenience method for module developers
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param string $
     * @return boolean
     * @throws none
     */
    function makeGroup($name, $uname='')
    {
        if ($uname == '') $uname = $name;
       xarLogMessage('xarRoles-makeGroup - about to make group for'.$name);
       $rowcount= 0;
       $rolestable = $this->rolestable;
        $query = "SELECT xar_uid
                  FROM  $rolestable
                  WHERE xar_uname = ? and xar_type = 1";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($uname));

        if (!$result)
        {
            throw new Exception($e);
            return;
        }

        $rowcount = $result->_numOfRows;
        if ($rowcount > 0)
        {
             xarLogMessage('xarRoles-makeGroup - group already exists for'.$name);
            $msg = xarML('This entry already exists.');
            throw new DuplicateException(NULL,$msg);
        }

        $createdate = time();

        $nextId = $this->dbconn->genId($rolestable);
         xarLogMessage('xarRoles-makeGroup - About to insert new entry for'.$name);
        $xartype = 1;
        $query = "INSERT INTO  $rolestable
                    (xar_uid, xar_name, xar_type, xar_uname,xar_date_reg)
                  VALUES (?,?,?,?,?)";
        $bindvars = array($nextId, $name, $xartype, $uname, $createdate);
        $result = $this->dbconn->Execute($query,$bindvars);
        if(!$result) return;
        // done
        return TRUE;
    }
}

/**
 * xarRole: class for the role object
 *
 * Represents a single role (user or group)
 *
 * @package modules
 * @subpackage Roles module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @throws none
 * @todo none
 */
class xarRole
{
    var $uid;          //the id of this user or group
    var $name;         //the name of this user or group
    var $type;         //the type of this role (0=user, 1=group)
    var $parentid;     //the id of the parent of this role
    var $uname;        //the user name (not used by groups)
    var $email;        //the email address (not used by groups)
    var $pass;         //the password (not used by groups)
    var $date_reg;     //the date of registration
    var $val_code;     //the validation code of this user or group
    var $state;        //the state of this user or group
    var $auth_module;  //no idea what this is (not used by groups)
    //var $userhome;     //home page for this role
    //var $primaryparent;//primary group for this role
    var $duvs;         //property for holding dynamic user vars
    var $parentlevel;  //we use this just to store transient information

    var $dbconn;
    var $rolestable;
    var $rolememberstable;
    var $privilegestable;
    var $acltable;

    var $allprivileges;

    /**
     * xarRole: constructor for the role object
     *
     * Retrieves a single role (user or group) from the roles repository
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param array $
     * @return role object
     * @throws none
     * @todo none
     */
    function __construct($pargs)
    {
        extract($pargs);

        $this->dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $this->rolestable = $xartable['roles'];
        $this->rolememberstable = $xartable['rolemembers'];
        $this->privilegestable = $xartable['privileges'];
        $this->acltable = $xartable['security_acl'];

        if (!isset($uid)) $uid = 0;
        if (!isset($parentid)) $parentid = 0;
        if (!isset($uname)) $uname = xarSessionGetVar('uid') . time();
        if (!isset($email)) $email = '';
        if (!isset($pass)) $pass = '';
        if (!isset($state)) $state = ROLES_STATE_INACTIVE;
        // FIXME: why is date_reg a varchar in the database and not a date field?
        if (!isset($date_reg)) $date_reg = time();
        if (!isset($val_code)) $val_code = 'createdbyadmin';
        // FIXME: what is a sensible default for auth_module?
        if (!isset($auth_module)) $auth_module = 0;

        $this->uid = (int) $uid;
        $this->name = $name;
        $this->type = (int) $type;
        $this->parentid = (int) $parentid;
        $this->uname = $uname;
        $this->email = $email;
        $this->pass = $pass;
        $this->state = (int) $state;
        $this->date_reg = $date_reg;
        $this->val_code = $val_code;
        $this->auth_module = $auth_module;
        $this->parentlevel = 0;
        $this->duvs = isset($duvs) ? $duvs : array();
    }

    /**
     * add: add a new role object to the repository
     *
     * Creates an entry in the repository for a role object that has been created
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function add()
    {
        if (empty($this->name)) {
            $msg = xarML('You must enter a name.','roles');
            throw new DuplicateException(array(),$msg);
            xarSession::setVar('errormsg', _MODARGSERROR);

        }
        if ((empty($this->type)) && (empty($this->uname) || empty($this->email))) {
            $msg = xarML('You must enter a user name and a valid email address.', 'roles');
            throw new DuplicateException(array(),$msg);
            return FALSE;
        }
        // Confirm that this group or user does not already exist
        $q = new xarQuery('SELECT',$this->rolestable);
        if ($this->type == 1) {
            $q->eq('xar_name',$this->name);
        } else {
            $q->eq('xar_uname',$this->uname);
        }

        if (!$q->run()) return;

        if ($q->getrows() == 1) {
            $msg = xarML('This entry already exists.',
                'roles');
            throw new DuplicateException(array(),$msg);
            return FALSE;
        }

        $nextId = $this->dbconn->genID($this->rolestable);

        $tablefields = array(
            array('name' => 'xar_uid',      'value' => $nextId),
            array('name' => 'xar_name',     'value' => $this->name),
            array('name' => 'xar_uname',    'value' => $this->uname),
            array('name' => 'xar_date_reg', 'value' => time()),
            array('name' => 'xar_valcode',  'value' => $this->val_code),
        );
        $q = new xarQuery('INSERT',$this->rolestable);
        $q->addfields($tablefields);
        if ($this->type == 1) {
            $groupfields = array(
                array('name' => 'xar_type', 'value' => 1)
            );
            $q->addfields($groupfields);
        } else {
            $userfields = array(
                array('name' => 'xar_type',       'value' => 0),
                array('name' => 'xar_email',      'value' => $this->email),
                array('name' => 'xar_pass',       'value' => md5($this->pass)),
                array('name' => 'xar_state',      'value' => $this->state),
                array('name' => 'xar_auth_module','value' => $this->auth_module)
            );
            $q->addfields($userfields);
        }
        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;

        // Fetch the last inserted user ID, bail if an exception was thrown
        $this->uid = $this->dbconn->PO_Insert_ID($this->rolestable, 'xar_uid');
        if (!$this->uid) return;

        foreach ($this->duvs as $key => $value) xarModSetUserVar('roles',$key,$value,$this->uid);
        //set the email useage for this user to FALSE
        xarModSetUserVar('roles','usersendemails', FALSE, $this->uid);
        $parts = new xarRoles();
        $parentpart = $parts->getRole($this->parentid);
        return $parentpart->addMember($this);
    }

    /**
     * addMember: adds a role to a group
     *
     * Make a user or group a member of another group.
     * A user of group can have any number of parents or children..
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param role $ object
     * @return boolean
     * @throws none
     * @todo none
     */
    function addMember($member)
    {
        // bail if the purported parent is not a group.
        if ($this->isUser()) return FALSE;

        $q = new xarQuery('SELECT',$this->rolememberstable);
        $q->eq('xar_uid',$member->getID());
        $q->eq('xar_parentid',$this->getID());
        if (!$q->run()) return;
        // This relationship already exists. Move on
        if ($q->row() != array()) return TRUE;
        $uid = $member->getID();
        $group = $this->getID();
        // add the necessary entry to the rolemembers table
        $q = new xarQuery('INSERT',$this->rolememberstable);
        $q->addfield('xar_uid',$uid);
        $q->addfield('xar_parentid',$group);
        if (!$q->run()) return;

        // for children that are users
        // add 1 to the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count
            $query = "SELECT xar_users FROM $this->rolestable WHERE xar_uid = ?";
            $result = $this->dbconn->Execute($query,array($this->getID()));
            if (!$result) return;
            // add 1 and update.
            list($users) = $result->fields;
            $users = $users + 1;
            $query = "UPDATE " . $this->rolestable . " SET xar_users = ? WHERE xar_uid = ?";
            $bindvars = array($users,$this->getID());
            if (!$this->dbconn->Execute($query,$bindvars)) return;
        }
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        // done
         xarLogMessage('ROLES: A user with UID '. $uid.' was added to group '.$group.' by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
        return TRUE;
    }

    /**
     * removeMember: removes a role from a group
     *
     * Removes a user or group as an entry of another group.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param role $ object
     * @return boolean
     * @throws none
     * @todo none
     */
    function removeMember($member)
    {
        // delete the relevant entry from the rolemembers table
        $query = "DELETE FROM $this->rolememberstable WHERE xar_uid= ? AND xar_parentid= ?";
        $bindvars = array($member->getID(), $this->getID());
        if (!$this->dbconn->Execute($query,$bindvars)) return;
        // for children that are users
        // subtract 1 from the users field of the parent group. This is for display purposes.
        if ($member->isUser()) {
            // get the current count.
            $query = "SELECT xar_users FROM $this->rolestable WHERE xar_uid = ?";
            $result = $this->dbconn->Execute($query,array($this->getID()));
            if (!$result) return;
            // subtract 1 and update.
            list($users) = $result->fields;
            $users = $users - 1;
            $query = "UPDATE " . $this->rolestable . " SET xar_users = ? WHERE xar_uid = ?";
            $bindvars = array($users, $this->getID());
            if (!$this->dbconn->Execute($query,$bindvars)) return;
        }
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        // done
             xarLogMessage('ROLES: A user with '. $member->getID().' was removed from group '.$this->getID().' by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
        return TRUE;
    }

    function update()
    {
        $q = new xarQuery('UPDATE',$this->rolestable);
        $q->addfield('xar_name',$this->name);
        $q->addfield('xar_type',$this->type);
        $q->addfield('xar_uname',$this->uname);
        $q->addfield('xar_email',$this->email);
        $q->addfield('xar_state',$this->state);
        $q->addfield('xar_auth_module',$this->auth_module);
        if ($this->pass != '') $q->addfield('xar_pass',md5($this->pass));
        $q->eq('xar_uid',$this->getID());

        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;

        foreach ($this->duvs as $key => $value) xarModSetUserVar('roles',$key,$value,$this->getID());
        xarLogMessage('ROLES: Roles user '. $this->getID().' was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
        return TRUE;
    }

    /**
     * remove: make a role deleted
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo flag illegal deletes
     */
    function remove()
    {
        // get a list of all relevant entries in the rolemembers table
        // where this role is the child
        $query = "SELECT xar_parentid FROM $this->rolememberstable WHERE xar_uid= ?";
        // Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($this->getID()));
        if (!$result) return;
        // get the Roles class so we can use its methods
        $parts = new xarRoles();
        // go through the list, retrieving the roles and detaching each one
        // we need to do it this way because the method removeMember is more than just
        // a simple SQL DELETE
        while (!$result->EOF) {
            list($parentid) = $result->fields;
            $parentpart = $parts->getRole($parentid);
            // Check that a parent was returned
            if ($parentpart) {
                $parentpart->removeMember($this);
            }
            $result->MoveNext();
        }
        // delete the relevant entry in the roles table
        //$query = "DELETE FROM $this->rolestable
        //      WHERE xar_uid=" . $this->getID();

        //Let's not remove the role yet.  Instead, we want to deactivate it

        $deleted = xarML('deleted');
        $q = new xarQuery('UPDATE',$this->rolestable);
        $q->addfield('xar_uname',$this->getUser() . "[" . $deleted . "]" . time());
        $q->addfield('xar_email',$this->getEmail() . "[" . $deleted . "]" . time());
        $q->addfield('xar_state',ROLES_STATE_DELETED);
        $q->eq('xar_uid',$this->getID());

        // Execute the query, bail if an exception was thrown
        if (!$q->run()) return;
        // done

// get all the privileges that were assigned to this role
        $privileges = $this->getAssignedPrivileges();
// remove the privilege assignments for this role
        foreach ($privileges as $priv) {
            $this->removePrivilege($priv);
        }
        xarLogMessage('ROLES: Roles user '. $this->getID().' was marked as deleted by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);

// CHECKME: re-assign all privileges to the child roles ? (probably not)

        return TRUE;
    }


    /**
     * purge: make a role purged
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     */
    function purge()
    {
        // no checks here. just do it
        $this->remove();
        $state = ROLES_STATE_DELETED;
        $uname = xarML('deleted') . microtime(TRUE) .'.'. $this->uid;
        $name = '';
        $pass = '';
        $email = '';
        $date_reg = '';
        $q = new xarQuery('UPDATE',$this->rolestable);
        $q->addfield('xar_name',$name);
        $q->addfield('xar_uname',$uname);
        $q->addfield('xar_pass',$pass);
        $q->addfield('xar_email',$email);
        $q->addfield('xar_date_reg',$date_reg);
        $q->addfield('xar_state',$state);
        $q->eq('xar_uid',$this->uid);
        if(!$q->run()) return;
        return TRUE;
    }


    /**
     * Gets all the privileges in the database.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege arrays like ('pid' => x, 'name' => y)
     */
    function getAllPrivileges()
    {
        static $allprivileges = array();
        if (empty($allprivileges)) {
            $query = "SELECT xar_pid, xar_name FROM $this->privilegestable ORDER BY xar_name";
            $result = $this->dbconn->Execute($query);
            if (!$result) return;

            $ind = 0;
            while (!$result->EOF) {
                list($pid, $name) = $result->fields;
                $allprivileges[$ind++] = array('pid' => $pid, 'name' => $name);
                $result->MoveNext();
            }
        }
        return $allprivileges;
    }


    /**
     * Gets all the privileges assigned directly to this role.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege objects
     */
    function getAssignedPrivileges()
    {
        static $stmt = NULL;  // For each uid, the query is the same, prepare it once.

        $cacheKey = "Privileges.ByUid";
        if(xarCoreCache::isCached($cacheKey,$this->uid)) {
            return xarCoreCache::getCached($cacheKey,$this->uid);
        }
        // We'll have to get it.
        xarLogMessage("ROLE: getting privileges for uid: $this->uid");
        $query = "SELECT  xar_pid, xar_name, xar_realm, xar_module,
                          xar_component, xar_instance, xar_level, xar_description
                  FROM    $this->privilegestable p, $this->acltable acl
                  WHERE   p.xar_pid = acl.xar_permid AND acl.xar_partid = ?";
        $result = $this->dbconn->Execute($query,array($this->uid));
        if (!$result) return;

        if (!class_exists('xarPrivilege')) sys::import('modules.privileges.xarclass.xarprivileges');
        $privileges = array();

        while (!$result->EOF) {
            list($pid, $name, $realm, $module, $component, $instance, $level,
                $description) = $result->fields;
            $perm = new xarPrivilege(array('pid' => $pid,
                    'name' => $name,
                    'realm' => $realm,
                    'module' => $module,
                    'component' => $component,
                    'instance' => $instance,
                    'level' => $level,
                    'description' => $description,
                    'parentid' => 0));
            $privileges[] = $perm;
            $result->MoveNext();
        }
        xarCoreCache::setCached($cacheKey,$this->uid,$privileges);
        return $privileges;
    }


    /**
     * Gets all the privileges inherited by this role.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return array of privilege objects
     */
    function getInheritedPrivileges()
    {
        $ancestors = $this->getAncestors();
        $inherited = array();
        foreach ($ancestors as $ancestor) {
            $perms = $ancestor->getAssignedPrivileges();
            while (list($key, $perm) = each($perms)) {
                $inherited[] = $perm;
            }
        }
        return $inherited;
    }

    /**
     * Checks whether this role has a specific privilege assigned or inherited.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @return boolean
     */
    function hasPrivilege($privname)
    {
        $privs = $this->getAssignedPrivileges();
        foreach ($privs as $privilege)
            if ($privilege->name == $privname) return TRUE;
        $privs = $this->getInheritedPrivileges();
        foreach ($privs as $privilege)
            if ($privilege->name == $privname) return TRUE;
        return FALSE;
    }

    /**
     * assignPrivilege: assigns a privilege to a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param privilege $ object
     * @return boolean
     * @throws none
     * @todo none
     */
    function assignPrivilege($perm)
    {
        // create an entry in the privmembers table
        $query = "INSERT INTO $this->acltable VALUES (?,?)";
        $bindvars = array($this->getID(),$perm->getID());
        if (!$this->dbconn->Execute($query,$bindvars)) return;
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
        xarLogMessage('ROLES: Roles user '. $this->getID().' was assigned a new privilege id '.$perm->getID().' by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
        return TRUE;
    }

    /**
     * removePrivilege: removes a privilege from a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param privilege $ object
     * @return boolean
     * @throws none
     * @todo none
     */
    function removePrivilege($perm)
    {
        // remove an entry from the privmembers table
        $query = "DELETE FROM $this->acltable
                  WHERE xar_partid= ? AND xar_permid= ?";
        $bindvars = array($this->uid, $perm->getID());
        if (!$this->dbconn->Execute($query,$bindvars)) return;
        // empty the privset cache
        // $privileges = new xarPrivileges();
        // $privileges->forgetprivsets();
 xarLogMessage('ROLES: Roles user '. $this->uid.' had privilege id '.$perm->getID().' removed by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
        return TRUE;
    }

    /**
     * getUsers: get the members of a group that are users
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer state get users in this state
     * @param integer startnum get users beyond this number
     * @param integer numitems get a defined number of users
     * @param string order order the result (name, uname, type, email, date_reg, state...)
     * @param string selection get users within this selection criteria
     * @return boolean
     * @throws none
     * @todo none
     */
    function getUsers($state = ROLES_STATE_CURRENT, $startnum = 0, $numitems = 0, $order = 'name', $selection = NULL)
    {
        $query = "SELECT r.xar_uid, r.xar_name, r.xar_type, r.xar_uname,
                         r.xar_email, r.xar_pass, r.xar_date_reg,
                         r.xar_valcode, r.xar_state,r.xar_auth_module
                  FROM $this->rolestable r, $this->rolememberstable rm
                  WHERE r.xar_uid = rm.xar_uid AND
                        r.xar_type = ? AND
                        r.xar_state != ? AND
                        rm.xar_parentid = ?";
        // set up the query and get the data
        if ($state == ROLES_STATE_CURRENT) {
             $bindvars = array(ROLES_USERTYPE,ROLES_STATE_DELETED,$this->uid);

        } else {
             $bindvars = array(ROLES_USERTYPE, $state, $this->uid);
        }
        if (isset($selection)) $query .= $selection;
        $query .= " ORDER BY xar_" . $order;
        if ($startnum != 0) {
            $result = $this->dbconn->SelectLimit($query, $numitems, $startnum-1,$bindvars);
        } else {
            $result = $this->dbconn->Execute($query,$bindvars);
        }
        if (!$result) return;
        // CHECKME: I suppose this is what you meant here ?
        $parentid = $this->uid;
        // arrange the data in an array of role objects
        $users = array();
        while (!$result->EOF) {
            list($uid, $name, $type, $uname, $email, $pass,
                $date_reg, $val_code, $state, $auth_module) = $result->fields;
            $pargs = array('uid' => $uid,
                'name' => $name,
                'type' => $type,
                'parentid' => $parentid,
                'uname' => $uname,
                'email' => $email,
                'pass' => $pass,
                'date_reg' => $date_reg,
                'val_code' => $val_code,
                'state' => $state,
                'auth_module' => $auth_module);
            $vars = xarModUserVars::getDynVars($uid);
            $pargs = array_merge($pargs,$vars);
            $users[] = new xarRole($pargs);
            $result->MoveNext();
        }
        // done
        return $users;
    }

    /**
     * countChildren: count the members of a group
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer state count user in this state
     * @param string selection count user within this selection criteria
     * @param integer type group or user
     * @return boolean
     * @throws none
     * @todo none
     */
    function countChildren($state = ROLES_STATE_CURRENT, $selection = NULL, $type = NULL)
    {
        $q = new xarQuery('SELECT');
        $q->addfield('COUNT(r.xar_uid) AS children');
        $q->addtable($this->rolestable,'r');
        $q->addtable($this->rolememberstable,'rm');
        $q->join('r.xar_uid', 'rm.xar_uid');
        $q->eq('rm.xar_parentid', $this->uid);
        if ($state == ROLES_STATE_CURRENT) {
            $q->ne('r.xar_state', ROLES_STATE_DELETED);
        } else {
            $q->eq('r.xar_state', $state);
        }
        if (isset($type)) $q->eq('r.xar_type', $type);

        if (isset($selection)) {
            $query = $q->tostring() . $selection;
            if(!$q->run($query)) return;
        } else {
            if(!$q->run()) return;
        }
        $result = $q->row();
        return $result['children'];
    }

    /**
     * countUsers: count the members of a group that are users
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer state count user in this state
     * @param string selection count user within this selection criteria
     * @return boolean
     * @throws none
     * @todo none
     */
    function countUsers($state = ROLES_STATE_CURRENT, $selection = NULL)
    {
        return $this->countChildren(0, $state, $selection);
    }

    /**
     * getParents: returns the parent objects of a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return array of role objects
     * @throws none
     * @todo none
     */
    function getParents()
    {
        static $stmt = NULL;  // The query below is the same for each uid, prepare it once.

        $cacheKey = 'RoleParents.ByUid';
        // create an array to hold the objects to be returned
        $parents = array();
        // if this is the root return an empty array
        if ($this->uid == 1) return $parents;

        // if it's cached, we can return it
        if (xarCoreCache::isCached($cacheKey,$this->uid)) {
            return xarCoreCache::getCached($cacheKey,$this->uid);
        }

        // if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT r.*
                    FROM $this->rolestable r, $this->rolememberstable rm
                    WHERE r.xar_uid = rm.xar_parentid
                    AND rm.xar_uid = ?";
        $result = $this->dbconn->Execute($query,array($this->uid));
        if (!$result) return;
        // collect the table values and use them to create new role objects
        while (!$result->EOF) {
            list($uid, $name, $type, $parentid, $uname, $email, $pass,
                $date_reg, $val_code, $state, $auth_module) = $result->fields;
            $vars = array();

            $duvs = xarModUserVars::getDynVars($this->uid);
            $pargs = array('uid' => $uid,
                'name' => $name,
                'type' => $type,
                'parentid' => $parentid,
                'uname' => $uname,
                'email' => $email,
                'pass' => $pass,
                'date_reg' => $date_reg,
                'val_code' => $val_code,
                'state' => $state,
                'auth_module' => $auth_module,
                'duvs'=>$duvs);

            $parents[] = new xarRole($pargs);
            $result->MoveNext();
        }
        // done
        xarCoreCache::setCached($cacheKey,$this->uid,$parents);
        return $parents;
    }

    /**
     * getAncestors: returns all objects in the roles hierarchy above a role
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none
     * @return array of role objects. The objects can be queried with the getLevel() method to show their relationship (1=prents, 2=grandparents etc.).
     * @throws none
     */
    function getAncestors()
    {
        // if this is the root return an empty array
        if ($this->uid == 1) return array();
        // start by getting an array of the parents
        $parents = $this->getParents();
        $parents1 = array();
        foreach ($parents as $key => $parent) {
            $parents[$key]->parentlevel = 1;
        }
        //Reset the array pointer - else in some cases we may miss getting all ancestors
        reset($parents);
        // Get the parent field for each parent
        while (list($key, $parent) = each ($parents)) {
            $plevel = $parent->parentlevel + 1;
            $ancestors = $parent->getParents();
            foreach ($ancestors as $key1 => $ancestor) {
                $ancestors[$key1]->parentlevel = $plevel;
                $parents[] = $ancestors[$key1];
            }
        }

        $ancestors = array();
        // If this is a new ancestor add to the end of the array
        foreach ($parents as $parent) {
            $iscontained = FALSE;
            foreach ($ancestors as $ancestor) {
                //if ($parent->isEqual($ancestor)) {
                if ($parent->uid == $ancestor->uid) {
                    $iscontained = TRUE;
                    break;
                }
            }
            if (!$iscontained) $ancestors[] = $parent;
        }
        // done
        return $ancestors;
    }
    /**
     * getDescendants: get the descendaants of a group
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer state get users in this state
     * @return list of users
     * @throws none
     * @todo none
     */
    function getDescendants($state = ROLES_STATE_CURRENT, $grpflag=0)
    {
        $roles = xar::Roles();
        $role = $roles->getRole($this->uid);
        $users = $role->getUsers($state);
        $groups = $roles->getSubGroups($this->uid);
        $ua = array();
        foreach($users as $user){
            //using the ID as the key so that if a person is in more than one sub group they only get one email
            $ua[$user->uid] = $user;
        }
        //Get the sub groups and go for another round
        foreach($groups as $group){
            $role = $roles->getRole($group['uid']);
            if ($grpflag) {
                $ua[$group['uid']] = $role;
            }
            $users = $role->getDescendants($state);
            foreach($users as $user){
                $ua[$user->uid] = $user;
            }
        }
        return($ua);
    }

    /**
     * isEqual: checks whether two roles are equal
     *
     * Two role objects are considered equal if they have the same uid.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isEqual($role)
    {
        return $this->uid == $role->uid;//$this->getID() == $role->getID();
    }

    /**
     * isUser: checks whether this role is a user
     *
     * Users have type = 0.
     * Groups have type = 1.
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isUser()
    {
        return $this->getType() == 0;
    }

    /**
     * isParent: checks whether a role is a parent of this one
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isParent($role)
    {
        $parents = $this->getParents();
        foreach ($parents as $parent) {
            //if ($role->isEqual($parent)) return TRUE;
            // optimized in speed (copy and paste from isEqual)
            if ($role->uid == $parent->uid) return TRUE;
        }
        return FALSE;
    }

    /**
     * isAncestor: checks whether a role is an ancestor of this one
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return boolean
     * @throws none
     * @todo none
     */
    function isAncestor($role)
    {
        $ancestors = $this->getAncestors();
        foreach ($ancestors as $ancestor) {
            if ($role->isEqual($ancestor)) return TRUE;
        }
        return FALSE;
    }

    /**
     * adjustParentUsers: adjust of a user's parent user tallies
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param integer
     * @return boolean
     * @throws none
     * @todo none
     */
    function adjustParentUsers($adjust)
    {
        $q = new xarQuery('SELECT', $this->rolestable, 'xar_users AS users');
        $q1 = new xarQuery('UPDATE', $this->rolestable);
        $parents = $this->getParents();
        foreach ($parents as $parent) {
            $q->clearconditions();
            $q->eq('xar_uid', $parent->getID());
            $q1->clearconditions();
            $q1->eq('xar_uid', $parent->getID());

            // get the current count.
            if (!$q->run()) return;
            $row = $q->row();

            // adjust and update update.
            $q1->addfield('xar_users', $row['users'] + $adjust);
            if (!$q1->run()) return;
        }
        return TRUE;
    }

    /**
     * getPrivileges: returns the privileges in the privileges repository
     *
     * Returns an array of all the privileges objects
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param none $
     * @return array of privilege objects
     * @throws none
     * @todo none
     */
    function getPrivileges()
    {
        /*  // start by getting an array of all the privileges
            $query = "SELECT * FROM $this->privilegestable";
            $result = $this->dbconn->Execute($query);
            if (!$result) return;

            $privileges = array();
            while(!$result->EOF) {
                list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
                $pargs = array('pid' => $pid,
                            'name' => $name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description);
                array_push($privileges,new xarPrivilege($pargs))
                $result->MoveNext();
            }

    // start by getting an array of the parents
            $parents = $part->getParents();

    //Get the parent field for each parent
            while (list($key, $parent) = each($parents)) {
                $ancestors = $parent->getParents();
                foreach ($ancestors as $ancestor) {

    //If this is a new ancestor add to the end of the array
                    $iscontained = FALSE;
                    foreach ($parents as $parent){
                        if ($parent->isEqual($ancestor)) $iscontained = TRUE;
                    }
                    if (!$iscontained) array_push($parents, $ancestor);
                }
            }
*/ }

    /**
     * Gets and Sets
     *
     * Get and set methods for the class variables
     *
     * @author Marc Lutolf <marcinmilan@xaraya.com>
     * @access public
     * @param n $ /a
     * @return n /a
     * @throws none
     * @todo none
     */
    function getID()
    {
        return $this->uid;
    }
    function getName()
    {
        return $this->name;
    }
    function getHome()
    {
        $duv = isset($this->duvs['userhome']) ? $this->duvs['userhome'] : "";
         return $duv;
    }
    function getPrimaryParent()
    {
        $duv = isset($this->duvs['primaryparent']) ? $this->duvs['primaryparent'] : "";
        return $duv;
    }
    function getPasswordUpdate()
    {
        $duv = isset($this->duvs['passwordupdate']) ? $this->duvs['passwordupdate'] : "";
         return $duv;
    }
    function getUserLastLogin()
    {
        $duv = isset($this->duvs['userlastlogin']) ? $this->duvs['userlastlogin'] : "";
         return $duv;
    }
    function getUserTimezone()
    {
        $duv = isset($this->duvs['usertimezone']) ? $this->duvs['usertimezone'] : "";
         return $duv;
    }
    function getUname()
    {
        return $this->uname;
    }
    function getType()
    {
        return $this->type;
    }
    function getUser()
    {
        return $this->uname;
    }
    function getEmail()
    {
        return $this->email;
    }
    function getPass()
    {
        return $this->pass;
    }
    function getState()
    {
        return $this->state;
    }
    function getDateReg()
    {
        return $this->date_reg;
    }
    function getValCode()
    {
        return $this->val_code;
    }
    function getAuthModule()
    {
        return $this->auth_module;
    }
    function getLevel()
    {
        return $this->parentlevel;
    }

    function setName($var)
    {
        $this->name = $var;
    }
    function setHome($var)
    {
        $this->userhome = $var;
    }
    function setPasswordUpdate($var)
    {
        $this->passwordupdate= $var;
    }
    function setUserLastLogin($var)
    {
        $this->userlastlogin= $var;
    }
    function setUserTimezone($var)
    {
        $this->usertimezone= $var;
    }
    function setPrimaryParent($var)
    {
        $this->primaryparent = $var;
    }
    function setUname($var)
    {
        $this->uname = $var;
    }
    function setParent($var)
    {
        $this->parentid = $var;
    }
    function setUser($var)
    {
        $this->uname = $var;
    }
    function setEmail($var)
    {
        $this->email = $var;
    }
    function setPass($var)
    {
        $this->pass = $var;
    }
    function setState($var)
    {
        $this->state = $var;
    }
    function setDateReg($var)
    {
        $this->date_reg = $var;
    }
    function setValCode($var)
    {
        $this->val_code = $var;
    }
    function setAuthModule($var)
    {
        $this->auth_module = $var;
    }
    function setLevel($var)
    {
        $this->parentlevel = $var;
    }
}

?>
