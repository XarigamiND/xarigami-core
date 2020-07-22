<?php
/**
 * Xarigami Security functions
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Security
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 *
 *
 * Notes on security system
 *
 * Special UID and GIDS:
 *  UID -1 corresponds to 'all users', includes unregistered users
 *  GID -1 corresponds to 'all groups', includes unregistered users
 *  UID 0 corresponds to unregistered users
 *  GID 0 corresponds to unregistered users
 *
 */
//check do we need to import xarCore here?
sys::import('xarigami.xarCore');

if (file_exists(sys::varpath() . '/security/on.touch')) {
    sys::import('xarigami.xarCacheSecurity');
}

sys::import('modules.privileges.xarclass.xarprivileges');
sys::import('modules.roles.xarclass.xarroles');


/**
 * Start the security subsystem
 *
 * @access protected
 * @return bool true
 */
function xarSecurity_init()
{
    // Subsystem initialized, register a handler to run when the request is over
    $prefix = xarDB::$prefix;
    $tables = array('security_masks' => $prefix . '_security_masks',
                    'security_acl' => $prefix . '_security_acl',
                    'privileges' => $prefix . '_privileges',
                    'privmembers' => $prefix . '_privmembers',
                    'security_realms' => $prefix . '_security_realms',
                    'security_instances' => $prefix . '_security_instances',
                    'security_levels' => $prefix . '_security_levels',
                    'modules' => $prefix . '_modules',
                    'security_privsets' => $prefix . '_security_privsets'
                    );
    xarDB::importTables($tables);
    //register_shutdown_function ('xarSecurity__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for xarSecurity
 *
 * @access private
 */
function xarSecurity__shutdown_handler()
{
    //xarLogMessage("xarSecurity shutdown handler");
}

/*
 * schemas - holds all component/instance schemas
 * Should wrap this in a static one day, but the information
 * isn't critical so we'll do it later
 */
$schemas = array();



/**
 * xarMakeGroup: create an entry in the database for a group
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string name
 * @return  bool
 */
function xarMakeGroup($name, $uname='')
{
    $roles = new xarRoles();
    return $roles->makeGroup($name,$uname);
}

/**
 * xarMakeUser: create an entry in the database for a user
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string name
 * @return bool
 */
function xarMakeUser($name,$uname,$email,$pass='',$dateReg='',$valCode='',$state=3,$authModule='')
{
    $roles = new xarRoles();
    return $roles->makeUser($name,$uname,$email,$pass,$dateReg,$valCode,$state,$authModule);
}

/**
 * xarMakeRoleRoot: defines an entry in the database as the root of a role tree
 *
 * This is a wrapper function
 * @access public
 * @param  string $name
 * @return bool
 */
function xarMakeRoleRoot($name)
{
    $roles = new xarRoles();
    return $roles->isRoot($name);
}

/**
 * xarMakeRoleMemberByName: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string child name
 * @param  string parent name
 * @return bool
 */
function xarMakeRoleMemberByName($childName, $parentName)
{
    $roles = new xarRoles();
    return $roles->makeMemberByName($childName, $parentName);
}

/**
 * xarMakeRoleMemberByUname: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string child uname
 * @param  string parent uname
 * @return bool
 */
function xarMakeRoleMemberByUname($childName, $parentName)
{
    $roles = new xarRoles();
    $parent = $roles->ufindRole($parentName);
    $child = $roles->ufindRole($childName);

    return $parent->addMember($child);
}

/**
 * xarMakeRoleMemberByID: create a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string child ID
 * @param  string parent ID
 * @return bool
 */
function xarMakeRoleMemberByID($childId, $parentId)
{
    $roles = new xarRoles();
    $parent = $roles->getRole($parentId);
    $child = $roles->getRole($childId);

    return $parent->addMember($child);
}

/**
 * xarRemoveRoleMemberByID: destroys a parent-child relationship in the database between two roles
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string $childID
 * @param  string $parentID
 * @return bool
 */
function xarRemoveRoleMemberByID($childId, $parentId)
{
    $roles = new xarRoles();
    $parent = $roles->getRole($parentId);
    $child = $roles->getRole($childId);

    return $parent->removeMember($child);
}

/**
 * xarRegisterPrivilege: create an entry in the database for a privilege
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string name
 * @param  integer realm
 * @param  string module
 * @param  string component
 * @param  string instance
 * @param  integer level
 * @param  string description
 * @return bool
 */
function xarRegisterPrivilege($name,$realm,$module,$component,$instance,$level,$description='')
{
    $privileges = new xarPrivileges();

    // Check if the privilege already exists
    $privilege = $privileges->findPrivilege($name);
    if (!$privilege) {
        return $privileges->register($name,$realm,$module,$component,$instance,xarSecurityLevel($level),$description);
    }
    return;
}

/**
 * xarUpdatePrivilege: update an existing priv entry in the database for a privilege
 *
 * This is a wrapper function
 *
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 * @access public
 * @param  string  $name
 * @param  integer $realm
 * @param  string  $module
 * @param  string  $component
 * @param  string  $instance
 * @param  integer $level
 * @param  string  $description
 * @return bool
 */
function xarUpdatePrivilege($name,$realm,$module,$component,$instance,$level,$description='')
{
    $priv = new xarPrivileges();

    // Check if the privilege already exists
    $privilege = $priv->findPrivilege($name);
    if ($privilege) {
        $pargs = array('pid'=>$privilege->pid,'parentid'=>$privilege->parentid,'name'=>$name,'realm'=>$realm,'module'=>$module,'component'=>$component,'instance'=>$instance,'level'=>$level,'description'=>$description);
        $privupdate = new xarPrivilege($pargs);
        $update = $privupdate->update();
    }
    return;
}

/**
 * xarMakePrivilegeRoot: defines an entry in the database as the root of a privilege tree
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string $name
 * @return  bool
 */
function xarMakePrivilegeRoot($name)
{
    $privileges = new xarPrivileges();
    return $privileges->makeEntry($name);
}

/**
 * xarMakePrivilegeMember: create a parent-child relationship in the database between two privileges
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string childName
 * @param  string  parentName
 * @return bool
 */
function xarMakePrivilegeMember($childName, $parentName)
{
    $privileges = new xarPrivileges();
    return $privileges->makeMember($childName, $parentName);
}

/**
 * xarAssignPrivilege: assign a privilege to a role
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string  privilege name
 * @param  string role name
 * @return bool
 */
function xarAssignPrivilege($privilege,$role)
{
    $privileges = new xarPrivileges();
    return $privileges->assign($privilege,$role);
}

/**
 * xarRemovePrivileges: removes the privileges registered by a module from the database
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string module
 * @return  bool
 */
function xarRemovePrivileges($module)
{
    $privileges = new xarPrivileges();

    // Get the pids for the module
    $modulePrivileges = $privileges->findPrivilegesForModule($module);
    foreach ($modulePrivileges as $modulePrivilege) {
        $modulePrivilege->remove();
    }
}

/**
 * xarDefineInstance: creates an instance definition in the database
 *
 * This is a wrapper function
 *
 * @access public
 * @param  string module
 * @param  string type
 * @param  string query
 * @param  integer propagate
 * @param  string table2
 * @param  integer childId
 * @param  integer parentId
 * @param  string description
 * @return bool
 */
function xarDefineInstance($module,$type,$query,$propagate=0,$table2='',$childId='',$parentId='',$description='')
{
    $privileges = new xarPrivileges();

    return $privileges->defineInstance($module,$type,$query,$propagate,$table2,$childId,$parentId,$description);
}

/**
 * xarRemoveInstances: removes the instances registered by a module from the database
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string module
 * @return  bool
 */
function xarRemoveInstances($module, $component='')
{

    $privileges = new xarPrivileges();
    return $privileges->removeInstances($module,$component);
}

/**
 * xarGetGroups: returns an array of all the groups in the database
 *
 * This is a wrapper function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @return array of strings
 */
function xarGetGroups()
{
    $roles = new xarRoles();
    return $roles->getgroups();
}
/* xarFindGroup: returns a group name given it's ID
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string name
 * @return  object role
 */
function xarFindGroup($uid)
{
    $roles = new xarRoles();
    $role = $roles->getGroup($uid);
    if (is_array($role)) {
        return $role['name'];
    } else {
        return FALSE;
    }
}
/* xarFindRole: returns a role object by its name
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string name
 * @return  object role
 */
function xarFindRole($name)
{
    $roles = xar::Roles();
    return $roles->findRole($name);
}

function xarUFindRole($name)
{
    $roles = xar::Roles();
    return $roles->ufindRole($name);
}

function xarCurrentRole()
{
    $roles = xar::Roles();
    return $roles->getRole(xarSessionGetVar('uid'));
}

function xarIsParent($name1, $name2)
{
    $roles = xar::Roles();
    $role1 = $roles->findRole($name1);
    $role2 = $roles->ufindRole($name2);
    if (is_object($role1) && is_object($role2)) {
        return $role2->isParent($role1);
    }
    return false;
}

function xarIsAncestor($name1, $name2)
{
    $roles = xar::Roles();
    $role1 = $roles->findRole($name1);
    $role2 = $roles->ufindRole($name2);
    if (is_object($role1) && is_object($role2)) {
        return $role2->isAncestor($role1);
    }
    return false;
}

/* xarTree: creates a tree object
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string name
 * @return  object role
 */
function xarTree()
{
    // Since the class xarTreeRenderer exists in both roles and privileges this can lead to errors.
    sys::import('modules.roles.xartreerenderer');
    $tree = new xarTreeRenderer();
    return $tree;
}

/* xarReturnPrivilege: stores a privilege from an external wizard in the repository.
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   integer pid,level
 * @param   strings pid,name,realm,module,component
 * @param   array instance
 * @return  boolean
 */
function xarReturnPrivilege($pid,$name,$realm,$module,$component,$instance,$level,$pparentid=0)
{
    $privs = xar::Privileges();
    return $privs->returnPrivilege($pid,$name,$realm,$module,$component,$instance,$level,$pparentid);
}

/* xarSecurityLevel: gets a security level based on its name.
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   integer levelname
 * @return  security level
 */
function xarSecurityLevel($levelname)
{
    $masks = new xarMasks();
    return $masks->xarSecLevel($levelname);
}

/**
 * xarPrivExists: checks whether a privilege exists.
 *
 *
 * @access  public
 * @param   string name of privilege
 * @return  boolean
 */
function xarPrivExists($name)
{
    $privileges = new xarPrivileges();
    $priv = $privileges->findPrivilege($name);
    if ($priv) return TRUE;
    else return FALSE;
}

/* xarMaskExists: checks whether a mask exists.
 *
 *
 * @access  public
 * @param   string name of mask
 * @param   string module of mask
 * @return  boolean
 */
function xarMaskExists($name,$module="All",$component="All")
{
    $masks = new xarMasks();
    $mask = $masks->getMask($name,$module,$component,TRUE);
    if ($mask) return TRUE;
    else return FALSE;
}

/* xarQueryMask: returns a mask suitable for inclusion in a structured query
 *
 *
 * @access  public
 * @param   string name of mask
 * @param   string module of mask
 * @return  boolean
 */
function xarQueryMask($mask, $showException=1, $component='', $instance='', $module='', $role='', $realm=0, $level =0)
{
   $masks = new xarMasks();
   return $masks->querymask($mask, $component, $instance, $module, $role,$realm,$level);
}

/**
 * xarSecurityCheck: check a role's privileges against the masks of a component
 *
 * Checks the current group or user's privileges against a component
 * This function should be invoked every time a security check needs to be done
 *
 * @access public
 * @param  string  $mask
 * @param  integer $showException
 * @param  string  $component
 * @param  string  $instance
 * @param  string  $module
 * @param  string  $role
 * @return bool
 */
function xarSecurityCheck($mask, $showException=1, $component='', $instance='', $module='', $role='', $realm=0, $level=0)
{
    static $installing = NULL;
    static $masks = NULL;

    if ($installing === NULL) {
        $installing = xarCoreCache::getCached('installer','installing');
        $installing = isset($installing) && $installing == TRUE ? TRUE : sys::isInstall();
    }
    if ($installing) return TRUE;

    if ($masks === NULL) {
        if (xarDB::$count === 1) {
            $masks = xar::Masks();
        } else {
            $masks = new xarMasks();
        }
    } else if (xarDB::$count !== 1 || $masks->dbconn !== xarDB::$dbconn || $masks->prefix !== xarDB::$prefix) {
        $masks = new xarMasks();
    }

    return $masks->xarSecurityCheck($mask, $showException, $component, $instance, $module, $role, $realm, $level);
}

/**
 * xarRegisterMask: wrapper function for registering a mask
 *
 * @access public
 * @param  string  $name
 * @param  integer $realm
 * @param  string  $module
 * @param  string  $component
 * @param  string  $instance
 * @param  integer $level
 * @param  string  $description
 * @return bool
 */
function xarRegisterMask($name,$realm,$module,$component,$instance,$level,$description='')
{
        $masks = new xarMasks();
        return $masks->register($name,$realm,$module,$component,$instance,xarSecurityLevel($level),$description);
}

/**
 * xarUnregisterMask: wrapper function for unregistering a mask
 *
 * @access public
 * @param  string name
 * @return bool
 */
function xarUnregisterMask($name)
{
    $masks = new xarMasks();
    return $masks->unregister($name);
}

/**
 * xarRemoveMasks: removes the masks registered by a module from the database
 *
 * This is a wrapper function
 *
 * @access  public
 * @param   string module
 * @return  bool
 */
function xarRemoveMasks($module)
{
    $privileges = new xarPrivileges();
    return $privileges->removeMasks($module);
}

/**
 * Generate an authorisation key
 *
 * The authorisation key is used to confirm that actions requested by a
 * particular user have followed the correct path.  Any stage that an
 * action could be made (e.g. a form or a 'delete' button) this function
 * must be called and the resultant string passed to the client as either
 * a GET or POST variable.  When the action then takes place it first calls
 * xarSecConfirmAuthKey() to ensure that the operation has
 * indeed been manually requested by the user and that the key is valid
 *
 * @access public
 * @param string modName the module this authorisation key is for (default = requested module) (optinal or deprecated now?)
 * @return string an encrypted key for use in authorisation of operations
 * @todo bring back possibility of extra security by using date (See code)
 */
function xarSecGenAuthKey($modName = NULL)
{
     if (empty($modName)) {
         list($modName) = xarRequest::getInfo();
     }

    // Date gives extra security but leave it out for now
    // $key = xarSessionGetVar('rand') . $modName . date ('YmdGi');
    //$key = xarSessionGetVar('rand') . strtolower($modName);
    $key = xarSessionGetVar('rand') . strtolower(xarUserGetVar('uname'));
    // Encrypt key
    $authid = md5($key);

    // Tell xarCache not to cache this page
    xarCoreCache::setCached('Page.Caching', 'nocache', TRUE);

    // Return encrypted key
    return $authid;
}

/**
 * Confirm an authorisation key is valid
 *
 * See description of xarSecGenAuthKey for information on
 * this function
 *
 * @access public
 * @param string authIdVarName
 * @return bool true if the key is valid, false if it is not
 * @throws ForbiddenOperationException
 * @todo bring back possibility of time authorized keys
 */
function xarSecConfirmAuthKey($modName=NULL, $authIdVarName='authid', $catch=FALSE)
{

    if(!isset($modName)) list($modName) = xarRequest::getInfo();
    $authid = xarRequest::getVar($authIdVarName);

    // Regenerate static part of key
    //$partkey = xarSession::getVar('rand') . strtolower($modName);
    //jojo - review this change ...
    $partkey = xarSession::getVar('rand') . strtolower(xarUserGetVar('uname'));
// Not using time-sensitive keys for the moment
//    // Key life is 5 minutes, so search backwards and forwards 5
//    // minutes to see if there is a match anywhere
//    for ($i=-5; $i<=5; $i++) {
//        $testdate  = mktime(date('G'), date('i')+$i, 0, date('m') , date('d'), date('Y'));
//
//        $testauthid = md5($partkey . date('YmdGi', $testdate));
//        if ($testauthid == $authid) {
//            // Match
//
//            // We've used up the current random
//            // number, make up a new one
//            srand((double)microtime()*1000000);
//            xarSession::setVar('rand', rand());
//
//            return true;
//        }
//    }

    if ((md5($partkey)) == $authid) {
        // Match - generate new random number for next key and leave happy
        srand((double)microtime()*1000000);
        xarSession::setVar('rand', rand());

        return TRUE;
    }
    // Not found, assume invalid
    if ($catch === TRUE) {
        throw new ForbiddenOperationException();
    }else {
        return FALSE;
    }
}

?>