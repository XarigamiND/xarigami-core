<?php
/**
 * Privileges administration API
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Privileges
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * xarMasks: class for the mask repository
 *
 * Represents the repository containing all security masks
 *
 * @package modules
 * @subpackage Privileges module
 *
 * @access  public
 * @throws  none
 * @todo    none
*/

class xarMasks
{
    public $dbconn = NULL;
    public $prefix = '';
    public $privilegestable = '';
    public $privmemberstable = '';
    public $maskstable = '';
    public $modulestable = '';
    public $realmstable ='';
    public $instancestable = '';
    public $levelstable = '';
    public $acltable = '';

    public $allmasks = array();
    public $levels = array();

    public $privilegeset;

/**
 * xarMasks: constructor for the class
 *
 * Just sets up the db connection and initializes some variables
 * This should really be a static class
 *
 * @access  public
 * @param   none
 * @return  the masks object
 * @throws  none
 * @todo    none
*/
    function __construct()
    {
        $this->dbconn = xarDB::$dbconn;
        //$xartable = &xarDB::$tables;
        $this->prefix = $prefix = xarDB::$prefix;
        $this->privilegestable = $prefix.'_privileges';
        $this->privmemberstable =  $prefix.'_privmembers';
        $this->maskstable =  $prefix.'_security_masks';
        $this->modulestable =  $prefix.'_modules';
        $this->realmstable =  $prefix.'_security_realms';
        $this->acltable =  $prefix.'_security_acl';
        $this->instancestable =  $prefix.'_security_instances';
        $this->levelstable =  $prefix.'_security_levels';

// hack this for display purposes
// probably should be defined elsewhere
        $this->levels = array(0=>'No Access (0)',
                    100=>'Overview (100)',
                    200=>'Read (200)',
                    300=>'Comment (300)',
                    400=>'Edit (400)',
                    500=>'Moderate (500)',
                    600=>'Add (600)',
                    700=>'Delete (700)',
                    800=>'Administer (800)');
    }

/**
 * getmasks: returns all the current masks for a given module and component.
 *
 * Returns an array of all the masks in the masks repository for a given module and component
 * The repository contains an entry for each mask.
 * This function will initially load the masks from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @access  public
 * @param   string: module name
 * @param   string: component name
 * @return  array of mask objects
 * @throws  list of exception identifiers which can be thrown
 * @todo    list of things which must be done to comply to relevant RFC
*/
    function getmasks($module = 'All',$component='All')
    {
        $bindvars = array();
        if ($module == '' || $module == 'All') {
            if ($component == '' || $component == 'All') {
                $query = "SELECT * FROM $this->maskstable ORDER BY xar_module, xar_component, xar_name";
            }
            else {
                $query = "SELECT * FROM $this->maskstable
                        WHERE (xar_component = ?)
                        OR (xar_component = ?)
                        OR (xar_component = ?)
                        ORDER BY xar_module, xar_component, xar_name";
                $bindvars = array($component,'All','None');
            }
        }
        else {
            if ($component == '' || $component == 'All') {
                $query = "SELECT * FROM $this->maskstable
                        WHERE xar_module = ? ORDER BY xar_module, xar_component, xar_name";
                $bindvars = array($module);
            }
            else {
                $query = "SELECT *
                          FROM $this->maskstable
                          WHERE (xar_module = ?) AND
                          ((xar_component = ?) OR
                           (xar_component = ?) OR
                           (xar_component = ?)
                            )
                    ORDER BY xar_module, xar_component, xar_name";
                $bindvars = array($module,$component,'All','None');
            }
        }
        $result = $this->dbconn->Execute($query,$bindvars);
        if (!$result) return;
        $masks = array();
        while(!$result->EOF) {
            list($sid, $name, $realm, $module, $component, $instance, $level,
                    $description) = $result->fields;
            $pargs = array('sid' => $sid,
                               'name' => $name,
                               'realm' => $realm,
                               'module' => $module,
                               'component' => $component,
                               'instance' => $instance,
                               'level' => $level,
                               'description' => $description);
            $masks[] = new xarMask($pargs);
            $result->MoveNext();
        }

        return $masks;
    }

/**
 * register: register a mask
 *
 * Creates a mask entry in the masks table
 * This function should be invoked every time a new mask is created
 *
 * @access  public
 * @param   array of mask values
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        // Check if the mask has already been registered, and update it if necessary.
// FIXME: make mask names unique across modules (+ across realms) ?
        // FIXME: is module/name enough? Perhaps revisit this with realms in mind.
        $query = 'SELECT xar_sid FROM ' . $this->maskstable
            . ' WHERE xar_module = ? AND xar_name = ?';
        $result = $this->dbconn->Execute($query, array($module, $name));
        if (!$result) return;
        if (!$result->EOF) {
            list($sid) = $result->fields;
            $query = 'UPDATE ' . $this->maskstable
                . ' SET xar_realm = ?, xar_component = ?,'
                . ' xar_instance = ?, xar_level = ?,'
                . ' xar_description = ?'
                . ' WHERE xar_sid = ?';
            $bindvars = array(
                $realm, $component, $instance, $level,
                $description, $sid
            );
        } else {
        $query = "INSERT INTO $this->maskstable VALUES (?,?,?,?,?,?,?,?)";
            $bindvars = array(
                $this->dbconn->genID($this->maskstable),
                          $name, $realm, $module, $component, $instance, $level,
                $description
            );
        }

        if (!$this->dbconn->Execute($query,$bindvars)) return;
        return TRUE;
    }

/**
 * unregister: unregister a mask
 *
 * Removes a mask entry from the masks table
 * This function should be invoked every time a mask is removed
 *
 * @access  public
 * @param   string representing a mask name
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function unregister($name)
    {
        $query = "DELETE FROM $this->maskstable WHERE xar_name = ?";
        if (!$this->dbconn->Execute($query,array($name))) return;
        return TRUE;
    }

/**
 * removeMasks: remove the masks registered by a module from the database
 * *
 * @access  public
 * @param   module name
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removemasks($module)
    {
        $query = "DELETE FROM $this->maskstable WHERE xar_module = ?";
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,array($module))) return;
        return TRUE;
    }

/**
 * winnow: merges two arrays of privileges to a single array of privileges
 *
 * The privileges are compared for implication and the less mighty are discarded
 * This is the way privileges hierarchies are contracted.
 *
 * @access  public
 * @param   array of privileges objects
 * @param   array of privileges objects
 * @return  array of privileges objects
 * @throws  none
 * @todo    create exceptions for bad input
*/
    function winnow($privs1, $privs2, $selfwinnow = FALSE)
    {
        $t1 = empty($privs1);
        $t2 = empty($privs2);
        if ($t1 && $t2) return array();
        if ($t1) return $privs2;
        if ($t2 && !$selfwinnow) return $privs1;

        $privs = array();

        foreach ($privs1 as $priv) {
            if (empty($priv->normalform)) $priv->normalize();
            $privs[$priv->sign] = $priv;
        }
        
        if (!$selfwinnow) {
            foreach ($privs2 as $priv) {
                if (empty($priv->normalform)) $priv->normalize();
                $privs[$priv->sign] = $priv;
            }
        }

        return array_values($privs);
    }

/**
 * xarSecLevel: Return an access level based on its name
 *
 * @access  public
 * @param   access level description
 * @return  access level
 * @throws  none
 * @todo    none
*/

    function xarSecLevel($levelname)
    {
        if (xarCoreCache::isCached('Security.xarSecLevel', $levelname)) {
            return xarCoreCache::getCached('Security.xarSecLevel', $levelname);
        }
        $query = "SELECT xar_level FROM $this->levelstable
                    WHERE xar_leveltext = ?";
        $result = $this->dbconn->Execute($query,array($levelname));
        if (!$result) return;
        $level = -1;
        if (!$result->EOF) list($level) = $result->fields;
        xarCoreCache::setCached('Security.xarSecLevel', $levelname, $level);
        return $level;
    }

/**
 * xarSecurityCheck: check a role's privileges against the masks of a component
 *
 * Checks the current group or user's privileges against a component
 * This function should be invoked every time a security check needs to be done
 *
 * @access  public
 * @param   component string
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function xarSecurityCheck($mask,$catch=1,$component='',$instance='',$module='',$rolename='',$pnrealm=0,$pnlevel=0)
    {
        static $userID = NULL;
        if (!isset($userID)) $userID = xarSessionGetVar('uid');

        xarLogMessage("PRIVS: xarSecurityCheck mask \"$mask\", instance \"$instance\", uid $userID");
        if ($userID == XARUSER_LAST_RESORT) return TRUE;

        $maskname = $mask;

        $mask =  $this->getMask($mask);
        if (!$mask) {
            // <mikespub> moved this whole $module thing where it's actually used, i.e. for
            // error reporting only. If you want to override masks with this someday, move
            // it back before the $this->getMask($mask) or wherever :-)

            // get the masks pertaining to the current module and the component requested
            // <mikespub> why do you need this in the first place ?
            if (empty($module)) list($module) = xarRequest::getInfo();

            // I'm a bit lost on this line. Does this var ever get set?
            // <mikespub> this gets set in xarBlock_render, to replace the xarModSetVar /
            // xarModVars::get combination you used before (although $module will generally
            // not be 'blocks', so I have no idea why this is needed anyway)
            if ($module == 'blocks' && xarCoreCache::isCached('Security.Variables','currentmodule'))
            $module = xarCoreCache::getCached('Security.Variables','currentmodule');

            if (empty($component)) {
                $msg = xarML('Did not find mask #(1) registered for an unspecified component in module #(2)', $maskname, $module);
            } else {
                $msg = xarML('Did not find mask #(1) registered for component #(2) in module #(3)', $maskname, $component, $module);
            }
           throw new Exception($msg);
        }

        // insert any component overrides
        if (!empty($component)) $mask->setComponent($component);
        // insert any instance overrides
        if (!empty($instance)) $mask->setInstance($instance);

        // insert any overrides of realm and level


        static $realmvalue = NULL;
        if (!isset($realmvalue)) $realmvalue = xarModVars::get('privileges', 'realmvalue');

        if (strpos($realmvalue,'string:') === 0) {
            $textvalue = substr($realmvalue,7);
            $realmvalue = 'string';
        } else {
            $textvalue = '';
        }
        switch($realmvalue) {
            //jojodee - should we not have a mapping so we can define realms of different types?
            //perhaps something for later.
            case "theme":
                $mask->setRealm(xarModVars::get('themes', 'default'));
                break;
            case "domain":
                static $host = NULL;
                if(!isset($host))
                    $host = xarServer::getHost();
                $parts = explode('.',$host);
                if (count($parts) < 2) {
                    $mask->setRealm('All');
                } else { //doublecheck
                    if ($parts[0]=='www') {
                        $mask->setRealm($parts[1]);
                    } else {
                        $mask->setRealm($parts[0]);
                    }
                }
                break;
            case "string":
                $mask->setRealm($textvalue);
                break;
            case "group":
                //get some info on the user
                $thisname=xarUserGetVar('uname');
                $role = xarUFindRole($thisname);
                $parent='Everybody'; //set a default
                //We now have primary parent implemented
                //Use primary parent if implemented else get first parent??
                //TODO: this needs to be reviewed
                $useprimary = xarModVars::get('roles','setprimaryparent');
                if ($useprimary) { //grab the primary parent
                    $parent=$role->getPrimaryParent(); //string value
                } else { //we don't have a primary parent so use the first parent?? ... hmm review
                    foreach ($role->getParents() as $parent) {
                      $parent = $parent->name;
                        break;
                    }
                }
                $mask->setRealm($parent);
                break;
            case "none":
            default:
                $mask->setRealm('All');
                break;
        }

        // normalize the mask now - its properties won't change below
        $mask->normalize();

        // get the Roles class
        //if (!class_exists('xarRoles')) sys::import('modules.roles.xarclass.xarroles');
        $roles = xar::Roles();

        // get the uid of the role we will check against
        // an empty role means take the current user
        if ($rolename == '') {

            // $userID = xarSessionGetVar('uid');
            if (empty($userID)) {
                $userID = _XAR_ID_UNREGISTERED;
            }
            $role = $roles->getRole($userID);
        }
        else {
            $role = $roles->findRole($rolename);
        }

        // check if we already have the irreducible set of privileges for the current user
        if (!xarCoreCache::isCached('Security.Variables','privilegeset.'.$mask->module) || !empty($rolename)) {
            // get the privileges and test against them
            $privileges = $this->irreducibleset(array('roles' => array($role)),$mask->module);
            // leave this as same-page caching, even if the db cache is finished
            // if this is the current user, save the irreducible set of privileges to cache
            if ($rolename == '') {
                // normalize all privileges before saving, to avoid re-doing that every time
                $this->normalizeprivset($privileges);
                xarCoreCache::setCached('Security.Variables','privilegeset.'.$mask->module,$privileges);
            }
        } else {
            // get the irreducible set of privileges for the current user from cache
            $privileges = xarCoreCache::getCached('Security.Variables','privilegeset.'.$mask->module);
        }

        $pass = $this->testprivileges($mask,$privileges,FALSE,$role);
        //Can also catch at point of calling xarSecurityCheck and return xarResponseForbidden();
        //If exceptionredirect is active, a login form will be displayed to anon user
        if ($catch && !$pass) {
                $msg = xarML('No privilege for #(1)',$mask->getName());
                //jojo - we have to throw an exception here rather than return a xarResponseForbidden().
                throw new ForbiddenOperationException ($msg);
        }
        return $pass;

    }

/**
 * irreducibleset: assemble a role's irreducible set of privileges
 *
 * @access  public
 * @param   array representing the initial node to start from
 * @return  nested array containing the role's ancestors and privileges
 * @throws  none
 * @todo    none
*/
    function irreducibleset($coreset, $module='')
    {
        if (!empty($module)) {
            $module = strtolower($module);
        }

        $roles = $coreset['roles'];
        $coreset['privileges'] = array();
        $coreset['children'] = array();
        if (empty($roles)) return $coreset;

        $parents = array();
        foreach ($roles as $role) {
            // FIXME: evaluate why role is empty
            // Below (hack) fix added by Rabbitt (suggested by mikespub on the devel mailing list)
            if (empty($role)) continue;

            $privs = $role->getAssignedPrivileges();
            $descprivs = array();

            // We quickly merge descendant privs.
            foreach ($privs as $priv) {
                $descs = $priv->getDescendants();
                foreach($descs as $desc) {
                    $descprivs[] = $desc;
                }
            }
            if (!empty($descprivs)) {
                $privileges = $this->winnow($privs, $descprivs);
            } else {
                $privileges = $privs;
            }

            $privs = array();
            foreach ($privileges as $priv) {
                $privModule = strtolower($priv->module);
                if ($privModule === "all" || $privModule == $module) {
                    $privs[] = $priv;
                }
            }
            if (!empty($privs) !== 0) $coreset['privileges'] = $this->winnow($privs, array(), TRUE);

            $addParents = $role->getParents();
            $size = count($addParents);
            for ($i=0; $i !== $size; $i++) {
                $parents[] = $addParents[$i];
            }
        }
        if (empty($parents)) {
            $coreset['children'] = array('roles' => array(), 'privileges' => array(), 'children' => array());
        } else {
            $coreset['children'] = $this->irreducibleset(array('roles' => $parents), $module);
        }
        
        return $coreset;
    }

/**
 * normalizeprivset: apply the normalize() method on all privileges in a privilege set
 *
 * @access  public
 * @param   array representing the privilege set
 * @return  none
 * @throws  none
 * @todo    none
*/
    function normalizeprivset(&$privset)
    {
        if (!empty($privset['privileges'])) {
            $keys = array_keys($privset['privileges']);
            foreach ($keys as $id) {
                if (!empty($privset['privileges'][$id]->normalform)) $privset['privileges'][$id]->normalize();
            }
        }
        if (!empty($privset['children'])) {
            $this->normalizeprivset($privset['children']);
        }
    }

/**
 * testprivileges: test an irreducible set of privileges against a mask
 *
 * @access  public
 * @param   mask object
 * @param   nested array representing the irreducibles set of privileges
 * @param   boolean FALSE (initial test value)
 * @return  boolean FALSE if check fails, privilege object if check succeeds
 * @throws  none
 * @todo    none
*/
    function testprivileges($mask,$privilegeset,$pass,$role='')
    {
        static $tester =NULL;
        if(!isset($tester))
            $tester = xarModVars::get('privileges','tester');

        static $testvar = NULL;
        if(!isset($testvar))
            $testvar = xarModVars::get('privileges','test');

        static $testdenyvar = NULL;
        if(!isset($testdenyvar))
            $testdenyvar = xarModVars::get('privileges','testdeny');

        static $uid=NULL;
        if(!isset($uid))
            $uid = xarSessionGetVar('uid');
        $candebug = ($uid == $tester);


        $test     = $testvar && $candebug;
        $testdeny = $testdenyvar && $candebug;

        static $testmask = NULL;
        if(!isset($testmask))
            $testmask = xarModVars::get('privileges','testmask');

        $matched  = FALSE;
        $pass     = FALSE;
        // Note : DENY rules override all others here...
        $thistest = $testdeny && ($testmask == $mask->getName() || $testmask == "All");

        // Do this outside the loop and once
        static $inheritdeny = NULL;
        if(!isset($inheritdeny))
            $inheritdeny= xarModVars::get('privileges','inheritdeny');

        foreach ($privilegeset['privileges'] as $privilege) {
            if($thistest) {
                echo "Comparing <font color='blue'>[" . $privilege->present() . "]</font> against  <font color='green'>[". $mask->present() . "]</font> <b>for deny</b>. ";
                if (($privilege->level == 0) && ($privilege->includes($mask))) echo "<font color='blue'>[" . $privilege->getName() . "]</font> matches. ";
                else echo "no match found. ";
                /* debugging output */
                $msg = "Comparing for DENY.<font color='blue'>".$privilege->present(). "</blue>\n  ".
                    $mask->present();
                if (($privilege->level == 0) && ($privilege->includes($mask))) {
                    $msg .= $privilege->getName() . " FOUND. \n";
                } else {
                    $msg .= " NOT FOUND. \n";
                }
                xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
            }
            if ($privilege->level == 0 && $privilege->includes($mask)) {
                if (!$inheritdeny && is_object($role)) {
                    if($thistest) {
                        echo "We don't inherit <strong>denys</strong>, ";
                    }
                    $privs = $role->getAssignedPrivileges();
                    $isassigned = FALSE;
                    foreach ($privs as $priv) {
                        if ($privilege == $priv) {
                            if($thistest) {
                                echo "but <font color='blue'>[" . $privilege->present() . "] wins</font> because directly assigned. Continuing with other checks...<br />";
                            }
                            return FALSE;
                            break;
                        }
                    }
                    if($thistest) {
                        echo "and <font color='blue'>[" . $privilege->present() . "] wins</font> is not directly assigned. Ignoring..<br/>";
                    }
                } else {
                    if($thistest) {
                        echo "<font color='blue'>[" . $privilege->present() . "] wins</font>. Continuing with other checks...<br />";
                    }
                    return FALSE;
                }
            } else {
                if($thistest) {
                    echo "Continuing with other checks..<br />";
                }
            }
        }

        foreach ($privilegeset['privileges'] as $privilege) {
            if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                echo "Comparing <font color='blue'>[" . $privilege->present() . "]</font> and <font color='green'>[" . $mask->present() . "]</font>. ";
                $msg = "Comparing \n  Privilege: ".$privilege->present().
                    "\n       Mask: ".$mask->present();
                xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
            }
            if ($privilege->includes($mask)) {
                if ($privilege->implies($mask)) {
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='blue'>[" . $privilege->getName() . "] wins</font>. Privilege includes mask. Privilege level greater or equal. Continuing with other checks.. <br />";
                        $msg = $privilege->getName() . " WINS! ".
                            "Privilege includes mask. ".
                            "Privilege level greater or equal.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege->getLevel() > $pass->getLevel()) $pass = $privilege;
                }
                else {
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='green'>[" . $mask->getName() . "] wins</font>. Privilege includes mask. Privilege level lesser. Continuing with other checks..<br />";
                        $msg = $mask->getName() . " MATCHES! ".
                                "Privilege includes mask. Privilege level ".
                                "lesser.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                }
                $matched = TRUE;
            }
            elseif ($mask->includes($privilege)) {
                if ($privilege->level >= $mask->level) {
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='blue'>[" . $privilege->getName() . "] wins</font>. Mask includes privilege. Privilege level greater or equal. Continuing with other checks.. <br />";
                        $msg = $privilege->getName()." WINS! ".
                            "Mask includes privilege. Privilege level ".
                            "greater or equal.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                    if (!$pass || $privilege->getLevel() > $pass->getLevel()) $pass = $privilege;
                    $matched = TRUE;
                }
                else {
                    if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                        echo "<font color='blue'>[" . $mask->getName() . "] wins</font>. Mask includes privilege. Privilege level lesser. Continuing with other checks..<br />";
                        $msg = $mask->getName()." MATCHES! ".
                            "Mask includes privilege. Privilege level ".
                            "lesser.\n";
                        xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                    }
                }
            }
            else {
                if($test && ($testmask == $mask->getName() || $testmask == "All")) {
                    echo "<font color='red'>no match</font>. Continuing with other checks..<br />";
                    $msg = "NO MATCH.\n";
                    xarLogMessage($msg, XARLOG_LEVEL_DEBUG);
                }
            }
        }
        if (!$matched && ($privilegeset['children'] != array())) $pass = $this->testprivileges($mask,$privilegeset['children'],$pass,$role);
        return $pass;
    }

/**
 * getMask: gets a single mask
 *
 * Retrieves a single mask from the Masks repository
 *
 * @access  public
 * @param   string
 * @return  mask object
 * @throws  none
 * @todo    none
*/
    function getMask($name,$module="All",$component="All",$suppresscache=FALSE)
    {
        //could use getMasks instead of this function
        // check if we already have the definition of this mask
        if ($suppresscache || !xarCoreCache::isCached('Security.Masks',$name)) {
            $bindvars= array();
            $pargs = array();
            $where = 'xar_name = ?';
            $bindvars[] = $name;

            if (($module != 'All') && ($module != '')) {
                $where .= ' AND xar_module = ? ';
                $bindvars[] = $module;
            }
            if ($component != 'All') {
                $where .= ' AND xar_component = ? ';
                $bindvars[] = $component;
           }
            $query = "SELECT xar_sid, xar_name, xar_realm, xar_module, xar_component, xar_instance, xar_level, xar_description
                      FROM $this->maskstable
                      WHERE $where ";

            $result = $this->dbconn->Execute($query,$bindvars);
            if (!$result) return;
            list($sid, $name, $realm, $module, $component, $instance, $level, $description) = $result->fields;
                $pargs = array('sid' => $sid,
                               'name' => $name,
                              'realm' => $realm,
                               'module' => $module,
                              'component' => $component,
                              'instance' => $instance,
                              'level' => $level,
                              'description' => $description);

           /* $q = new xarQuery('SELECT',$this->maskstable);
            $q->addfields(array(
                            'xar_sid AS sid',
                            'xar_name AS name',
                            'xar_realm AS realm',
                            'xar_module AS module',
                            'xar_component AS component',
                            'xar_instance AS instance',
                            'xar_level AS level',
                            'xar_description AS description',
                        ));
            $q->eq('xar_name',$name);
            if ($module != "All") $q->eq('xar_module',strtolower($module));
            if ($component != "All") $q->eq('xar_component',strtolower($component));
            if (!$q->run()) return;
            if ($q->row() == array()) return FALSE;
            $pargs = $q->row();
            */
            xarCoreCache::setCached('Security.Masks',$name,$pargs);
        } else {
            $pargs = xarCoreCache::getCached('Security.Masks',$name);
        }
        return new xarMask($pargs);
    }
}


/**
 * xarPrivileges: class for the privileges repository
 *
 * Represents the repository containing all privileges
 * The constructor is the constructor of the parent object
 *
 * @package modules
 * @subpackage Privileges module

 * @access  public
 * @throws  none
 * @todo    none
*/

class xarPrivileges extends xarMasks
{

/**
 * defineInstance: define how a module's instances are registered
 *
 * Creates an entry in the instances table
 * This function should be invoked at module initialisation time
 *
 * @access  public
 * @param   array of values to register instance
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function defineInstance($module,$type,$instances,$propagate=0,$table2='',$childID='',$parentID='',$description='')
    {
        $base = xarServer::getBaseURL();
        foreach($instances as $instance) {
            // make privilege wizard URLs relative, for easier migration of sites
            if (!empty($instance['header']) && $instance['header'] == 'external' && !empty($instance['query'])) {
                $instance['query'] = str_replace($base,'',$instance['query']);
            }
            // Check if the instance already exists.
            // The instance is uniquely defined by its module, component and header.
            // FIXME: since the header is just a label, it probably should not be
            // treated as key information here. Do we need some further unique (within a
            // module and component) name for an instance, independant of the header label?
            $query = 'SELECT xar_iid FROM ' . $this->instancestable
                . ' WHERE xar_module = ? AND xar_component = ? AND xar_header = ?';
            $result = $this->dbconn->execute($query, array($module, $type, $instance['header']));
            if (!$result) return;
            if (!$result->EOF) {
                // Instance exists: update it.
                list($iid) = $result->fields;
                $query = 'UPDATE ' . $this->instancestable
                    . ' SET xar_query = ?, xar_limit = ?,'
                    . ' xar_propagate = ?, xar_instancetable2 = ?, xar_instancechildid = ?,'
                    . ' xar_instanceparentid = ?, xar_description = ?'
                    . ' WHERE xar_iid = ?';
                $bindvars = array(
                    $instance['query'], $instance['limit'],
                    $propagate, $table2, $childID, $parentID,
                    $description, $iid
                );
            } else {
            $query = "INSERT INTO $this->instancestable
                     ( xar_iid, xar_module, xar_component, xar_header,
                       xar_query, xar_limit, xar_propagate,
                       xar_instancetable2, xar_instancechildid,
                       xar_instanceparentid, xar_description)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)";
                    $bindvars = array(
                    $this->dbconn->genID($this->instancestable),
                              $module, $type, $instance['header'],
                              $instance['query'], $instance['limit'],
                              $propagate, $table2, $childID, $parentID,
                    $description
                );
            }

            if (!$this->dbconn->Execute($query,$bindvars)) return;
        }
        return TRUE;
    }

/**
 * removeInstances: remove the instances registered by a module form the database
 *
 * @access  public
 * @param   module name
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeInstances($module, $component='')
    {
        if (empty($component)) {
            $query = "DELETE FROM $this->instancestable WHERE xar_module = ?";
            $bindvars = array($module);
        } else {
            $query = "DELETE FROM $this->instancestable WHERE xar_module = ? and xar_component = ?";
             $bindvars = array($module,$component);
        }
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,$bindvars)) return;
        return TRUE;
    }

/**
 * register: register a privilege
 *
 * Creates an entry in the privileges table
 * This function should be invoked every time a new instance is created
 *
 * @access  public
 * @param   array of privilege values
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function register($name,$realm,$module,$component,$instance,$level,$description='')
    {
        $query = "INSERT INTO $this->privilegestable (
                    xar_pid, xar_name, xar_realm, xar_module, xar_component,
                    xar_instance, xar_level, xar_description)
                  VALUES (?,?,?,?,?,?,?,?)";
        $bindvars = array($this->dbconn->genID($this->privilegestable),
                          $name, $realm, $module, $component,
                          $instance, $level, $description);

        if (!$this->dbconn->Execute($query,$bindvars)) return;
        return TRUE;
    }

/**
 * assign: assign a privilege to a user/group
 *
 * Creates an entry in the acl table
 * This is a convenience function that can be used by module developers
 * Note the input params are strings to make it easier.
 *
 * @access  public
 * @param   string
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function assign($privilegename,$rolename)
    {

// get the ID of the privilege to be assigned
        $privilege = $this->findPrivilege($privilegename);
        $privid = $privilege->getID();

// get the Roles class
        $roles = new xarRoles();

// find the role for the assignation and get its ID
        $role = $roles->findRole($rolename);
        $roleid = $role->getID();

// Add the assignation as an entry to the acl table
        $query = "INSERT INTO $this->acltable VALUES (?,?)";
        $bindvars = array($roleid,$privid);
        if (!$this->dbconn->Execute($query,$bindvars)) return;

// empty the privset cache
//        $this->forgetprivsets();

        return TRUE;
    }

/**
 * getprivileges: returns all the current privileges.
 *
 * Returns an array of all the privileges in the privileges repository
 * The repository contains an entry for each privilege.
 * This function will initially load the privileges from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @access  public
 * @param   none
 * @return  array of privileges
 * @throws  none
 * @todo    none
*/
    function getprivileges()
    {
        static $allprivileges = array();

        if (empty($allprivileges)) {
            xarLogMessage('PRIV: getting all privs, once!');
            $query = "SELECT p.xar_pid, p.xar_name, p.xar_realm,
                             p.xar_module, p.xar_component, p.xar_instance,
                             p.xar_level,  p.xar_description, pm.xar_parentid
                      FROM $this->privilegestable p, $this->privmemberstable pm
                      WHERE p.xar_pid = pm.xar_pid
                      ORDER BY p.xar_name";
            // The fetchmode *needed* to be here, dunno why. Exception otherwise
            //$result = $stmt->executeQuery($query,ResultSet::FETCHMODE_NUM);
            $result = $this->dbconn->Execute($query);
            if (!$result) return;

            $allprivileges = array();
            while(!$result->EOF) {
                list($pid, $name, $realm, $module, $component, $instance, $level,
                        $description,$parentid) = $result->fields;
                $allprivileges[] = array('pid' => $pid,
                                   'name' => $name,
                                   'realm' => $realm,
                                   'module' => $module,
                                   'component' => $component,
                                   'instance' => $instance,
                                   'level' => $level,
                                   'description' => $description,
                                   'parentid' => $parentid);
            $result->MoveNext();
            }
        }
        return $allprivileges;

    }

/**
 * gettoplevelprivileges: returns all the current privileges that have no parent.
 *
 * Returns an array of all the privileges in the privileges repository
 * that are top level entries, i.e. have no parent
 * This function will initially load the privileges from the db into an array and return it.
 * On subsequent calls it just returns the array .
 *
 * @access  public
 * @param   string $arg indicates what types of elements to get
 * @return  array of privileges
 * @throws  none
 * @todo    none
*/
    function gettoplevelprivileges($arg)
    {
//    if ((!isset($alltoplevelprivileges)) || count($alltoplevelprivileges)==0) {
        $bindvars = array();
        if($arg == "all") {
             $fromclause = "FROM $this->privilegestable p,$this->privmemberstable pm
                        WHERE p.xar_pid = pm.xar_pid
                        AND pm.xar_parentid = ?
                        ORDER BY p.xar_name";
                        $bindvars[]=0;
        } elseif ($arg == "assigned"){
             $fromclause = "FROM $this->privilegestable p,$this->privmemberstable pm,
                            $this->acltable acl
                            WHERE
                            p.xar_pid = pm.xar_pid
                            AND p.xar_pid = acl.xar_permid
                            AND pm.xar_parentid = ?
                            ORDER BY p.xar_name";
                             $bindvars[]=0;

        } elseif ($arg == "unassigned"){
                //we could do a more complex select here (all privs are in the privmembers table, even if they have no parent)
                //easiest is to use group by and HAVING but this probably not compatable across dbs
                //select here and then interate to remove those with parents and duplicates that also have no parent
                $fromclause = "FROM $this->privilegestable p
                            JOIN $this->privmemberstable pm on p.xar_pid = pm.xar_pid
                            LEFT JOIN $this->acltable acl ON pm.xar_pid = acl.xar_permid
                            WHERE p.xar_pid=pm.xar_pid AND acl.xar_permid IS NULL
                                  ORDER BY p.xar_name";
        }
        $query = "SELECT DISTINCT
                    p.xar_pid,
                    p.xar_name,
                    p.xar_realm,
                    p.xar_module,
                    p.xar_component,
                    p.xar_instance,
                    p.xar_level,
                    p.xar_description,
                    pm.xar_parentid ";
        $query .= $fromclause;
        $result = $this->dbconn->Execute($query,$bindvars);
        if (!$result) return;

        $privileges = array();
        $pids = array();
        while(!$result->EOF) {
            list(
                $pid, $name, $realm, $module, $component, $instance, $level,
                    $description,$parentid) = $result->fields;
            $thisone = $pid;
           // if (!in_array($thisone,$pids)){
           //     $pids[] = $thisone;
                $privileges[] = array(
                                      'pid' => $pid,
                                      'name' => $name,
                                      'realm' => $realm,
                                      'module' => $module,
                                      'component' => $component,
                                      'instance' => $instance,
                                      'level' => $level,
                                      'description' => $description,
                                      'parentid' => $parentid);
           // }
            $result->MoveNext();
        }
        $alltoplevelprivileges = $privileges;
        //If we can do a reasonable query that is known to be cross db compatible in select above for 'unassigned'
        //then this can be removed
        //alternatively we remove all privs without parents from the privmembers table to simplify
        if ($arg == 'unassigned') {
            $hasparent = array();
            $noparents = array();
            foreach ($privileges as $priv) {
                if ($priv['parentid'] !=0) {
                    $hasparent[]=$priv['pid'];
                } else {
                    $noparents[] = $priv;
                }
            }
            $hasparent = array_unique($hasparent);
            foreach ($noparents as $k=> $p) {
                $test = in_array($p['pid'],$hasparent);
                if ($test) unset($noparents[$k]);
            }
            $privileges = $noparents;
        }
        return $privileges;
    }

/**
 * getrealms: returns all the current realms.
 *
 * Returns an array of all the realms in the realms table
 * They are used to populate dropdowns in displays
 *
 * @access  public
 * @param   none
 * @return  array of realm ids and names
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getrealms()
    {
        static $allreams = array(); // Get them once

        if (empty($allrealms)) {
            $query = "SELECT xar_rid, xar_name FROM $this->realmstable";
            $result = $this->dbconn->Execute($query);
            if (!$result) return;

            // add some extra lines we want
            // $allrealms[] = array('rid' => -2,'name' => ' ');
            $allrealms[] = array('rid' => -1,'name' => 'All');
            // $allrealms[] = array('rid' => 0, 'name' => 'None');

            // add the realms from the database
            while(!$result->EOF) {
                list($rid, $name) = $result->fields;
                $allrealms[] = array('rid' => $rid,'name' => $name);
                $result->MoveNext();
            }
        }
        return $allrealms;
    }

/**
 * getmodules: returns all the current modules.
 *
 * Returns an array of all the modules in the modules table
 * They are used to populate dropdowns in displays
 *
 * @access  public
 * @param   none
 * @return  array of module ids and names
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getmodules()
    {
        if (empty($allmodules)) {
            $query = "SELECT xar_id,
                        xar_name,
                        xar_state
                        FROM $this->modulestable
                        WHERE xar_state = 3
                        ORDER BY xar_name";

            $result = $this->dbconn->Execute($query);
            if (!$result) return;

// add some extra lines we want
            $modules = array();
//          $modules[] = array('id' => -2,
//                             'name' => ' ');
            $modules[] = array('id' => -1,
                               'name' => 'All',
                               'display' => 'All');
//          $modules[] = array('id' => 0,
//                             'name' => 'None');

// add the modules from the database
// TODO: maybe remove the key, don't really need it
            while(!$result->EOF) {
                list($mid, $name) = $result->fields;
                $modules[] = array('id' => $mid,
                                   'name' => $name,
                                   'display' => xarMod::getDisplayableName($name)
                                   //'display' => ucfirst($name)
                                   );
                $result->MoveNext();
            }
            $allmodules = $modules;
            return $modules;
        }
        else {
            return $allmodules;
        }
    }

/**
 * getcomponents: returns all the current components of a module.
 *
 * Returns an array of all the components that have been registered for a given module.
 * The components correspond to masks in the masks table. Each one can be used to
 * construct a privilege's xarSecurityCheck.
 * They are used to populate dropdowns in displays
 *
 * @access  public
 * @param   string with module name
 * @return  array of component ids and names
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getcomponents($module)
    {
        $query = "SELECT DISTINCT xar_component
                    FROM $this->instancestable
                    WHERE xar_module= ?
                    ORDER BY xar_component";

        $result = $this->dbconn->Execute($query,array($module));
        if (!$result) return;

        $components = array();
        if ($module ==''){
            $components[] = array('id' => -2,
                               'name' => 'All');
        }
        elseif(count($result->fields) == 0) {
            $components[] = array('id' => -1,
                               'name' => 'All');
//          $components[] = array('id' => 0,
//                             'name' => 'None');
        }
        else {
            $components[] = array('id' => -1,
                               'name' => 'All');
//          $components[] = array('id' => 0,
//                             'name' => 'None');
            $ind = 2;
            while(!$result->EOF) {
                list($name) = $result->fields;
                if (($name != 'All') && ($name != 'None')){
                    $ind = $ind + 1;
                    $components[] = array('id' => $name,
                                       'name' => $name);
                }
                $result->MoveNext();
            }
        }
        return $components;
    }

/**
 * getinstances: returns all the current instances of a module.
 *
 * Returns an array of all the instances that have been defined for a given module.
 * The instances for each module are registered at initialization.
 * They are used to populate dropdowns in displays
 *
 * @access  public
 * @param   string with module name
 * @return  array of instance ids and names for the module
 * @throws  none
 * @todo    this isn't really the right place for this function
*/
    function getinstances($module, $component)
    {

        if ($component =="All") {
            $componentstring = "";
        }
        else {
            $componentstring = "AND ";
        }
        $query = "SELECT xar_header, xar_query, xar_limit
                    FROM $this->instancestable
                    WHERE xar_module= ? AND xar_component= ?
                     ORDER BY xar_component,xar_iid";
        $bindvars = array($module,$component);

        $instances = array();
        $result = $this->dbconn->Execute($query,$bindvars);
        if (!$result) return;

        while(!$result->EOF) {
            list($header,$selection,$limit) = $result->fields;

// Check if an external instance wizard is requested, if so redirect using the URL in the 'query' part
// This is indicated by the keyword 'external' in the 'header' of the instance definition
            if ($header == 'external') {
                return array('external' => 'yes',
                             'target'   => $selection);
            }

// check if the query is there
            if ($selection =='') {
                $msg = xarML('A query is missing in component #(1) of module #(2)', $component, $module);

                throw new BadParameterException(NULL,$msg);
            }

            $result1 = $this->dbconn->Execute($selection);
            if (!$result1) return;

            $dropdown = array();
            if ($module ==''){
                $dropdown[] = array('id' => -2,
                                   'title' => '',
                                   'name' => '');
            } elseif($result->EOF) {
                $dropdown[] = array('id' => -1,
                                   'title' => 'All',
                                   'name' => 'All');
    //          $dropdown[] = array('id' => 0,
    //                             'name' => 'None');
            }
            else {
                $dropdown[] = array('id' => -1,
                                   'title' => 'All',
                                   'name' => 'All');
    //          $dropdown[] = array('id' => 0,
    //                             'name' => 'None');
            }
            //Can have memory probs here if we have many instances
            //let's check the limit first
            $rowcount = $result1->_numOfRows;
            if ($rowcount <= $limit) {
                while(!$result1->EOF) {
                    // Use titles from the optional second column of instances query
                    if ($result1->FieldCount() == 1) {
                        list($dropdownline) = $result1->fields;
                        $dropdowntitle = '';
                    } else {
                        $dropdownline  = $result1->fields[0];
                        $dropdowntitle  = $result1->fields[1];
                    }
                    if (($dropdownline != 'All') && ($dropdownline != 'None')){
                        $dropdown[] = array('id' => $dropdownline,
                                           'title' => $dropdownline . ' ' . $dropdowntitle,
                                           'name' => $dropdownline);
                    }
                    $result1->MoveNext();
                }

                $instances[] = array('header' => $header,
                                'dropdown' => $dropdown,
                                'type' => 'dropdown'
                                );

            } else {
                 $instances[] = array('header' => $header,
                                'dropdown' => $dropdown,
                                'type' => 'manual'
                                );
            }
             $result->MoveNext();
        }
        return $instances;
    }

    function getprivilegefast($pid)
    {
        $privs = $this->getprivileges();
        foreach($privs as $privilege){
            if ($privilege['pid'] == $pid) return $privilege;
        }
        return FALSE;
    }

    function getChildren($pid)
    {
        $subprivileges = array();
        $ind = 1;
        $privs = $this->getprivileges();
        foreach($privs as $subprivilege){
            if ($subprivilege['parentid'] == $pid) {
                $subprivileges[$ind++] = $subprivilege;
            }
        }
        return $subprivileges;
    }

/**
 * returnPrivilege: adds or modifies a privilege coming from an external wizard .
 *
 *
 * @access  public
 * @param   strings with pid, name, realm, module, componentand level
 * @param   array instances
 * @param   int parentid optional ID of parent privilege
 * @return  mixed pid if OK, void if not
*/
    function returnPrivilege($pid,$name,$realm,$module,$component,$instances,$level,$parentid=0)
    {

        $instance = "";
        foreach ($instances as $inst) {
            $instance .= $inst . ":";
        }
        if ($instance =="") {
            $instance = "All";
        }
        else {
            $instance = substr($instance,0,strlen($instance)-1);
        }

        if($pid==0) {
            $pargs = array('name' => $name,
                        'realm' => $realm,
                        'module' => $module,
                        'component' => $component,
                        'instance' => $instance,
                        'level' => $level,
                        'parentid' => $parentid,
                        );
            $priv = new xarPrivilege($pargs);
            if ($priv->add()) {
                return $priv->getID();
            }
            return;
        }
        else {
            $privs = new xarPrivileges();
            $priv = $privs->getPrivilege($pid);
            $priv->setName($name);
            $priv->setRealm($realm);
            $priv->setModule($module);
            $priv->setComponent($component);
            $priv->setInstance($instance);
            $priv->setLevel($level);
            if ($priv->update()) {
                return $priv->getID();
            }
            return;
        }
    }

/**
 * getPrivilege: gets a single privilege
 *
 * Retrieves a single privilege object from the Privileges repository
 *
 * @access  public
 * @param   integer
 * @return  privilege object
 * @throws  none
 * @todo    none
*/
    function getPrivilege($pid)
    {
        static $stmt = NULL;  // Statement only needs to be prepared once.

        $cacheKey = 'Privilege.ByPid';
        if (xarCoreCache::isCached($cacheKey,$pid)) {
            return xarCoreCache::getCached($cacheKey,$pid);
        }
        // Need to get it
        $query = "SELECT * FROM $this->privilegestable WHERE xar_pid = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($pid));
        if (!$result) return;
        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('pid'=>$pid,
                           'name'=>$name,
                           'realm'=>$realm,
                           'module'=>$module,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);

            $priv = new xarPrivilege($pargs);
            xarCoreCache::setCached($cacheKey,$pid,$priv);
            return $priv;
        } else {
            return NULL;
        }
    }

/**
 * findPrivilege: finds a single privilege based on its name
 *
 * Retrieves a single privilege object from the Privileges repository
 * This is a convenience class for module developers
 *
 * @access  public
 * @param   string
 * @return  privilege object
 * @throws  none
 * @todo    none
*/
    function findPrivilege($name)
    {
        $query = "SELECT * FROM $this->privilegestable WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($name));
        if (!$result) return;
        if (!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('pid'=>$pid,
                           'name'=>$name,
                           'realm'=>$realm,
                           'module'=>$module,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);
            return new xarPrivilege($pargs);
        }
        return;
    }

/**
 * findPrivilegesForModule: finds the privileges assigned to a module
 *
 * Retrieves an of privilege objects from the Privileges repository
 * This is a convenience class for module developers
 *
 * @author  Richard Cave<rcave@xaraya.com>
 * @access  public
 * @param   string
 * @return  privilege object
 * @throws  none
 * @todo    none
*/
    function findPrivilegesForModule($module)
    {
        $privileges = array();
        $query = "SELECT * FROM $this->privilegestable WHERE xar_module = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($module));
        if (!$result) return;
        for (; !$result->EOF; $result->MoveNext()) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
            $pargs = array('pid'=>$pid,
                           'name'=>$name,
                           'realm'=>$realm,
                           'module'=>$module,
                           'component'=>$component,
                           'instance'=>$instance,
                           'level'=>$level,
                           'description'=>$description,
                           'parentid'=>0);
            $privileges[] = new xarPrivilege($pargs);
        }
        // Close result set
        $result->Close();
        return $privileges;
    }

/**
 * makeMember: makes a privilege a child of another privilege
 *
 * Creates an entry in the privmembers table
 * This is a convenience class for module developers
 *
 * @access  public
 * @param   string
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    create exceptions for bad input
*/
    function makeMember($childname,$parentname)
    {
// get the data for the parent object
        $query = "SELECT *
                  FROM $this->privilegestable WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($parentname));
        if (!$result) return;

// create the parent object
        list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
        $pargs = array('pid'=>$pid,
                        'name'=>$name,
                        'realm'=>$realm,
                        'module'=>$module,
                        'component'=>$component,
                        'instance'=>$instance,
                        'level'=>$level,
                        'description'=>$description,
                        'parentid'=>0);
        $parent =  new xarPrivilege($pargs);

// get the data for the child object
        $query = "SELECT * FROM $this->privilegestable WHERE xar_name = ?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($childname));
        if (!$result) return;

// create the child object
        list($pid,$name,$realm,$module,$component,$instance,$level,$description) = $result->fields;
        $pargs = array('pid'=>$pid,
                        'name'=>$name,
                        'realm'=>$realm,
                        'module'=>$module,
                        'component'=>$component,
                        'instance'=>$instance,
                        'level'=>$level,
                        'description'=>$description,
                        'parentid'=>0);
        $child =  new xarPrivilege($pargs);

// done
        return $parent->addMember($child);
    }

/**
 * makeEntry: defines a top level entry of the privileges hierarchy
 *
 * Creates an entry in the privmembers table
 * This is a convenience class for module developers
 *
 *
 * @access  public
 * @param   string
 * @return  boolean
 * @throws  none
 * @todo    create exceptions for bad input
*/
    function makeEntry($rootname)
    {
        $priv = $this->findPrivilege($rootname);
        $priv->makeEntry();
        return TRUE;
    }

}

/**
 * xarMask: class for the mask object
 *
 * Represents a single security mask
 *
 * @package modules
 * @subpackage Privileges module
 *
 * @access  public
 * @throws  none
 * @todo    none
*/
class xarMask
{
    public $sid = -1;           //the id of this privilege
    public $name = '';          //the name of this privilege
    public $realm = '';         //the realm of this privilege
    public $module = '';        //the module of this privilege
    public $component = '';     //the component of this privilege
    public $instance = '';      //the instance of this privilege
    public $level = 0;         //the access level of this privilege
    public $description = '';   //the long description of this privilege
    public $normalform = array();    //the normalized form of this privilege
    public $sign = '';     // md5 signature to compare

    public $dbconn;
    public $privilegestable;
    public $privmemberstable;

/**
 * xarMask: constructor for the class
 *
 * Creates a security mask
 *
 * @access  public
 * @param   array of values
 * @return  mask
 * @throws  none
 * @todo    none
*/

    function __construct($pargs)
    {
        extract($pargs);

        $this->dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $this->privilegestable = $xartable['privileges'];
        $this->privmemberstable = $xartable['privmembers'];
        $this->rolestable = $xartable['roles'];
        $this->acltable = $xartable['security_acl'];

        $this->sid          = (int) $sid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module       = $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = (int) $level;
        $this->description  = $description;
    }

    function present()
    {
        $display  = "-" . $this->getLevel();
        $display .= ":" . strtolower($this->getRealm());
        $display .= ":" . strtolower($this->getModule());
        $display .= ":" . strtolower($this->getComponent());
        $display .= ":" . strtolower($this->getInstance());
        return $this->getName() . strtolower($display);
    }

/**
 * normalize: creates a "normalized" array representing a mask
 *
 * Returns an array of strings representing a mask
 * The array can be used for comparisons with other masks
 * The function optionally adds "all"'s to the end of a normalized mask representation
 *
 * @access  public
 * @param   integer   adds  Number of additional instance parts to add to the array
 * @return  array of strings
 * @throws  none
 * @todo    none
*/
    function normalize($adds=0)
    {
        static $uid = NULL;
        if (!isset($uid)) $uid = xarSession::getVar('uid');

        if (!empty($this->normalform)) {
            if ($adds === 0) return $this->normalform;
            $normalform = $this->normalform;
        } else {
            $normalform = array($this->level, $this->realm, $this->module, $this->component);
             
            if ($this->instance !== 'All') { 
                $instanceArr = explode(':', $this->instance);
                foreach ($instanceArr as $element) {
                    if ($element === 'myself') {
                        $normalform[] = $uid;
                    } else {
                        $normalform[] = $element;
                    }
                }
            } else {
                $normalform[] = $this->instance;
            }
            // instead of calling 5 times or more strtolower, use serialization.
            $serialized = serialize($normalform);
            $serializednocaps = strtolower($serialized);
            if ($serialized !== $serializednocaps) $normalform = unserialize($serializednocaps);
        }

        for ($i=0;$i<$adds;$i++) {
            $normalform[] = 'all';
        }
        
        if ($adds === 0) {
            if (!isset($serialized)) $serialized = serialize($normalform);
            $this->normalform = $normalform;
            $this->sign = md5($serialized);
        }
        return $normalform;
    }

/**
 * canonical: returns 2 normalized privileges or masks as arrays for comparison
 *
 * @access  public
 * @param   mask object
 * @return  array 2 normalized masks
 * @throws  none
 * @todo    none
*/
    function canonical($mask)
    {
        $p1 = $this->normalize();
        $p2 = $mask->normalize();

        return array($p1,$p2);
    }

/**
 * matches: checks the structure of one privilege against another
 *
 * Checks whether two privileges, or a privilege and a mask, are equal
 * in all respects except for the access level
 *
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function matches($mask)
    {
        //list($p1,$p2) = $this->canonical($mask);
        // should be faster to include this directly:
        $p1 = $this->normalize();
        $p2 = $mask->normalize();
        //
        $match = TRUE;
        $p1count = count($p1);
        $p2count = count($p2);
        if ($p1count !== $p2count) return FALSE;
        for ($i=1; $i < $p1count; $i++) {
            $match = $match && ($p1[$i]===$p2[$i]);
        }
//        echo $this->present() . $mask->present() . $match;exit;
        return $match;
    }

/**
 * matchesexactly: checks the structure of one privilege against another
 *
 * Checks whether two privileges, or a privilege and a mask, are equal
 * in all respects
 *
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function matchesexactly($mask)
    {
        $p1 = $this->normalize();
        $p2 = $mask->normalize();
        $match = TRUE;
        $p1count = count($p1);
        $p2count = count($p2);
        if ($p1count !== $p2count) return FALSE;
        for ($i=1; $i < $p1count; $i++) {
            $match = $match && ($p1[$i]===$p2[$i]);
        }
        // inline code should be faster
        //$match = $this->matches($mask);
        return $match && ($this->level === $mask->level);
    }

/**
 * includes: checks the structure of one privilege against another
 *
 * Checks a mask has the same or larger range than another mask
 *
 *
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function includes($mask)
    {
        //static $realmcomparison = NULL;
        //if(!isset($realmcomparison))
        //    $realmcomparison = xarModVars::get('privileges','realmcomparison');
        // Lakys: this modvar changes nothing below. Code commented out as it is useless

        if (!empty($this->normalform)) {
            $p1 = $this->normalform;
        } else {
            $p1 = $this->normalize();
        }
        if (!empty($mask->normalform)) {
            $p2 = $mask->normalform;
        } else {
            $p2 = $mask->normalize();
        }
        
        // match realm. bail if no match.
        /*switch($realmcomparison) {
            case "contains":*/
                $fails = $p1[1] !== $p2[1];
            /*case "exact":
            default:
                $fails = $p1[1] !== $p2[1];
                break;
        }*/
        if (($p1[1] !== 'all') && $fails) return FALSE;

        // match module and component. bail if no match.
        for ($i=2;$i<4;$i++) {
            if (($p1[$i] !== 'all') && ($p1[$i] !== $p2[$i])) {
                return FALSE;
            }
        }

        // now match the instances
        $p1count = count($p1);
        $p2count = count($p2);
        if($p1count !== $p2count) {
            if($p1count > $p2count) {
                $p = $p2;
                $p2 = $mask->normalize($p1count - $p2count);
            } else {
                $p = $p1;
                $p1 = $this->normalize($p2count - $p1count);
            }
            if (count($p) !== 5) {
                $msg = xarML('#(1) and #(2) do not have the same instances. #(3) | #(4) | #(5)',$mask->getName(),$this->getName(),implode(',',$p2),implode(',',$p1),$this->present() . "|" . $mask->present());
                throw new BadParameterException(NULL,$msg);
            }
        }
        for ( $i = 4, $p1count = count($p1); $i < $p1count; $i++) {
            if (($p1[$i] !== 'all') && ($p1[$i] !== $p2[$i])) {
                return FALSE;
            }
        }
        return TRUE;
    }

/**
 * implies: checks the structure of one privilege against another
 *
 * Checks a mask has the same or larger range, and the same or higher access right,
 * than another mask
 *
 * @access  public
 * @param   mask object
 * @return  boolean
 * @throws  none
 * @todo    none
*/

    function implies($mask)
    {
        $match = $this->includes($mask);
        return $match && ($this->getLevel() >= $mask->getLevel()) && ($mask->getLevel() > 0);
    }

    function getID()
    {
        return $this->sid;
    }

    function getName()
    {
        return $this->name;
    }

    function getRealm()
    {
        return $this->realm;
    }

    function getModule()
    {
        return $this->module;
    }

    function getComponent()
    {
        return $this->component;
    }

    function getInstance()
    {
        return $this->instance;
    }

    function getLevel()
    {
        return $this->level;
    }

    function getDescription()
    {
        return $this->description;
    }

    function setName($var)
    {
        $this->name = $var;
    }

    function setRealm($var)
    {
        $this->realm = $var;
    }

    function setModule($var)
    {
        $this->module = $var;
    }

    function setComponent($var)
    {
        $this->component = $var;
    }

    function setInstance($var)
    {
        $this->instance = $var;
    }

    function setLevel($var)
    {
        $this->level = $var;
    }

    function setDescription($var)
    {
        $this->description = $var;
    }
}


/**
 * xarPrivilege: class for the privileges object
 *
 * Represents a single privileges object
 *
 * @package modules
 * @subpackage Privileges module

 * @access  public
 * @throws  none
 * @todo    none
*/

class xarPrivilege extends xarMask
{

    public $pid;           //the id of this privilege
    public $name;          //the name of this privilege
    public $realm;         //the realm of this privilege
    public $module;        //the module of this privilege
    public $component;     //the component of this privilege
    public $instance;      //the instance of this privilege
    public $level;         //the access level of this privilege
    public $description;   //the long description of this privilege
    public $parentid;      //the pid of the parent of this privilege

    public $dbconn;
    public $privilegestable;
    public $privmemberstable;

/**
 * xarPrivilege: constructor for the class
 *
 * Just sets up the db connection and initializes some variables
 *
 * @access  public
 * @param   array of values
 * @return  the privilege object
 * @throws  none
 * @todo    none
*/
    function __construct($pargs)
    {
        extract($pargs);

        $this->dbconn = xarDB::$dbconn;
        $xartable = &xarDB::$tables;
        $prefix = xarDB::$prefix;
        $this->privilegestable = $prefix.'_privileges';
        $this->privmemberstable =  $prefix.'_privmembers';
        $this->rolestable =  $prefix.'_roles';
        $this->acltable =  $prefix.'_security_acl';

// CHECKME: pid and description are undefined when adding a new privilege
        if (empty($pid)) {
            $pid = 0;
        }
        if (empty($description)) {
            $description = '';
        }

        $this->pid          = (int) $pid;
        $this->name         = $name;
        $this->realm        = $realm;
        $this->module       = $module;
        $this->component    = $component;
        $this->instance     = $instance;
        $this->level        = (int) $level;
        $this->description  = $description;
        $this->parentid     = (int) $parentid;
    }

/**
 * add: add a new privileges object to the repository
 *
 * Creates an entry in the repository for a privileges object that has been created
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
   function add()
   {
        if(empty($this->name)) {
            $msg = xarML('No Name was provided for the privilege. You must enter a Name for the new privilege.');

            //throw new BadParameterException(NULL,$msg);
            xarTpl::setMessage($msg, 'error');
            return FALSE;
        }

// Confirm that this privilege name does not already exist
        $query = "SELECT COUNT(*) FROM $this->privilegestable
              WHERE xar_name = ?";

        $result = $this->dbconn->Execute($query,array($this->name));
        if (!$result) return;

        list($count) = $result->fields;

        if ($count == 1) {
            $msg = xarML('A privilege with that Name already exists. Please choose another name.');
            //throw new BadParameterException(NULL,$msg);
            xarTpl::setMessage($msg, 'error');
            return FALSE;
        }

// create the insert query
        $query = "INSERT INTO $this->privilegestable
                    (xar_pid, xar_name, xar_realm, xar_module, xar_component, xar_instance, xar_level, xar_description)
                  VALUES (?,?,?,?,?,?,?,?)";
        $bindvars = array($this->dbconn->genID($this->privilegestable),
                          $this->name, $this->realm, $this->module,
                          $this->component, $this->instance, $this->level, $this->description);
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,$bindvars)) return;

// the insert created a new index value
// retrieve the value
        $query = "SELECT MAX(xar_pid) FROM $this->privilegestable";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query);
        if (!$result) return;

// use the index to get the privileges object created from the repository
        list($pid) = $result->fields;
        $this->pid = $pid;

// make this privilege a child of its parent
        If($this->parentid !=0) {
            $perms = new xarPrivileges();
            $parentperm = $perms->getprivilege($this->parentid);
            $parentperm->addMember($this);
        }
// create this privilege as an entry in the repository
        return $this->makeEntry();
    }

/**
 * makeEntry: sets up a privilege without parents
 *
 * Sets up a privilege as a root entry (no parent)
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    check to make sure the child is not a parent of the parent
*/
    function makeEntry()
    {
        if ($this->isRootPrivilege()) return TRUE;
        $query = "INSERT INTO $this->privmemberstable VALUES (?,0)";
        if (!$this->dbconn->Execute($query,array($this->getID()))) return;
        return TRUE;
    }

/**
 * addMember: adds a privilege to a privilege
 *
 * Make a privilege a member of another privilege.
 * A privilege can have any number of parents or children..
 *
 * @access  public
 * @param   privilege object
 * @return  boolean
 * @throws  none
 * @todo    check to make sure the child is not a parent of the parent
*/
    function addMember($member)
    {
        $query = "INSERT INTO $this->privmemberstable VALUES (?,?)";
        $bindvars = array($member->getID(), $this->getID());
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,$bindvars)) return;

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return TRUE;
    }

/**
 * removeMember: removes a privilege from a privilege
 *
 * Removes a privilege as an entry of another privilege.
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeMember($member)
    {

        $q = new xarQuery('SELECT', $this->privmemberstable, 'COUNT(*) AS count');
        $q->eq('xar_pid', $member->getID());
        if (!$q->run()) return;
        $total = $q->row();
        if($total['count'] == 0) return TRUE;

        if($total['count'] > 1) {
            $q = new xarQuery('DELETE');
            $q->eq('xar_parentid', $this->getID());
        } else {
            $q = new xarQuery('UPDATE');
            $q->addfield('xar_parentid', 0);
        }
        $q->addtable($this->privmemberstable);
        $q->eq('xar_pid', $member->getID());
        if (!$q->run()) return;

// empty the privset cache
//        $privileges = new xarPrivileges();
//        $privileges->forgetprivsets();

        return TRUE;
    }

/**
 * update: updates a privilege in the repository
 *
 * Updates a privilege in the privileges repository
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function update()
    {
        $query =    "UPDATE " . $this->privilegestable .
                    " SET xar_name = ?, xar_realm = ?,
                          xar_module = ?, xar_component = ?,
                          xar_instance = ?, xar_level = ?, xar_description = ?
                      WHERE xar_pid = ?";
        $bindvars = array($this->name, $this->realm, $this->module,
                          $this->component, $this->instance, $this->level, $this->description,
                          $this->getID());
        //Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,$bindvars)) return;
        return TRUE;
    }

/**
 * remove: deletes a privilege in the repository
 *
 * Deletes a privilege's entry in the privileges repository
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function remove()
    {

// set up the DELETE query
        $query = "DELETE FROM $this->privilegestable WHERE xar_pid=?";
//Execute the query, bail if an exception was thrown
        if (!$this->dbconn->Execute($query,array($this->pid))) return;

// set up a query to get all the parents of this child
        $query = "SELECT xar_parentid FROM $this->privmemberstable
              WHERE xar_pid=?";
        //Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($this->getID()));
        if (!$result) return;

// remove this child from all the parents
        $perms = new xarPrivileges();
        while(!$result->EOF) {
            list($parentid) = $result->fields;
            if ($parentid != 0) {
                $parentperm = $perms->getPrivilege($parentid);
                $parentperm->removeMember($this);
            }
            $result->MoveNext();
        }

// remove this child from the root privilege too
        $query = "DELETE FROM $this->privmemberstable WHERE xar_pid=? AND xar_parentid=0";
        if (!$this->dbconn->Execute($query,array($this->pid))) return;

// get all the roles this privilege was assigned to
        $roles = $this->getRoles();
// remove the role assignments for this privilege
        foreach ($roles as $role) {
            $this->removeRole($role);
        }

// get all the child privileges
        $children = $this->getChildren();
// remove the child privileges from this parent
        foreach ($children as $childperm) {
            $this->removeMember($childperm);
        }

// CHECKME: re-assign all child privileges to the roles that the parent was assigned to ?

        return TRUE;
    }

/**
 * isassigned: check if the current privilege is assigned to a role
 *
 * This function looks at the acl table and returns TRUE if the current privilege.
 * is assigned to a given role .
 *
 * @access  public
 * @param   role object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isassigned($role)
    {
        $query = "SELECT xar_partid FROM $this->acltable WHERE
                xar_partid = ? AND xar_permid = ?";
        $bindvars = array($role->getID(), $this->getID());
        $result = $this->dbconn->Execute($query,$bindvars);
        if (!$result) return;
        return !$result->EOF;
    }

/**
 * getRoles: returns an array of roles
 *
 * Returns an array of roles this privilege is assigned to
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function getRoles()
    {
        static $loaded = FALSE;
        if (!$loaded) {
            if (!class_exists('xarRoles')) sys::import('modules.roles.xarclass.xarroles');
            $loaded = TRUE;
        }
// set up a query to select the roles this privilege
// is linked to in the acl table
        $query = "SELECT r.xar_uid,
                    r.xar_name,
                    r.xar_type,
                    r.xar_uname,
                    r.xar_email,
                    r.xar_pass,
                    r.xar_auth_module
                    FROM $this->rolestable r, $this->acltable acl
                    WHERE r.xar_uid = acl.xar_partid
                    AND acl.xar_permid = ?";
//Execute the query, bail if an exception was thrown
        $result = $this->dbconn->Execute($query,array($this->pid));
        if (!$result) return;

// make objects from the db entries retrieved
         
        $roles = array();
//      $ind = 0;
        while(!$result->EOF) {
            list($uid,$name,$type,$uname,$email,$pass,$auth_module) = $result->fields;
//          $ind = $ind + 1;
            $role = new xarRole(array('uid' => $uid,
                               'name' => $name,
                               'type' => $type,
                               'uname' => $uname,
                               'email' => $email,
                               'pass' => $pass,
                               'auth_module' => $auth_module,
                               'parentid' => 0));
            $result->MoveNext();
            $roles[] = $role;
        }
// done
        return $roles;
    }

/**
 * removeRole: removes a role
 *
 * Removes a role this privilege is assigned to
 *
 * @access  public
 * @param   role object
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function removeRole($role)
    {

// use the equivalent method from the roles object
        return $role->removePrivilege($this);
    }

/**
 * getParents: returns the parent objects of a privilege
 *
 *
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getParents()
    {
// create an array to hold the objects to be returned
        $parents = array();

// perform a SELECT on the privmembers table
        $query = "SELECT p.*, pm.xar_parentid
                    FROM $this->privilegestable p, $this->privmemberstable pm
                    WHERE p.xar_pid = pm.xar_parentid
                      AND pm.xar_pid = ?";
        $result = $this->dbconn->Execute($query,array($this->getID()));
        if (!$result) return;

// collect the table values and use them to create new role objects
        $ind = 0;
            while(!$result->EOF) {
            list($pid,$name,$realm,$module,$component,$instance,$level,$description,$parentid) = $result->fields;
            $pargs = array('pid'=>$pid,
                            'name'=>$name,
                            'realm'=>$realm,
                            'module'=>$module,
                            'component'=>$component,
                            'instance'=>$instance,
                            'level'=>$level,
                            'description'=>$description,
                            'parentid' => $parentid);
            $ind = $ind + 1;
            $parents[] = new xarPrivilege($pargs);
            $result->MoveNext();
            }
// done
        return $parents;
    }

/**
 * getAncestors: returns all objects in the privileges hierarchy above a privilege
 *
 * The returned privileges are automatically winnowed
 *
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getAncestors()
    {
// if this is the root return an empty array
        if ($this->getID() == 1) return array();

// start by getting an array of the parents
        $parents = $this->getParents();

//Get the parent field for each parent
        $masks = new xarMasks();
        while (list($key, $parent) = each($parents)) {
            $ancestors = $parent->getParents();
            foreach ($ancestors as $ancestor) {
                $parents[] = $ancestor;
            }
        }

//done
        $ancestors = array();
        $parents = $masks->winnow($ancestors,$parents);
        return $ancestors;
    }

/**
 * getChildren: returns the child objects of a privilege
 *
 *
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getChildren()
    {
        $cacheId = $this->pid;

        // we retrieve and cache everything at once now
        if (xarCoreCache::isCached('Privileges.getChildren', 'cached')) { // Lakys: this first test is essential. Removing it is breaking privs (anonymous gets admin access).
            if (xarCoreCache::isCached('Privileges.getChildren', $cacheId)) {
                return xarCoreCache::getCached('Privileges.getChildren', $cacheId);
            } else {
                return array();
            }
        }

        // create an array to hold the objects to be returned
        $children = array();

        // if this is a user just perform a SELECT on the rolemembers table
        $query = "SELECT p.*, pm.xar_parentid
                    FROM $this->privilegestable p, $this->privmemberstable pm
                    WHERE p.xar_pid = pm.xar_pid";
        // retrieve all children of everyone at once
        //              AND pm.xar_parentid = " . $cacheId;
// Can't use caching here. The privs have changed
//        if (xarSystemVars::get(sys::CONFIG, 'DB.UseADODBCache')){
//            $result = $this->dbconn->CacheExecute(3600,$query);
//            if (!$result) return;
//        } else {
            $result = $this->dbconn->Execute($query);
            if (!$result) return;
//        }

        // collect the table values and use them to create new role objects
        while(!$result->EOF) {
            // This should be faster than using new and calling the constructor
            $child = clone $this;
            list($child->pid, $child->name, $child->realm, $child->module, $child->component, $child->instance, $child->level, $child->description, $parentid) = $result->fields;
            $child->parentid = $parentid;
            $child->normalform = array();
            if (!isset($children[$parentid])) $children[$parentid] = array();
            $children[$parentid][] = $child;
            $result->MoveNext();
        }
        // done
        foreach (array_keys($children) as $parentid) {
            xarCoreCache::setCached('Privileges.getChildren', $parentid, $children[$parentid]);
        }
        xarCoreCache::setCached('Privileges.getChildren', 'cached', 1);
        if (isset($children[$cacheId])) {
            return $children[$cacheId];
        } else {
            return array();
        }
    }

/**
 * getDescendants: returns all objects in the privileges hierarchy below a privilege
 *
 * The returned privileges are automatically winnowed
 *
 * @access  public
 * @param   none
 * @return  array of privilege objects
 * @throws  none
 * @todo    none
*/
    function getDescendants()
    {
        $cacheId = $this->pid;
        $cacheKey = 'Privileges.getDescendants';

        // we retrieve and cache everything at once now
        if (xarCoreCache::isCached($cacheKey, $cacheId)) {
            return xarCoreCache::getCached($cacheKey, $cacheId);
        }

        // start by getting an array of the parents
        if (!xarCoreCache::isCached('Privileges.getChildren', 'cached')) {
            $children = $this->getChildren();
        } else {
            $children = xarCoreCache::getCached('Privileges.getChildren', $cacheId);
            if (empty($children)) $children = array();
        }


        if (!empty($children)) {
            //Get the child field for each child
            while (list($key, $child) = each($children)) {
                $descendants = xarCoreCache::getCached('Privileges.getChildren', $child->pid);
                if (!empty($descendants)) {
                    foreach ($descendants as $descendant) {
                        $children[] = $descendant;
                    }
                }
            }
        }
            
        if (count($children) > 1) {
            $descendants = xar::Masks()->winnow($children, array(), TRUE);
        } else {
           $descendants = &$children;
        }

        // caching
        xarCoreCache::setCached($cacheKey, $cacheId, $descendants);

        return $descendants;
    }

/**
 * isEqual: checks whether two privileges are equal
 *
 * Two privilege objects are considered equal if they have the same pid.
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isEqual($privilege)
    {
        return $this->getID() == $privilege->getID();
    }

/**
 * getID: returns the ID of this privilege
 *
 * This overrides the method of the same name in the parent class
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function getID()
    {
        return $this->pid;
    }

/**
 * isEmpty: returns the type of this privilege
 *
 * This methods returns TRUE if the privilege is an empty container
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isEmpty()
    {
        return $this->module == 'empty';
    }

/**
 * isParentPrivilege: checks whether a given privilege is a parent of this privilege
 *
 * This methods returns TRUE if the privilege is a parent of this one
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isParentPrivilege($privilege)
    {
        $privs = $this->getParents();
        foreach ($privs as $priv) {
            // Lakys: let's make this a bit faster by preventing another method call for a single line.
            // if ($privilege->isEqual($priv)) return TRUE;
            if ($this->getID() === $privilege->getID()) return TRUE;
        }
        return FALSE;
    }
/**
 * isRootPrivilege: checks whether this privilege is root privilege
 *
 * This methods returns TRUE if this privilege is a root privilege
 *
 * @access  public
 * @param   none
 * @return  boolean
 * @throws  none
 * @todo    none
*/
    function isRootPrivilege()
    {
        $q = new xarQuery('SELECT');
        $q->addtable($this->privilegestable,'p');
        $q->addtable($this->privmemberstable,'pm');
        $q->join('p.xar_pid','pm.xar_pid');
        $q->eq('pm.xar_pid',$this->getID());
        $q->eq('pm.xar_parentid',0);
        if(!$q->run()) return;
        return ($q->output() != array());
    }
}

?>