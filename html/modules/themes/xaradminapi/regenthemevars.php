<?php
/**
 * Regenerate theme varlist
 *
 * @package modules

 * @subpackage Xarigami Themes
 * @copyright (C) 2010-2011 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Regenerate theme var list
 *
 * @author Xarigami Core Development Team
 * @param none
 * @return array of changed theme vars
 */
function themes_adminapi_regenthemevars($args)
{
    extract($args);
    if (!xarVarFetch('themename',      'str',   $themename,   '', XARVAR_NOT_REQUIRED)) return;
    if (!isset($themename) || empty($themename)) return;

    //initialise some vars
     $thememessage = '';
    //regenerate theme variables - move this to separate file
    //1. Get all the variables in the database for this theme
    //2. Make sure they are all in the correct format
    //3. Get all the system (file) vars
    //4. Check changed     - 0. no change
    //    Check for new ones - 1. add them to the db
    //   Check for deleted - 2. highlight as deleted but do not delete
    //   Check for changed - 3. update to the database

    // get the db theme variables
    $dbthemevars = xarThemeGetConfig(array('themename'=>$themename));
     //get file system theme vars
    $themevars = xarThemeGetConfig(array('themename'=>$themename,'configtype'=>'file'));
    if (!isset($themevars) || !is_array($themevars)) $themevars  = array();
    $varchanges = array();
    //update theme vars to system - result is updated db
    foreach($themevars as $var => $varvalue){
        //new theme variable
        if(!isset($varvalue['name']) || !isset($varvalue['value'])){
            $msg = xarML('Malformed Theme Variable (#(1)).', $var.'-'.$varvalue['name']);
            throw new Exception($msg);
        }
        $name           = $varvalue['name'];
        $prime          = isset( $varvalue['prime']) ?  $varvalue['prime']:1;//file theme vars are system theme vars
        $value          = $varvalue['value'];
        $description    = isset( $varvalue['description']) ?  $varvalue['description']:'';
        $config         = isset($varvalue['config']) && !empty($varvalue['config'])? $varvalue['config']: array();
        //now check we have any required config settings and optional propargs (equivalent of dd property validation)
        $config['propargs'] = isset($varvalue['config']['propargs'])?$varvalue['config']['propargs']:array();
        $config['label']    = isset($varvalue['config']['label'])?$varvalue['config']['label']:$varvalue['name'];
        $config['default']  = isset($varvalue['config']['default'])?$varvalue['config']['default'] :  $varvalue['value'];
        $config['propertyname'] =  isset($varvalue['config']['propertyname'])?$varvalue['config']['propertyname'] :  ''; //don't set

        //check if the theme var does not exist, load up the lot new
        if (!isset($dbthemevars[$name]) || empty($dbthemevars[$name])) {
            //let's set it  = new prime theme var
            $args = array('themename'   => $themename,
                          'varname'     => $name,
                          'value'       => $value,
                          'description' => $description,
                          'prime'       => 1, //theme vars from file are always prime
                          'config'      => $config
                        );
                   $set = xarThemeSetConfig($args);
                   $varchanges[$name]= 1;
            if(!$set)  {
                    //jojo - do we want this???
                 //   $msg = xarML("Could not load var '#(1)' for theme (#(2)).", $varvalue['name'], $themename);
                 //   throw new Exception($msg);
            }

        } elseif (isset($dbthemevars[$name])) {
            //the theme var already exists - don't overwrite the value unless $themestatus is set - this means new theme initialized
            // the config may have been set from the GUI
            //do not update config VALUE unless we have some flag - restore but we want to ensure prime and format is set
            //get the existing db config
            //make sure default is always set
            $dbthemevars[$name]['config']['default']        =   isset($varvalue['config']['default'])?$varvalue['config']['default']:$varvalue['value'] ;
            $dbthemevars[$name]['config']['propertyname']   =  $varvalue['config']['propertyname'];

            $config = $dbthemevars[$varvalue['name']]['config'];
            $config['prime'] =  $varvalue['prime'];
            $args = array('themename'    => $themename,
                           'varname'     => $name,
                           // 'value'    => $varvalue['value'], do not overwrite db value from file
                           'description' => $dbthemevars[$name]['description'],
                           'prime'       => $prime,
                           'config'      => $dbthemevars[$name]['config'] //ensure we keep existing db values
                              );
             $set = xarThemeSetConfig($args);

        }
        unset($args);
    }
    //ok we've updated the theme system vars to the database so anything in the db should be correct
    if (!empty($dbthemevars)) { //original dbthemevar array
        //check to see if there is still a filetheme var by that name or value is changed
        foreach ($dbthemevars as $varname=>$info) {
            $config = $info['config'];
            if (isset($info['prime']) && $info['prime'] == 1) {
                //we want to know if it is still a theme system var
                $found = 0;
                foreach($themevars as $var=>$vardata) {
                    if ($varname == $vardata['name']) {
                        //nothing - the theme exists still  - perhaps the value is different?
                        $found = 1;
                        //value may be different
                        if ($vardata['value'] != $info['value']) {
                           // $varchanges[$themename] [$varname]= 3; //changed
                        }
                        break;
                    }
                }
                if ($found != 1) {
                    $varchanges[$varname]= 2; //deleted
                }
            }
        }
    }
    //$varchanges['thememessage'] = $thememessage;
    return $varchanges;
}

?>