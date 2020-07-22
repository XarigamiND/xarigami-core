<?php
/**
 * Check for properties and import to properties table
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Check the properties directory for properties and import them into the Property Type table.
 *
 * @author the DynamicData module development team
 * @param $args['flush'] flush the property type table before import true/false (optional)
  * @param $args['returnerrors'] return errrors in the array true/false (optional)
 * @return an array of the property types currently available, or with array of errors if $return errors true
 * This function is called during module installation, upgrade, and installation as well as independently as required
 * We cannot afford it to break at any point eg in install.
 * @throws nothing
 */
sys::import('xarigami.structures.relativedirectoryiterator');

function dynamicdata_adminapi_importpropertytypes( $args )
{
    extract( $args );
    if (!isset($returnerrors)) $returnerrors = FALSE;

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;
     $installing = xarCoreCache::getCached('installer','installing');
    $dynamicproptypes = $xartable['dynamic_properties_def'];
    $propDirs = array();

    if(isset($dirs) && is_array($dirs)) {
      // We got an array of directories passed in for which to import properties
      // typical usecase: a module which has its own property, during install phase needs that property before
      // the module is active.
      $propDirs = $dirs;
    } else {
        //Cear the cache
        PropertyRegistration::ClearCache();

        $activeMods = xarMod::apiFunc('modules','admin','getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        if(empty($activeMods)) return; // this should never happen

        foreach($activeMods as $modInfo) {
            // the modinfo directory does NOT end with a /
            $dir = sys::code().'modules/' .$modInfo['osdirectory'] . '/xarproperties/';
            if(file_exists($dir)){
                $propDirs[$modInfo['name']] = $dir;
            }
        }
    }
     xarLogMessage('DYNAMICDATA: importpropertytypes about to get properties in directories');
    // Get list of properties in properties directories
    $invalid = array(); //keep a list of invalid property files or errors
    static $loaded = array();
    $proptypes = array();
    $numLoaded = 0;
    $declaredlist = array();
    foreach($propDirs as $mod=> $PropertiesDir) {
        // Open Properties Directory if it exists, otherwise go to the next one
        if (!file_exists($PropertiesDir)) continue;

        $dir = new RelativeDirectoryIterator($PropertiesDir);
         // Loop through properties directory
         for($dir->rewind();$dir->valid();$dir->next()) {
            // Only Process files, not directories
            if($dir->isDir()) continue; // no dirs
            if($dir->getExtension() != 'php') continue; // only php files
            if($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
             // Include the file into the environment
             $file = $dir->getPathName();

            if(!isset($loaded[$file])) {
                $dp = str_replace('/','.',substr($PropertiesDir.basename( $file),0,-4));
                sys::import($dp);
                $loaded[$file] = true;
            }
            $propertyClass = basename($file,'.php');
            xarLogMessage("DYNAMICDATA: Checking property class FOR $mod with class $propertyClass");
            if (class_exists($propertyClass)) {
                //let's check it has methods
                if(!is_subclass_of ($propertyClass, 'Dynamic_Property')) {
                  $msg = xarML("Bad class - #(1) - not a subclass of Dynamic_Property", $propertyClass);
                   xarTplSetMessage($msg,'error');
                 $invalid[] = $msg;
                    continue;
                }
            } else {
                $msg = xarML("Bad propertyClass or file in #(1)", $file);
                   xarTplSetMessage($msg,'error');
                 xarLogMessage('DYNAMICDATA: invalid class for '.$propertyClass);
                 $invalid[] = $msg;
                 continue;
            }
            // Tell the property to skip initialization, this is only really needed for Dynamic_FieldType_Property
            // because it causes this function to recurse.
            $args['skipInit'] = true;

            // Instantiate a copy of this class
            $property = new $propertyClass($args);
            $baseInfo = new PropertyRegistration($args);
            //$declaredlist[$propertyClass] = array($propertyfilepath);
            // Get the base information that used to be hardcoded into /modules/dynamicdata/class/properties.php
            $baseInfo = $property->getBasePropertyInfo();

            if (!isset($baseInfo['filepath'])) {
                $baseInfo['filepath']='modules/base/xarproperties';
                $msg =xarML("No filepath for  #(1)", $propertyClass);
                 xarTplSetMessage($msg,'error');
                 $invalid[] = $msg;
                xarLogMessage("DYNAMICDATA PROPERTY: No filepath for ".$propertyClass);
                continue;
            }
            $propertyfilepath = $baseInfo['filepath'] . '/' . $propertyClass . '.php';
            // Ensure that the base properties are all present.
            if( !isset($baseInfo['dependancies']) )   $baseInfo['dependancies'] = '';
            if( !isset($baseInfo['requiresmodule']) ) $baseInfo['requiresmodule'] = '';
            if( !isset($baseInfo['aliases']) )        $baseInfo['aliases'] = '';
            if( empty($baseInfo['args']) )            $baseInfo['args'] = serialize(array());

            // If the property needs specific files to exist, check for them

            if( isset($baseInfo['dependancies']) && ($baseInfo['dependancies'] != '') ) {
                $dependancies = explode(';', $baseInfo['dependancies']);
                foreach( $dependancies as $dependancy ) {
                    // If the file is not there continue to the next property
                    if( !file_exists($dependancy) ) {
                        $msg = xarML("Dependency not met for #(1) ", $propertyClass);
                        xarTplSetMessage($msg,'error');
                        $invalid[] = $msg;
                     continue 2;
                    }
                }
            }

            // Check if any Modules are required
            //this function is called after a mod activation so result should be OK doing so
            if( isset($baseInfo['requiresmodule']) && ($baseInfo['requiresmodule'] != '') ) {

                $modulesNeeded = explode(';', $baseInfo['requiresmodule']);
                  foreach( $modulesNeeded as $moduleName ) {
                    // is this valid? As long as it is not the current module
                    if ($moduleName == $mod) continue;
                    // If a required module is not available continue with the next property
                        if( !xarMod::isAvailable($moduleName) &&!$installing) {
                            $msg = xarML("A required module #(1) is not available for #(2). ", $moduleName,$propertyClass);
                            xarTplSetMessage($msg,'error');
                             $invalid[] = $msg;
                            break 2;
                        }
                }
            }
            // Save the name of the property
            $baseInfo['propertyClass'] = $propertyClass;
            $baseInfo['filepath'] = $propertyfilepath;

            // Check for aliases
            if( !isset($baseInfo['aliases']) || ($baseInfo['aliases'] == '') || !is_array($baseInfo['aliases']) ) {
                // Make sure that this is always available
                $baseInfo['aliases'] = '';

                // Add the property to the property type list
                $proptypes[$baseInfo['id']] = $baseInfo;

            } else if ( is_array($baseInfo['aliases']) && (count($baseInfo['aliases']) > 0) ) {
                // if aliases are present include them as seperate entries
                $aliasList = '';
                foreach( $baseInfo['aliases'] as $aliasInfo ) {
                    // Save the name of the property, for the alias
                    $aliasInfo['propertyClass'] = $propertyClass;
                    $aliasInfo['aliases']       = '';
                    $aliasInfo['filepath']      = $propertyfilepath;

                    // Add the alias to the property type list
                    $proptypes[$aliasInfo['id']] = $aliasInfo;
                    $aliasList .= $aliasInfo['id'].',';

                    // Update Database
                    updateDB( $aliasInfo, $baseInfo['id'], $propertyfilepath );
                }

                // Store a list of reference ID's from the base property it's aliases
                // FIXME: strip the last comma off?
                $baseInfo['aliases'] = $aliasList;

                // Add the base property to the property type list
                $proptypes[$baseInfo['id']] = $baseInfo;
            }
            xarLogMessage("DD IMPORT PROP: updating db for property $propertyfilepath");
            // Update database entry for this property (the aliases array, if any, will now be an aliaslist)

            updateDB( $baseInfo, '', $propertyfilepath );

        }//loop over files
         //jojo - finish above and use registration class to update
         //foreach ($proptypes as $proptype) $registered = $proptype->Register();
         //unset($proptypes);
    } //loop over directories

    // Sort the property types
    ksort( $proptypes );

    if (isset($returnerrors) && $returnerrors == TRUE) {
         return $proptypes['invalid'] = $invalid;
    } else {
        return $proptypes;
    }
}
/**
 * Update the database with info on the property
 * @param array $proptype the info array on the property
 * @return void
 */
function updateDB( $proptype, $parent, $filepath )
{

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $dynamicproptypes = $xartable['dynamic_properties_def'];

    $insert = "INSERT INTO $dynamicproptypes
                ( xar_prop_id, xar_prop_name, xar_prop_label,
                  xar_prop_parent, xar_prop_filepath, xar_prop_class,
                  xar_prop_format, xar_prop_validation, xar_prop_source,
                  xar_prop_reqfiles, xar_prop_reqmodules, xar_prop_args,
                  xar_prop_aliases
                )
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $bindvars = array((int) $proptype['id'], $proptype['name'], $proptype['label'],
                      $parent, $filepath, $proptype['propertyClass'],
                      $proptype['format'], $proptype['validation'], $proptype['source'],
                      $proptype['dependancies'], $proptype['requiresmodule'], $proptype['args'],
                      $proptype['aliases']);
    try {
        $result = $dbconn->Execute($insert,$bindvars);
    } catch (Exception $e) {
         xarLogMessage("DD IMPORT PROP: Problem updating db for property ".$proptype['propertyClass']);
    }
}
?>
