<?php
/**
 * Export theme vars
 *
 * @package modules
 * @subpackage Xarigami Themes module
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * export theme variables
 */
function themes_admin_exportvars($args)
{
    extract($args);

    // Get parameters
    if (!xarVarFetch('themeid', 'int:1:', $themeid)) return;
    if (!xarVarFetch('vartype','isset', $vartype, 'all', XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('format','isset', $format, 'php', XARVAR_DONT_SET)) {return;}

      //regenerate theme list to ensure latest file info in the db
    $changevars = xarMod::apiFunc('themes','admin','regenerate');
    $sitethemedir = xarConfigGetVar('Site.BL.ThemesDirectory');
    $themeinfo = xarThemeGetInfo($themeid);
    if (!isset($themeinfo)) return;
    $vartype = trim($vartype);
    $themename = $themeinfo['name'];
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '$themename::$regId')) return xarResponseForbidden();
    // Initialise the template variables
    $data = array();
    $data['themename'] = $themename;
    $data['vartype'] = $vartype;
    $varcode = ''; //initialize the variable that will hold the code
    //get all the db theme vars - the configs should be set with prime or not prime
    $dbthemevars = xarThemeGetConfig(array('themename'=>$themeinfo['name']));
    if ($format == 'php') {
        $varcode .= "//Variables for ".$themename."\n\n";
        foreach ($dbthemevars as $dbvar => $dbinfo) {
            $dbinfo['prime'] = isset($dbinfo['prime'])?$dbinfo['prime']:0;
            $dbinfo['value'] = isset($dbinfo['value'])?$dbinfo['value']:'';
              if (isset($dbinfo['id'])) unset($dbinfo['id']);
            //get rid of unecessary values
            if (isset($dbinfo['config']['prime'])) unset($dbinfo['config']['prime']);
             if (isset($dbinfo['config']['format'])) unset($dbinfo['config']['format']);
            //get rid of empty config values
            if (isset($dbinfo['config']['propargs']) && is_array($dbinfo['config']['propargs'])) {
                foreach ($dbinfo['config']['propargs'] as $configname=>$configvalue  )
                {
                    if ((substr($configname,0,3) == 'xv_') && (!isset($configvalue)|| empty($configvalue))) {
                        unset($dbinfo['config']['propargs'][$configname]);
                    }elseif (substr($configname,0,3) != 'xv_') {
                          unset($dbinfo['config']['propargs'][$configname]);
                    }
                }
                //and any other superfluous ones can be removed
                if (isset($dbinfo['config']['value'])) unset($dbinfo['config']['value']);
                if (isset($dbinfo['config']['proptype'])) unset($dbinfo['config']['proptype']);
                if (isset($dbinfo['config']['name'])) unset($dbinfo['config']['name']);
                 if (isset($dbinfo['config']['validation'])) unset($dbinfo['config']['validation']);

            }
            if ( $dbinfo['prime'] == 0) {
                $varcode .= "\$themevars['".$dbvar."'] = ";
                ob_start();
                print_r("\n");
                var_export($dbinfo);
                $test = ob_get_contents();
                ob_end_clean();
                $varcode .= $test.";\n";
            }
            if (( $dbinfo['prime'] == 1) && ($vartype == 'all')) {
                $varcode .= "\$themevars['".$dbvar."'] = ";
                ob_start();
                print_r("\n");
                var_export($dbinfo);;
                $test = ob_get_contents();
                ob_end_clean();
                $varcode .= $test.";\n";
            }
         }

    }
       //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');
    // Return the template variables defined in this function
   $data['varcode'] = xarVarPrepForDisplay($varcode);
    return $data;
}

?>