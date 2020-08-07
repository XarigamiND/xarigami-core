<?php
/**
 * Theme handling functions
 *
 * @package Xarigami core
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 *
*/
/**
 * Wrapper functions to support Xarigami 1 API
 **/
sys::import('xarigami.variables.theme');
function xarThemeGetVar($themeName, $name, $transform = NULL, $throw = FALSE)      {   return xarThemeVars::get($themeName, $name, $transform, $throw); }
function xarThemeGetVarDisplayed($themeName, $name) 
{
    $result = xarThemeVars::get($themeName, $name, TRUE, FALSE);
    if (empty($result)) return ''; // This covers also the empty array
    if (is_string($result) || is_numeric($result)) return $result;
    if (is_array($result)) {
        $first = reset($result);
        if (is_string($first) || is_numeric($first)) return $first;
    }
    if (is_object($result) && method_exists($result, 'toString')) return $result->toString();
    return (string) $result; // Should we not thow an exception here!
}
function xarThemeSetVar($themeName, $name, $prime = NULL, $value, $description='') {   return xarThemeVars::set($themeName, $name, $prime, $value, $description); }
function xarThemeDelVar($themeName, $name)                                         {   return xarThemeVars::delete($themeName, $name); }

/**
 * Get configuration for a theme variables
 *
 * @access public
 * @param string themename The name of the theme
 * @param string varname The name of the variable (optional)
 * @param string configtype Values are 'database' or 'file' configuration (optional, default 'database');
 * @return array theme => var name, var description, value, configuration
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeGetConfig($args)
{
    extract($args);

    if (!isset($configtype) || empty($configtype) ) $configtype = 'database';
    if (!isset($varname)) $varname = '';
    if(!isset($transform)) $transform = 0;
    if (!isset($themename) || empty($themename)) {
        $themename = xarTpl::getThemeName();
    }

    $themevars = array();

    //checked for cached var
    if (isset($transform) && $transform ==1) {
        $key= 'Theme.Variables.Output.'.$themename;
    } else {
        $key =  'Theme.Variables.'.$themename;
    }
    if (xarCoreCache::isCached($key,$varname)) {
        return xarCoreCache::getCached($key,$varname);
    }
    $throw = isset($throw)?$throw: FALSE;
    //database configs
    if ($configtype == 'database') {
        $dbconn = xarDB::$dbconn;
        $tables = &xarDB::$tables;
        $bindvars = array();

        $themevarstable = $tables['theme_vars'];
        $bindvars = array();
        $bindvars[] = $themename;
        $where = 'xar_themeName = ?';
        if (isset($varname) && !empty($varname)) {
            $bindvars[] = $varname;
            $where .= ' AND xar_name = ?';
        }
        $query = "SELECT  xar_id, xar_name, xar_value, xar_description, xar_config, xar_prime
                  FROM $themevarstable
                  WHERE $where";
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;

        while (!$result->EOF) {
            list($id, $name, $value, $description, $config, $prime) = $result->fields;
            if (!empty($config) && !is_array($config) && substr($config, 0,2) == 'a:') {
                $config = @unserialize($config);
            } elseif (empty($config)) {
                $config = array();
            }

            if (!isset($config['propertyname']) || empty($config) || (is_string($config) && (substr($config,0,2) != 'a:')))
            {
                xarThemeDelVar($themename,$name); //legacy var, delete it from the database else it will cause problems
                if ( $throw === TRUE) {
                    $msg = xarML('THEME CONFIG - Deleting bad theme variable "#(1)" in the database for theme "#(2)"',$name,$themename);

                    throw new VariableNotFoundException($name,$msg);
                } else {
                    $msg = xarML('THEME CONFIGS: Bad theme variable "#(1)" in the database for theme #(2)',$name,$themename);
                    xarLogMessage($msg);
                    xarTpl::setMessage($msg,'error');
                    return;
                }
            }
            //set some defaults
            $config['propargs'] = isset($config['propargs'])?$config['propargs']:array();

            $themevars[$name] = array('id'=>$id,
                                      'name' => $name,
                                      'value' => $value,
                                      'prime'   => $prime,
                                      'description' => $description,
                                      'config'=> $config
                                      );

            $result->MoveNext();
        }

        if (!empty($varname) && is_array($themevars) && isset($themevars[$varname]))  {
            $tempvar            = $themevars[$varname];
            $propargs           = $tempvar['config']['propargs'];
            $tempvar['name']    = $varname;
            $tempvar['value']  = $tempvar['value'];
            $tempvar['themename'] = $themename;
            $tempvar['config']['type']     =  isset($tempvar['config']['type'])?$tempvar['config']['type']:2; //textbox
            $tempvar['config']['varcat']     =  isset($tempvar['config']['varcat'])?$tempvar['config']['varcat']:xarML('miscellaneous');
            $tempvar['config']['proptype']  = $tempvar['config']['type']; // needed for validations
            $tempvar['config']['default']  = isset($tempvar['config']['default'])?$tempvar['config']['default']:'';
            $tempvar['config']['label'] = isset($tempvar['config']['label'])?$tempvar['config']['label']:$tempvar['name'];
            $tempvar['config']['propertyname'] = isset($tempvar['config']['propertyname'])?$tempvar['config']['propertyname']:'textbox';
            $tempvar['config']['status']     =  isset($tempvar['config']['status'])?$tempvar['config']['status']:0; //inactive
            $tempvar['config']['validation'] = $propargs;
             $tempvar['config']['value'] = $tempvar['value'];
            if ($transform == 1) {
               $themevars = xarMod::apiFunc('dynamicdata','user','showoutput', $tempvar['config']);
               unset( $tempvar['config']['validation'] ); //we don't need this in other areas
               xarCoreCache::setCached('Theme.Variables.Output' . $themename, $name, $themevars);
            } else {
                $themevars = $tempvar; //consistent return instead of indexed var
            }
        }
        $result->Close();
    } elseif ($configtype == 'file') { //we want filevars
        $sitethemedir = xarConfigGetVar('Site.BL.ThemesDirectory');
        $themedir = xarThemeGetDirFromName($themename);
        $xarinitfilename = $sitethemedir.'/'. $themedir .'/xartheme.php';
        if (!file_exists($xarinitfilename) && $throw === TRUE) {
           throw new FileNotFoundException($xarinitfilename);
        }
        try {
            include $xarinitfilename;
        } catch (Exception $e) {
            if ( $throw === TRUE) {
              throw new FileNotFoundException($xarinitfilename);
            } else {
                $msg = xarML('Could not find the xartheme.php file for theme #(1)',$themename);
                xarTpl::setMessage($msg,'error');
               return;
            }
        }
        //could be old format but we want associative array - let's ensure array is correct format
        $tempvars = array();
        foreach ($themevars as $var => $varinfo) {
            if (!isset($var) || empty($var) || !is_string($var) || !isset($varinfo['config']))
            {
                $name = isset($varinfo['name'])? $varinfo['name']:'unknown';

                if ( $throw === TRUE)
                {
                    throw new VariableNotFoundException($name);
                } else {

                $msg = xarML('Malformed theme variable named "#(1)" in xartheme.php file for "#(2)". Please remove it.', $name, $themename);
                xarTpl::setMessage($msg,'alert');
                     xarLogMessage('THEME CONFIGS: '.$msg);
                    return;
                }
            }

            $name        = isset($varinfo['name']) ?$varinfo['name']: $var;
            $value       = isset($varinfo['value'])? $varinfo['value']: '';

            $description = isset($varinfo['description']) ? $varinfo['description']: '';
            $config      = isset($varinfo['config'])?$varinfo['config']:array();

            $prime       =1; //this is a theme author defined theme var - not GUI defined custom var
            $config['default']  = isset($config['default'])? $config['default']: $value;
            $config['type']     = isset($config['type'])?$config['type'] : 2;//textbox
            $config['status']     = isset($config['status'])?$config['status'] : 0;
            $config['label']    = isset($config['label'])? $config['label']: $name;
            $config['propargs'] = isset($config['propargs'])? $config['propargs'] : array();
            $tempvars[$name] = array('name' => $name, 'value' => $value, 'description' => $description, 'prime'=>1, 'config'=> $config);

        }

        if (isset($varname) && !empty($varname)) {
            //only return the value of the specific var
            $themevars = $tempvars[$varname];
        }
    }

    return $themevars;
}
/**
 * Set configuration for a theme variable
 *
 * @access public
 * @param string themename The name of the theme
 * @param string varname The name of the variable
 * @param string desc The description of the variable (optional)
 * @param string value The value of the variable (optional)
 * @param string config  array of configs
 * @return array theme => var name, var description, value, configuration, prime
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeSetConfig ($args)
{
    extract($args);

    if (!isset($themename) || empty($themename)) {
         throw new EmptyParameterException('themename');
    }
    if (!isset($varname) || empty($varname)) {
         throw new EmptyParameterException('varname');
    }

    //See if it exists - we can get the existing value or description if it's not provided
    $themevar = xarThemeGetConfig(array('themename'=>$themename,'varname'=>$varname));

    //make sure existing  required vars are either passed in or from the db
    //passed in
    $config = isset($config) && is_array($config) ? $config : array();
    $config['propargs'] = isset($config['propargs'] )  && is_array($config['propargs'])?  $config['propargs']  : array();
    $config['status']   = isset($config['status']) ?$config['status']: 0; //inactive = 0

    if (!isset($description)) $description = '';

    if (isset($desc) && !empty($desc) && empty($description)) $description = $desc; //support for both desc and description
    //from db
    $themevar['config']['propargs'] = isset($themevar['config']['propargs']) && is_array($themevar['config']['propargs']) ?$themevar['config']['propargs']: array();
    $themevar['config']['status'] = isset($themevar['config']['status']) ?$themevar['config']['status']: 0;

    if (!sys::isInstall()) {
        //set our format for the prop type - always recheck
       $proptypes = xarMod::apiFunc('dynamicdata','user','getproptypes');
       foreach($proptypes as $proptypeid=>$propinfo) {

            if (isset($config['propertyname']) && ($propinfo['name'] == $config['propertyname'])) {
                $config['type'] = $propinfo['format'];
                break;
            }
        }
    }

    if (isset( $themevar['id']) && is_array($themevar) && !empty($themevar) ) {
        //theme var exists, we're updating so check optionals - take the new one passed in if available
        $description  = isset($description)?$description: $themevar['description'];
        if (isset($args['value']) && array_key_exists('value',$args) ) { //check the array key exists
             $value = $args['value'];
        } else {
              $value = $themevar['value'];
        }
        $id     = $themevar['id'];
        $config['propargs'] =  is_array($config['propargs']) ? array_merge($themevar['config']['propargs'],$config['propargs']):$themevar['config']['propargs'];
        $config = is_array($config) ? array_merge($themevar['config'],$config):$themevar['config'];
        $prime  = isset($prime)?$prime :$themevar['prime']; //might be zero
    }

    $prime = isset($prime)?$prime:'0';
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    $themevarstable = $tables['theme_vars'];
    $bindvars = array();

    //make sure config is a serialized array
    if (isset($config) && is_array($config)) $config = serialize($config);

    if (isset( $themevar['id'] )){ //theme var exists, update it
        $query = "UPDATE $themevarstable
                  SET xar_value = ?, xar_description = ?, xar_config = ?, xar_prime = ?
                  WHERE xar_id = ?";
             $bindvars = array($value, $description, $config, $prime, $id);
    } else {
        //insert it new
         $seqId = $dbconn->GenId($themevarstable);
        $query = "INSERT INTO $themevarstable
                     (xar_id, xar_themeName, xar_name, xar_prime, xar_value, xar_description, xar_config)
                  VALUES (?,?,?,?,?,?,?)";
        $bindvars = array($seqId, $themename,$varname,(int)$prime, $value, $description, $config);
    }

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
    $result->Close();
    //make sure we update the cache as the value could have changed
     xarCoreCache::setCached('Theme.Variables.' . $themename, $varname, $value);
     //we also have the DD output cached value
     if (isset($config['propargs']) && is_array($config['propargs'])) {
        $varoutput = xarMod::apiFunc('dynamicdata','user','showoutput',$config);
        xarCoreCache::setCached('Theme.Variables.Output'. $themename, $varname,$varoutput);
    }

    return TRUE;
}
/**
 * Delete and reset a theme var or selection of theme vars for a given theme
 *
 * @access public
 * @param string themename The name of the theme
 * @param string varname The name of the variable
 * @param string prime  if set only prime vars are deleted
 * @return boolean true
 */
function xarThemeDelConfig($args)
{
     extract($args);
    if (!isset($themename) || empty($themename)) {
         throw new EmptyParameterException('themename');
    }
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    $themevarstable = $tables['theme_vars'];

    $bindvars = array();

    $query = "DELETE FROM  $themevarstable
               WHERE xar_themeName = ? AND xar_prime = ?";
    $bindvars = array($themename,1);
    if (isset($varname) && !empty($varname)) {
        $query .= " AND xar_name = ?";
        $bindvars[] = $varname;
    }

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    $result->Close();
    //now make sure we delete the variable from the the cache or flush it totally if we delete or system vars (no option for just system vars)
    if (!isset($varname)) {
        xarCoreCache::flushCached('Theme.Variables.'.$themename);
        xarCoreCache::flushCached('Theme.Variables.Output',$themename);
    } else {
         xarCoreCache::delCached('Theme.Variables.'.$themename, $varname);
        xarCoreCache::delCached('Theme.Variables.Output',$themename, $varname);
    }
    return TRUE;
}


/**
 * Gets theme registry ID given its name
 *
 * @access public
 * @param string themeName The name of the theme
 * @return xarModGetIDFromName for processing
 * @throws DATABASE_ERROR, BAD_PARAM, THEME_NOT_EXIST
 */
function xarThemeGetIDFromName($themeName, $id='regid')
{
    if (empty($themeName)) throw new EmptyParameterException('themeName');
      $themeBaseInfo = xarMod::getBaseInfo($themeName, 'theme');
        return $themeBaseInfo[$id];
}
function xarThemeGetDirFromName($themeName)
{
    return xarMod::getDirFromName($themeName, $type = 'theme');
}

/**
 * get information on theme
 *
 * @access public
 * @param int themeRegId theme id
 * @return array array of theme information
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarThemeGetInfo($regId)
{
    $themeinfo = xarMod::GetInfo($regId, $type = 'theme');
    return $themeinfo;
}


/**
 * load database definition for a theme
 *
 * @param string themeName name of theme to load database definition for
 * @param string themeDir directory that theme is in (if known)
 * @return xarModDBInfoLoad for processing.
 */
function xarThemeDBInfoLoad($themeName, $themeDir = NULL)
{
    return xarMod::loadDbInfo($themeName, $themeDir, $type = 'theme');
}


/**
 * Gets the displayable name for the passed themeName.
 * The displayble name is sensible to user language.
 *
 * @access public
 * @param themeName registered name of theme
 * @return string the displayable name
 */
function xarThemeGetDisplayableName($themeName)
{
    // The theme display name is language sensitive,
    // so it's fetched through xarML.
    // jojo - Removing the xarML for now and fetching actual display name
    $themeBaseInfo =xarMod::getBaseInfo($themeName, $type = 'theme');
    $themeFileInfo = xarMod::getFileInfo($themeBaseInfo['directory'], $type = 'theme');
    return  $themeFileInfo['displayname'];
}

/**
 * checks if a theme is installed and its state is XARTHEME_STATE_ACTIVE
 *
 * @access public
 * @param string themeName registered name of theme
 * @return bool true if the theme is available, false if not
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarThemeIsAvailable($themeName)
{
    return xarMod::isAvailable($themeName, $type = 'theme');
}


// PROTECTED FUNCTIONS

/**
 * Get info from xartheme.php
 *
 * @access protected
 * @param string themeOSdir the theme's directory
 * @return xarMod_getFileInfo for processing
 */
function xarTheme_getFileInfo($themeOsDir)
{
    return xarMod::getFileInfo($themeOsDir, $type = 'theme');
}

/**
 * Load a theme's base information
 *
 * @access protected
 * @param string themeName the theme's name
 * @return to xarMod__getBaseInfo for processing
 */
function xarTheme_getBaseInfo($themeName)
{
    return xarMod::getBaseInfo($themeName, $type = 'theme');
}

/**
 * Get all theme variables for a particular theme
 *
 * @access protected
 * @return array an array of theme variables
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarTheme_getVarsByTheme($themeName)
{

    return xarMod::getVarsByModule($themeName, $type = 'theme');
}

/**
 * Get all theme variables with a particular name
 *
 * @access protected
 * @return bool true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarTheme_getVarsByName($name)
{
    return xarMod::getVarsByName($name, $type = 'theme');
}

/**
 * Get the theme's current state
 *
 * @param int themeRegId the theme's registered id
 * @param string themeMode the theme's site mode
 * @return to xarMod__getState for processing
 */
function xarTheme_getState($themeRegId, $themeMode)
{
    return xarMod::getState($themeRegId, $themeMode, $type = 'theme');
}
?>
