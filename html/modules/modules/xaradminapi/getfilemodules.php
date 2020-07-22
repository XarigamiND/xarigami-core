<?php
/**
 * Get module information from xarversion.php for each module
 *
 * @package modules
 * @copyright (C) 2005-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Get module information from xarversion.php for each module
 *
 * Here we cycle through the modules directory and and
 * return an array of information from xarversion.php of each module.
 *
 * Excluded directories:
 * MT  - this is a special directory of Monotone
 * _MTN - same as MT
 * CVS - this is a special directory of the Concurrent Versioning System
 * SCCS - where Bitkeeper stores source files
 * PENDING - where Bitkeeper stores pending merges
 *
 * @author Xaraya Development Team
 * @param $args['regid'] - optional regid to retrieve. When given, the function only returns the info for this module
 * @return array an array of modules from the file system with:
 * 'directory'
   'name'
   'nameinfile'
   'regid'
   'version'
   'mode'
   'class'
   'category'
   'admin_capable'
   'user_capable'
   'dependency'
   'extensions'
   'author'  
   'contact'     
   'license'     
   'official'    
   'credits'      
   'changelog'    
   'help'         
   'blversion'    
   'xarversion'   
   'publish_date'  
   '
 *
 */
function modules_adminapi_getfilemodules($args)
{
    // Get arguments
    extract($args);

    // Check for $regId
    $modregid = 0;
    if (isset($regId)) {
        $modregid = $regId;
    }

    $fileModules = array();
    $dh = opendir('modules');

    while ($modOsDir = readdir($dh)) {
        switch ($modOsDir) {
            // Exclude the obvious directories
            case '.':
            case '..':
            case 'MT':
            case '_MTN':
            case 'CVS':
            case 'SCCS':
            case 'PENDING':
            case 'notinstalled':
                break;
            default:
                if (is_dir("modules/$modOsDir")) {

                    // no xarversion.php, no module
                    $modFileInfo = xarMod::getFileInfo($modOsDir);
                    if (!isset($modFileInfo)) {
                        continue;
                    }

                    // Found a directory
                    //then let's do all vars 
                    $name          = $modOsDir;
                    $nameinfile    = $modFileInfo['name'];
                    $directoryinfile = $modFileInfo['directory'];
                    $regId         = $modFileInfo['id'];
                    $version       = $modFileInfo['version'];
                    $mode          = XARMOD_MODE_SHARED;
                    $class         = $modFileInfo['class'];
                    $category      = $modFileInfo['category'];
                    $adminCapable  = $modFileInfo['admin_capable'];
                    $userCapable   = $modFileInfo['user_capable'];
                    $dependency    = $modFileInfo['dependency'];
                    $extensions    = $modFileInfo['extensions'];
                    $author        = $modFileInfo['extensions'];
                    $contact       = $modFileInfo['contact'];
                    $license       = $modFileInfo['license'];
                    $official      = $modFileInfo['official'];
                    $credits       = $modFileInfo['credits'];
                    $changelog     = $modFileInfo['changelog'];
                    $help          = $modFileInfo['help'];
                    $blversion     = $modFileInfo['bl_version'];
                    $xarversion    = $modFileInfo['xar_version'];
                    $publish_date  = $modFileInfo['publish_date'];
                    $dependencyinfo = isset($modFileInfo['dependencyinfo'])?$modFileInfo['dependencyinfo']:$modFileInfo['dependency'];
                    //jojo - checking here - hope this is somehow handled later
                    if (!isset($regId)) {
                        xarSession::setVar('errormsg', "Module '$name' doesn't seem to have a registered module ID defined in xarversion.php - skipping...\nPlease register your module at http://www.xaraya.com");
                        continue;
                    }
                  
                    //Check for duplicates
                    foreach ($fileModules as $module) {
                        if($regId == $module['regid']) {
                            $msg = xarML('The same registered ID (#(1)) was found in two different modules, #(2) and #(3). Please remove one of the modules and regenerate the list.', $regId, $name, $module['name']);
                           throw new BadParameterException('regid',$msg);
                        }
                        if($nameinfile == $module['nameinfile']) {
                            $msg = xarML('The module #(1) was found under two different registered IDs, #(2) and #(3). Please remove one of the modules and regenerate the list', $nameinfile, $regId, $module['regid']);
                            throw new BadParameterException('regid',$msg);
                        }
                    }
                    if ($modregid == $regId) {
                            closedir($dh);
                            // Just return array without module name index
                            // We're selecting for one module
                            return array('directory'     => $directoryinfile, 
                                         'name'          => $name,
                                         'nameinfile'    => $nameinfile,
                                         'regid'         => $regId,
                                         'version'       => $version,
                                         'mode'          => $mode,
                                         'class'         => $class,
                                         'category'      => $category,
                                         'admin_capable' => $adminCapable,
                                         'user_capable'  => $userCapable,
                                         'dependency'    => $dependency,
                                         'extensions'    => $extensions,
                                         'author'        => $author,
                                         'contact'       => $contact,
                                         'license'       => $license,
                                         'official'      => $official,
                                         'credits'       => $credits,
                                         'changelog'     => $changelog,
                                         'help'          => $help,
                                         'blversion'     => $blversion,
                                         'xarversion'    => $xarversion,
                                         'publish_date'  => $publish_date,
                                         'dependencyinfo'=> $dependencyinfo                                          
                                         );
                    } else {
                            //put all modules in an array keyed on name
                            $fileModules[$name] =
                                   array('directory'     => $directoryinfile,
                                         'name'          => $name,
                                         'nameinfile'    => $nameinfile,
                                         'regid'         => $regId,
                                         'version'       => $version,
                                         'mode'          => $mode,
                                         'class'         => $class,
                                         'category'      => $category,
                                         'admin_capable' => $adminCapable,
                                         'user_capable'  => $userCapable,
                                         'dependency'    => $dependency,
                                         'extensions'   => $extensions,
                                         'author'       => $author,
                                         'contact'      => $contact,
                                         'license'      => $license,
                                         'official'     => $official,
                                         'credits'      => $credits,
                                         'changelog'    => $changelog,
                                         'help'         => $help,
                                         'blversion'    => $blversion,
                                         'xarversion'   => $xarversion,
                                         'publish_date' => $publish_date,
                                         'dependencyinfo'=> $dependencyinfo
                                          );
                    } // if modregid
                } // if is dir default
        } // switch
    } // while
    closedir($dh);

    return $fileModules;
}

?>
