<?php
/**
 * Update a theme variables
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update the configuration for a theme variable
 *
 * @param int id $ the theme's registered id
 * @param string newdisplayname $ the new display name
 * @param string newdescription $ the new description
 * @return bool true on success, error message on failure
 */
function themes_admin_update()
{
    // Get parameters
   if (!xarSecConfirmAuthKey()) return;

    if (!xarVarFetch('themeid', 'id', $regid)) return;
    if (!xarVarFetch("varname", 'isset', $singlevar, null,   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch("varid",   'int', $varid, null,   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('action', 'str',  $action, '',   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('save',    'str',       $save ,    '',   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('saveandreturn', 'str', $saveandreturn ,'',   XARVAR_NOT_REQUIRED)) {return;}
    $sitethemedir = xarConfigGetVar('Site.BL.ThemesDirectory');
    $themeinfo = xarThemeGetInfo($regid);
    if (!isset($themeinfo)) return;
    $themename = $themeinfo['name'];
    $propertytypes = xarMod::apiFunc('dynamicdata','user','getproptypes');

    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '$themename::$regId')) return xarResponseForbidden();

    //get all the db theme vars - the configs should be set with prime or not prime and at this stage cover all vars
    $dbthemevars = xarThemeGetConfig(array('themename'=>$themeinfo['name']));
    if (!isset($dbthemevars) || !is_array($dbthemevars)) $dbthemevars = array();
    $invalid = array();

    if ($action == 'configupdate') {
            $args = array();

           //we may have one property
            $args['id']     = $singlevar; //name of property
            $args['name']   = $singlevar.'_'.$varid;
            $args['type']   = 2002; //theme var type id
            $myprop = xarMod::apiFunc('dynamicdata','user','getproperty',$args);
            //get the value
            $isvalid = $myprop->checkInput($args['name']);

            if (!$isvalid) {
                $invalid = array('error'=>$myprop->invalid);
            } else {
                $newvalue = $myprop->getValue();
            }

            //ensure we have theme name
            $newvalue['themename'] =  $themename;
            if ($isvalid) {
                //don't update if not valid
                xarThemeSetConfig($newvalue);
                $msg =xarML('Configuration for "#(1)" was updated  and saved successfully.',$singlevar);
                xarTplSetMessage($msg,'status');
                //$args['config'] = $config;
                $oldargs['themeid'] = $regid;
                $oldargs['action'] = 'config';
                $oldargs['varname'] = $singlevar;

                if (!empty($save)) $returnurl = xarModURL('themes','admin','configaction',$oldargs);
            } else {
                $args['config'] = $config;
                $args['invalid'] = $invalid;
                $args['themename'] = $themeinfo['name'];
                $msg = xarML('There was a problem updating the configuration for "#(1)". Please check the configuration for errors.',$singlevar);
                xarTplSetMessage($msg,'error');
                return xarTplModule('themes','admin','configaction', $args);

            }
            unset($args);

    } elseif ($action == 'newvar') {

        if (!xarVarFetch('newvarname',        'str', $newname, '',   XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('newvartype',        'isset', $newvartype, 2,   XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('newvarvalue',       'str', $newval,  NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('newvardescription', 'str', $newdesc, NULL, XARVAR_NOT_REQUIRED)) {return;}

        if (!empty($newname)) {
            $label =  $newname;
            $newname = trim($newname);
            //consistent with DD
            $newname  = strtolower($newname);
            $newname = preg_replace('/[^a-z0-9_]+/','_',$newname);
            $newname =  preg_replace('/_$/','',$newname);
            if (!empty($newvartype)) {
                foreach($propertytypes as $proptypeid=>$propinfo) {
                    if ($proptypeid == $newvartype) {
                        $propertyname= $propinfo['name'];
                    }
                }
            }

            $config = array('propertyname'=> $propertyname,
                            'label'=> $newname,
                            'default'=>$newval,
                            'type'  =>$newvartype,
                            'status'=>1,
                            'varcat'=> 'miscellaneous',
                    );
            $myprop = xarMod::apiFunc('dynamicdata','user','getproperty',array('type'=>$newvartype,'label'=>$newname,'name'=>$newname));
            $myprop->__construct(array());
            //check the default value?

            $config['propargs'] = $myprop->getConfiguration();

            if (isset($newname) && $newname != '' && !isset($invalid['newvarname'])) {
                $updatevars = array('themename'=>$themeinfo['name'],
                                    'varname' => $newname,
                                    'value' => $newval,
                                    'prime' => 0,
                                    'desc' => $newdesc,
                                    'config'  =>$config);

                    $set = xarThemeSetConfig($updatevars);
                    if ($set) {
                        $msg =xarML('New variable "#(1)" added successfully.',$newname);
                        xarTplSetMessage($msg,'status');
                    } else {
                        $msg =xarML('There was a problem adding theme var "$set" to the database. Please check for errors.',$newname);
                         xarTplSetMessage($msg,'error');
                    }
            }
        }


         xarSession::setVar('themevars.invalid',$invalid);
        xarLogMessage('THEMES: Theme variable '.$newname.' for theme '.$themeinfo['name'].' was modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    }
    $returnurl = isset($returnurl) && !empty($returnurl) ?$returnurl : xarModURL('themes', 'admin', 'config', array('themeid' => $regid));
    xarResponseRedirect($returnurl);
    return true;
}

?>