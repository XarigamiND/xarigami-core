<?php
/**
 * Update module information
 *
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami modules
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Update module information
 *
 * @author Xaraya Development Team
 * @param $args['regid'] the id number of the module to update
 * @param $args['displayname'] the new display name of the module
 * @param admincapable the whether the module shows an admin menu
 * @param usercapable the whether the module shows a user menu
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_updateproperties($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
         throw new BadParameterException(null,$msg);
    }

// Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;
  
    // Clear cache to make sure we get newest values
    if (xarCoreCache::isCached('Mod.Infos', $regid)) {
        xarCoreCache::delCached('Mod.Infos', $regid);
    }
    //Get module info
    $modInfo = xarMod::getInfo($regid);
    
  //Set up database object
    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
    $modulestable = $xartable['modules'];
    $setarray= array();
    $bindvars = array();
    if (isset($class)) {
        $setarray[]= ' xar_class = ?';
        $bindvars[] = $class;
        $modInfo['class'] = $class;
    }
    if (isset($category)) {
        $setarray[]= ' xar_category = ?';
        $bindvars[] = $category;
        $modInfo['category'] = $category;        
    }
    if (isset($admincapable)) {
        $setarray[]= ' xar_admin_capable = ?';  
        $bindvars[] = $admincapable;  
        $modInfo['admin_capable'] = $admincapable;           
    }
    if (isset($usercapable)) {
        $setarray[]= ' xar_user_capable = ?';  
        $bindvars[] = $usercapable;  
        $modInfo['user_capable'] = $usercapable;           
    }
    if (count($setarray) > 0) {
            $set = join(',',$setarray);
        } else {
            $set = '';
        }    
    $bindvars[] = $regid;

    $query = "UPDATE  $modulestable
              SET $set
              WHERE xar_regid = ?";

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) {return;}

    $result->Close();
    // We have updated the db now update the cache with new info

    xarCoreCache::setCached('Mod.Infos',$regid,$modInfo);

    $result->Close();
    return true;
}

?>