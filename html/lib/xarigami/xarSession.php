<?php
/**
 * Session Support
 *
 * @package core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Michel Dalle
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

/**
 * Session exception class
 *
 */
class SessionException extends Exception
{}

/**
 * Initialise the Session Support
 *
 * @return bool true
 */
function xarSession_init($args)
{
    $GLOBALS['xarSession_systemArgs'] = $args;

    // Register the SessionCreate event
    xarEvents::register('SessionCreate');

    // Register tables this subsystem uses
    $tables = array('session_info' => xarDB::$prefix . '_session_info');
    xarDB::importTables($tables);

    //DEPRECATED 5.3 REMOVE AT PHP6.0
    if (version_compare(PHP_VERSION,'5.3.0','<')) {
        if (ini_get('register_globals')) {
            // First thing we do is ensure that there is no attempted pollution
            // of the session namespace (yes, we still need this in this case)
            foreach($GLOBALS as $k=>$v) {
                if (substr($k,0,5) == 'XARSV') {
                    xarCore_die('xarSession_init: Session Support initialisation failed.');
                }
            }
        }
    }
   // Set up the session object
    $session = new xarSession($args);

    // Start the session, this will call xarSession:read, and
    // it will tell us if we need to start a new session or just
    // to continue the current session
    $session->start();
    $sessionId = $session->id();

    // Get  client IP addr, so we can register or continue a session
    $ipAddress =  xarSession::getIPAddress();

    // If it's new, register it, otherwise use the existing.
    if ($session->isNew()) {
        if($session->register($ipAddress)) {
            // Congratulations. We have created a new session
            xarEvents::trigger('SessionCreate');
        } else {
            // Registering failed, now what?
        }
    } else {
        // Not all ISPs have a fixed IP or a reliable X_FORWARDED_FOR
        // so we don't test for the IP-address session var
        $session->current();
    }
    return true;
}

/**
 * Get the configured security level
 *
 */
function xarSessionGetSecurityLevel()
{
    return $GLOBALS['xarSession_systemArgs']['securityLevel'];
}

/*
 * Session variables here are a bit 'different'.  Because they sit in the
 * global namespace we use a couple of helper functions to give them their
 * own prefix, and also to force users to set new values for them if they
 * require.  This avoids blatant or accidental over-writing of session
 * variables.
 *
 * The old interface as wrappers for the class methods are here, see xarSession class
 * for the implementation
 */
function xarSessionGetVar($name)
{ return xarSession::getVar($name); }
function xarSessionSetVar($name, $value)
{ return xarSession::setVar($name, $value); }
function xarSessionDelVar($name)
{ return xarSession::delVar($name); }
function xarSessionGetId()
{ return xarSession::getId(); }
function xarSessionGetIPAddress()
{ return xarSession::getIPAddress();}

// PROTECTED FUNCTIONS
/** mrb: if it's protected, how come roles uses it? */
function xarSession_setUserInfo($userId, $rememberSession)
{ return xarSession::setUserInfo($userId, $rememberSession); }

/**
 * Class to model the default session handler
 *
 *
 * @todo this is a temp, since the obvious plan is to have a factory here
 */
interface IsessionHandler
{
    public function register($ipAddress);
    public function start();
    public function id($id = null);
    public function isNew();
    public function current();

    public function open($path, $name);
    public function close();
    public function read($sessionId);
    public function write($sessionId, $vars);
    public function destroy($sessionId);
    public function gc($maxlifetime);
}

class xarSession extends xarObject implements IsessionHandler
{
    const  PREFIX='XARSV';    // Reserved by us for our session vars
    const  COOKIE='XARIGAMISID';// Our cookiename
    private $db;               // We store sessioninfo in the database
    private $tbl;              // Container for the session info
    private $isNew = true;     // Flag signalling if we're dealing with a new session

    private $sessionId = null; // The id assigned to us.
    private $ipAddress = '';   // IP-address belonging to this session.

    /**
     * Constructor for the session handler
     *
     * @return void
     * @throws SessionException
     **/
    function __construct(&$args)
    {
        // Set up our container.
        $this->db = xarDB::$dbconn;
        $tbls = &xarDB::$tables;
        $this->tbl = $tbls['session_info'];

        // Set up the environment
        $this->setup($args);

        // Assign the handlers
        session_set_save_handler(
          array(&$this,"open"),    array(&$this,"close"),
          array(&$this,"read"),    array(&$this,"write"),
          array(&$this,"destroy"), array(&$this,"gc")
        );

        //DEPRECATED 5.3 REMOVE AT PHP6.0
        if (version_compare(PHP_VERSION,'6.0.0','<')) {
            if (ini_get('register_globals')) {
                // First thing we do is ensure that there is no attempted pollution
                // of the session namespace (yes, we still need this in this case)
                foreach($GLOBALS as $k=>$v) {
                    if (substr($k,0,5) == 'XARSV') {
                        xarCore_die('xarSession_init: Session Support initialisation failed.');
                    }
                }
            }
        }
    }

    /**
     * Destructor for the session handler
     *
     * @return void
     **/
    function __destruct()
    {
        // Make sure we write dirty data before we lose this object
        session_write_close();
    }

    /**
     * Set all PHP options for Xaraya session handling
     *
     * @param $args['securityLevel'] the current security level
     * @param $args['duration'] duration of the session
     * @param $args['inactivityTimeout']
     * @return bool
     */
    private function setup(&$args)
    {
        //All in here is based on the possibility of changing
        //PHP's session related configuration
        if (!xarFuncIsDisabled('ini_set'))
        {
            // PHP configuration variables
            // Stop adding SID to URLs
            ini_set('session.use_trans_sid', 0);

            // How to store data
            ini_set('session.serialize_handler', 'php');

            // Use cookie to store the session ID
            ini_set('session.use_cookies', 1);

           // Name of our cookie
            if (empty($args['cookieName'])) $args['cookieName'] = self::COOKIE;
            ini_set('session.name', $args['cookieName']);

            if (empty($args['cookiePath'])) {
                $path = xarServer::getBaseURI();
                if (empty($path)) {
                    $path = '/';
                }
            } else {
                $path = $args['cookiePath'];
            }

            // Lifetime of our cookie
            switch ($args['securityLevel']) {
            case 'High':
                // Session lasts duration of browser
                $lifetime = 0;
                // Referer check defaults to the current host for security level High
                if (empty($args['refererCheck'])) {
                    $host = xarServer::getVar('HTTP_HOST');
                    $host = preg_replace('/:.*/', '', $host);
                    // this won't work for non-standard ports
                    //if (!xarFuncIsDisabled('ini_set')) ini_set('session.referer_check', "$host$path");
                    // this should be customized for multi-server setups wanting to
                    // share sessions
                    $args['refererCheck'] = $host;
                }
                break;
            case 'Medium':
                // Session lasts set number of days
                $lifetime = $args['duration'] * 86400;
                break;
            case 'Low':
                // Session lasts unlimited number of days (well, lots, anyway)
                // (Currently set to 25 years)
                $lifetime = 788940000;
                break;
            }
            ini_set('session.cookie_lifetime', $lifetime);

            // Referer check for the session cookie
            if (!empty($args['refererCheck'])) {
                ini_set('session.referer_check', $args['refererCheck']);
            }

            // Cookie path
            // this should be customized for multi-server setups wanting to share
            // sessions
            ini_set('session.cookie_path', $path);

            // Cookie domain
            // this is only necessary for sharing sessions across multiple servers,
            // and should be configurable for multi-site setups
            // Example: .Xaraya.com for all *.Xaraya.com servers
            // Example: www.Xaraya.com for www.Xaraya.com and *.www.Xaraya.com
            //$domain = xarServer::getVar('HTTP_HOST');
            //$domain = preg_replace('/:.*/', '', $domain);
            if (!empty($args['cookieDomain'])) {
                ini_set('session.cookie_domain', $args['cookieDomain']);
            }

            // Garbage collection
            ini_set('session.gc_probability', 1);

            // Inactivity timeout for user sessions
            ini_set('session.gc_maxlifetime', $args['inactivityTimeout'] * 60);

            // Auto-start session
            ini_set('session.auto_start', 1);
        }
        return true;
    }
   /**
     * Start the session
     *
     * This will call the handler, and it will tell us if
     * we need a new session or just continue the old one
     *
     */
    function start()
    {
        session_start();
    }

    /**
     * Set or get the session id
     *
     * @todo the static vs runtime method sucks, do we really need that?
     */
    function id($id= null)
    {
        $this->sessionId = $this->getId($id);
        return $this->sessionId;
    }

    static function getId($id = null)
    {
        if(isset($id))
            return session_id($id);
        else
            return session_id();
    }

    /**
     * Getter for new isNew
     *
     */
    function isNew()
    {
        return $this->isNew;
    }

    /**
     * Register a new session in our container
     *
     * @throws Exception
     */
    function register($ipAddress)
    {
        $dbconn = xarDB::$dbconn;

        $query = "INSERT INTO $this->tbl
                     (xar_sessid, xar_ipaddr, xar_uid, xar_firstused, xar_lastused)
                  VALUES (?,?,?,?,?)";
        $bindvars = array($this->sessionId, $ipAddress, _XAR_ID_UNREGISTERED, time(), time());
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;

        // Generate a random number, used for
        // some authentication
        srand((double) microtime() * 1000000);
        $this->setVar('rand', rand());

        $this->ipAddress = $ipAddress;

        return true;
    }
   /**
     * Continue an existing session
     *
     */
    function current()
    {  return true;
    }


    /**
     * PHP function to open the session
     * @access private
     */
    function open($path, $name)
    {   // Nothing to do - database opened elsewhere
        return true;
    }

    /**
     * PHP function to close the session
     * @access private
     */
    function close()
    {   // Nothing to do - database closed elsewhere
        return true;
    }

    /**
     * PHP function to read a set of session variables
     * @access private
     */
    function read($sessionId)
    {
        $dbconn = xarDB::$dbconn;

        $query = "SELECT xar_uid, xar_ipaddr, xar_lastused, xar_vars
                  FROM $this->tbl WHERE xar_sessid = ?";
        $result = $dbconn->Execute($query,array($sessionId));
        if (!$result) return;

        if (!$result->EOF) {
            $this->isNew = false;
            list($XARSVuid,  $this->ipAddress, $lastused, $vars) = $result->fields;
              $_SESSION[self::PREFIX.'uid']  = $XARSVuid;

            // in case garbage collection didn't have the opportunity to do its job
            if (!empty($GLOBALS['xarSession_systemArgs']['securityLevel']) &&
                $GLOBALS['xarSession_systemArgs']['securityLevel'] == 'High') {
                $timeoutSetting = time() - ($GLOBALS['xarSession_systemArgs']['inactivityTimeout'] * 60);
                if ($lastused < $timeoutSetting) {
                    // force a reset of the userid (but use the same sessionid)
                    $this->setUserInfo(_XAR_ID_UNREGISTERED, 0);
                    $this->ipAddress = '';
                    $vars = '';
                }
            }
        } else {
            $_SESSION[self::PREFIX.'uid'] = _XAR_ID_UNREGISTERED;
            $this->ipAddress = '';
            $vars = '';
        }
        $result->Close();

        return (string) $vars;
    }

    /**
     * PHP function to write a set of session variables
     *
     * @access private
     * @throws Exception
     */
    function write($sessionId, $vars)
    {
        $dbconn = xarDB::$dbconn;
        $dbtype = xarDB::getType();

        if (substr($dbtype,0,4) == 'oci8' || substr($dbtype,0,5) == 'mssql') {
            $query = "UPDATE $this->tbl SET xar_lastused = ? WHERE xar_sessid = ?";
            $result = $dbconn->Execute($query,array(time(), $sessionId));
            if (!$result) return;
            $id = $dbconn->qstr($sessionId);
            // Note: not sure why we use BLOB instead of TEXT (aka CLOB) for this field
            $result = $dbconn->UpdateBlob($sessioninfoTable, 'xar_vars', $vars, "xar_sessid = $id");
            if (!$result) return;
        } else {
            $query = "UPDATE $this->tbl SET xar_vars = ?, xar_lastused = ? WHERE xar_sessid = ?";
            $result = $dbconn->Execute($query,array($vars, time(), $sessionId));
            if (!$result) return;
        }

        return true;
    }
    /**
     * PHP function to destroy a session
     *
     * @access private
     * @throws SQL Exception
     */
    function destroy($sessionId)
    {
        $dbconn = xarDB::$dbconn;
        try {
            $query = "DELETE FROM $this->tbl WHERE xar_sessid = ?";
            $result = $dbconn->Execute($query,array($sessionId));
        } catch (Exception $e) {
             throw $e;
        }

        return true;
    }
    /**
     * PHP function to garbage collect session information
     *
     * @access private
     * @throws SQLException
     */
    function gc($maxlifetime)
    {
        $dbconn = xarDB::$dbconn;

        // Calculate the inactivity cutoff time (setting is in minutes, so multiply with 60)
        $timeoutSetting  = time() - ($GLOBALS['xarSession_systemArgs']['inactivityTimeout'] * 60);
        // Calculate the cookie expiration cutoff time (setting is in days, hence multiply with 60*60*24=86400)
        $cookieTimer   = time() - ($GLOBALS['xarSession_systemArgs']['duration'] * 86400);
        $bindvars = array();
        switch ($GLOBALS['xarSession_systemArgs']['securityLevel']) {
            case 'Low':
                // Low security: Delete the session data if
                //  * rememberme is OFF, that is, user did not explicitly say to keep data AND
                //  * the inactivity time expired
                $where = "WHERE xar_remembersess = ? AND xar_lastused < ?";
                $bindvars = array(0,$timeoutSetting);
                break;
            case 'Medium':
                // Medium security: Delete the session data if
                //  * rememberme is OFF, that is, user did not explicitly say to keep data AND
                //  * the inactivity time expired
                //  OR
                //  *  the cookie lifetime has expired
                $where = "WHERE (xar_remembersess = ? AND xar_lastused <  ?)  OR xar_firstused < ?";
                $bindvars = array(0, $timeoutSetting, $cookieTimer);
                break;
            case 'High':
            default:
                // High security: Delete session info if:
                //  * user is inactive, period
                $where = "WHERE xar_lastused < ?";
                $bindvars = array($timeoutSetting);
                break;
        }
        $query = "DELETE FROM $this->tbl $where";
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;

        return true;
    }
    /**
     * Get a session variable
     *
     * @param name name of the session variable to get
     */
    static function getVar($name)
    {
        $var = self::PREFIX . $name;

        if (isset($_SESSION[$var])) {
            return $_SESSION[$var];
        } elseif ($name == 'uid') {
            // mrb: why is this again?
            $_SESSION[$var] = _XAR_ID_UNREGISTERED;
            return $_SESSION[$var];
        }
    }

    /**
     * Set a session variable
     * @param name name of the session variable to set
     * @param value value to set the named session variable
     */
    static function setVar($name, $value)
    {
        assert('!is_null($value); /* Not allowed to set variable to NULL value */');
        // security checks : do not allow to set the uid or mess with the session serialization
        if ($name == 'uid' || strpos($name,'|') !== false) return false;

        $var = self::PREFIX . $name;
        $_SESSION[$var] = $value;
        return true;
    }

    /**
     * Delete a session variable
     * @param name name of the session variable to delete
     */
    static function delVar($name)
    {
        if ($name == 'uid') return false;

        $var = self::PREFIX . $name;

        if (!isset($_SESSION[$var])) {
            return false;
        }
        unset($_SESSION[$var]);

        //DEPRECATED 5.3 REMOVE AT PHP6.0
        //jojo - don't think this is needed at all now
        if (version_compare(PHP_VERSION,'6.0.0','<')) {
            if (ini_get('register_globals')) {
                session_unregister($var);
            }
        }

        return true;
    }
    /**
     * Set user info
     *
     * @throws SQLException
     * @todo only used in roles by the looks of it
     */
    static function setUserInfo($userId, $rememberSession)
    {
        $dbconn = xarDB::$dbconn;
        $tables = &xarDB::$tables;
        $session_table  = $tables['session_info'];
        try {
            $query = "UPDATE $session_table
                      SET xar_uid = ? ,xar_remembersess = ?
                      WHERE xar_sessid = ?";
            $bindvars = array($userId, $rememberSession, self::getId());
            $result = $dbconn->Execute($query,$bindvars);
        } catch (Exception $e) {
            throw $e;
        }

        $_SESSION[self::PREFIX.'uid'] = $userId;
        return true;
    }
    /**
     * Get the IP address for this host session
     * @return string ipAddress
     */
    static function getIPAddress()
    {
        $proxyip=''; //proxy ip if it exists
        $trueip=''; //true ip if it exists
        $isanip=0; //is this an ip

        $remote_addr = xarServer::getVar('REMOTE_ADDR');
        $x_forwarded_for= xarServer::getVar('HTTP_X_FORWARDED_FOR');
        $x_forwarded= xarServer::getVar('HTTP_X_FORWARDED');
        $forwarded_for= xarServer::getVar('HTTP_FORWARDED_FOR');
        $forwarded= xarServer::getVar('HTTP_FORWARDED');
        $x_comingfrom=xarServer::getVar('HTTP_X_COMING_FROM');
        $comingfrom=xarServer::getVar('HTTP_COMING_FROM');
        $httpvia=xarServer::getVar('HTTP_VIA');
        /* Gets the ip sent by the user */
        if (!empty($remote_addr )) {
            $trueip = $remote_addr;
        }

        /* Gets the proxy ip if it exists and  sent */

        if (!empty($x_forwarded_for)){
            $proxyip = $x_forwarded_for;
        } elseif (!empty($x_forwarded)) {
            $proxyip = $x_forwarded;
        } elseif (!empty($forwarded_for)) {
            $proxyip = $forwarded_for;
        } elseif (!empty($forwarded)) {
            $proxyip = $forwarded;
        }elseif (!empty($httpvia)) {
            $proxyip = $httpvia;
        } elseif (!empty($x_comingfrom)) {
            $proxyip = $x_comingfrom;
        } elseif (!empty($comingfrom)) {
            $proxyip = $comingfrom;
        }
        /* watch out for  more than one ... */
        $multi_proxyip = explode(";", $proxyip);
        $proxyip = $multi_proxyip[0]; //take the first

        if (empty($proxyip)) {
           $ipAddress= $trueip;
        } else {
            /* check the ip */
            $results=0;
            $isanip = filter_var($proxyip,FILTER_VALIDATE_IP); //this should filter for ipv4 and ipv6 and return false if not an ip
            if (false !== $isanip) {
                $ipAddress = $proxyip;
            } else {
                $ipAddress = '';
            }

            //pre php 5.2
            /*$isanip = preg_match('/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',$proxyip,$results);
                 if ($isanip && (count($results) > 0)) {
                     $ipAddress=$results[0];
                 } else {
                     // hmm not much we can do?
                     $ipAddress='';
                 }
            */
        }
        return $ipAddress;
    }
}
?>
