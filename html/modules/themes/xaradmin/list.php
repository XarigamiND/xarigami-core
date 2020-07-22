<?php
/**
 * List themes and current settings
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * List themes and current settings
 * @param none
 */
function themes_admin_list()
{
    // Security Check
    if(!xarSecurityCheck('AddTheme',0)) return xarResponseForbidden();

    // form parameters
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('regen',    'isset', $regen,    NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('invalid',  'array', $invalid,'', XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc', $sort, NULL,  XARVAR_DONT_SET)) return;
    if(!xarVarFetch('order',     'isset',  $order,     '', XARVAR_DONT_SET)) {return;}

    $data['items'] = array();
    $data['infolabel']                              = xarML('Info');
    $data['actionlabel']                            = xarML('Action');
    $data['optionslabel']                           = xarML('Options');
    $data['reloadlabel']                            = xarML('Refresh');
    $data['pager']                                  = '';
    $authid = xarSecGenAuthKey();

    // pass tru some of the form variables
    $data['regen']                                  = $regen;
    $data['selpreview']                             = xarModUserVars::get('themes', 'selpreview');
    $data['selfilter']                              = xarModUserVars::get('themes', 'selfilter');
    $data['selclass']                               = xarModUserVars::get('themes', 'selclass');
    $data['useicons']                               = xarModUserVars::get('themes', 'useicons');

    // labels for class names
    $data['class']['all']                           = xarML('All');
    $data['class']['system']                        = xarML('System');
    $data['class']['utility']                       = xarML('Utility');
    $data['class']['user']                          = xarML('User');

    $data['filter'][XARTHEME_STATE_ANY]                         = xarML('All');
    $data['filter'][XARTHEME_STATE_INSTALLED]                   = xarML('Installed');
    $data['filter'][XARTHEME_STATE_ACTIVE]                      = xarML('Active');
    $data['filter'][XARTHEME_STATE_INACTIVE]                    = xarML('Inactive');
    $data['filter'][XARTHEME_STATE_UNINITIALISED]               = xarML('Uninitialized');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_UNINITIALISED]  = xarML('Missing (Not Installed)');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_INACTIVE]       = xarML('Missing (Inactive)');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_ACTIVE]         = xarML('Missing (Active)');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_UPGRADED]       = xarML('Missing (Upgraded)');
    $data['filter'][XARTHEME_STATE_ERROR_UNINITIALISED]         = xarML('Update (Not Installed)');
    $data['filter'][XARTHEME_STATE_ERROR_INACTIVE]              = xarML('Update (Inactive)');
    $data['filter'][XARTHEME_STATE_ERROR_ACTIVE]                = xarML('Update (Active)');
    $data['filter'][XARTHEME_STATE_ERROR_UPGRADED]              = xarML('Update (Upgraded)');

    // obtain list of themesbased on filtering criteria
/*     if($regen){ */
        // lets regenerate the list on each reload, for now
        $regenerate = xarMod::apiFunc('themes', 'admin', 'regenerate');
        $varchanges = array();
        $thememessage = '';
        //check variables
        if (is_array($regenerate)) {
            $thememessage = isset($regenerate['thememessage']) ?$regenerate['thememessage'] :'';
            if (count($regenerate)>1) {
                foreach ($regenerate as $themen=>$varinfo) {
                    $msg = '';
                    if (is_array($varinfo)) {
                        foreach($varinfo as $varname=>$varstate) {
                            if ($varstate ==3) {
                          //      $varstate = xarML('Default value  in theme file is different in your site database.');
                            }elseif ($varstate ==2) {
                                $msg= xarML('System variable - #(1) - removed in theme "#(2)", retained in your site database and possibly in your templates.',$varname,$themen);
                            } elseif ($varstate ==1) {
                                $msg= xarML('System variable - #(1) -  added in theme "#(2)" and in your site database.', $varname, $themen);
                            }
                            if (!empty($msg)) xarTplSetMessage($msg,'alert');
                           $varchanges[$themen][$varname] = $msg;
                        }
                    }
                }
            }
        }
         $data['thememessage'] = $thememessage;
        $data['varchanges'] = $varchanges;
        // assemble filter for theme list
        $filter = array('State' => $data['selfilter']);
        if ($data['selclass'] != 'all') {
            $filter['Class'] = strtr(
                $data['selclass'],
                array('system' => 0, 'utility' => 1, 'user' => 2)
            );
        }
    // get themes
    $themelist = xarMod::apiFunc('themes','admin','getthemelist',  array('filter'=> $filter));

    // get action icons/images classes
    $icondisabled       = 'xar-icon-disabled ';//leave the space
    $img_disabled       = 'sprite xs-disabled';
    $img_none           = 'sprite xs-none';
    $img_activate       = 'sprite xs-activate';
    $img_deactivate     = 'sprite xs-deactivate';
    $img_upgrade        = 'sprite xs-software-upgrade';
    $img_initialise     = 'sprite xs-software-install';
    $img_config         = 'esprite xs-modify-config';
    $img_remove         = 'esprite xs-remove';
    $img_warning        = 'sprite xs-dialog-warning';
    $img_error          = 'sprite xs-dialog-warning';
    $img_default        = 'sprite xs-set-default';
    //classes
    $infoclass = 'xar-info';
    $errorclass = 'xar-errorstate';
    $missingclass = 'xar-missing';
    // get other images
    $data['infoimg']    = 'sprite xs-info';
    $data['editimg']    = 'sprite xs-hooks';
    $data['dummyimage'] = xarTplGetImage('blank.gif','base');
    $data['listrowsitems'] = array();
    $listrows = array();
    $i = 0;
    // set sorting vars

    $order = !empty($order) ?$order : 'name';
    $sort = !empty($sort) ? $sort: 'asc' ;
    //sort by display name
    usort($themelist,'cmp');
    if ($sort == 'desc') {
        $themelist = array_reverse($themelist);
        $newsort = 'asc';
        $sortimage = 'esprite xs-sorted-desc';
        $sortlabel = xarML('Sorted Descending');
    } else {
        $newsort = 'desc';
        $sortimage = 'esprite xs-sorted-asc';
        $sortlabel = xarML('Sorted Ascending');
    }
    $data['sorturl'] = xarServer::getCurrentURL(array('sort' => $newsort));
    $data['sortimage'] = $sortimage;
    $data['sortlabel'] = $sortlabel;

    //common labels for one time translation
    $nalabel = xarML('Not available');
    $disabledlabel = xarML('Disabled');
    $removelabel = xarML('Remove');
    $makedefault = xarML('Make Default');
    $initialiselabel = xarML('Initialise');
    $activatelabel = xarML('Activate');
    $upgradelabel = xarML('Upgrade');
    $errorlabel = xarML('View error details');
    $deactivatelabel = xarML('Deactivate');
    $defaultlabel = xarML('Set Default');
    // now we can prepare data for template
    // we will use standard xarMod api calls as much as possible
    foreach($themelist as $theme){
        // we're going to use the module regid in many places
        $thisthemeid = $theme['regid'];
        $isdefault = FALSE; //track if default theme - cannot deactivate or configure
        $isinstaller = FALSE; //track if install theme - cannot deactivate or configure
        $issystem = FALSE; //track if system theme - cannot make default
        $isutility = FALSE; //track if utility theme - -cannot uninstall/deactivate
        $defaulttheme = xarModGetVar('themes','default');
          $themename = trim($theme['name']);
        if (($themename) == trim($defaulttheme)) $isdefault = TRUE;
        if (($themename) == 'installtheme') $isinstaller = TRUE;

        if ($theme['class'] == 0) $issystem = TRUE;
        if ($theme['class'] == 1) $isutility = TRUE;

        // for the sake of clarity, lets prepare all our links in advance
        $initialiseurl = xarModURL('themes', 'admin', 'install',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $activateurl = xarModURL('themes', 'admin', 'activate',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $deactivateurl = xarModURL('themes', 'admin', 'deactivate',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $removeurl = xarModURL('themes', 'admin', 'remove',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $upgradeurl = xarModURL('themes', 'admin', 'upgrade',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $errorurl = xarModURL('themes', 'admin', 'viewerror',
            array('id' => $thisthemeid, 'authid' => $authid)
        );

        $configurl = xarModURL('themes', 'admin', 'config',
            array('themeid' => $thisthemeid)
        );
        // common urls
        $editurl = xarModURL('themes', 'admin', 'modify',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $infourl = xarModURL('themes', 'admin', 'themesinfo',
            array('id' => $thisthemeid)
        );
        // added due to the feature request - opens info in new window
        $infourlnew = xarModURL('themes', 'admin', 'themesinfo',
            array('id' => $thisthemeid)
        );
        $defaulturl= xarModURL('themes', 'admin', 'setdefault',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        // common listitems
        $listrows[$i]['isdefault']      = $isdefault;
        $listrows[$i]['issystem']       = $issystem;
        $listrows[$i]['isutility']      = $isutility;
        $listrows[$i]['displayname']    = isset($theme['displayname'])?$theme['displayname']:$theme['name'];
        $listrows[$i]['description']    = isset($theme['description'])?$theme['description']:'';
        $listrows[$i]['version']        = $theme['version'];
        $listrows[$i]['edit']           = xarML('Edit');
        $listrows[$i]['class']          = $theme['class'];
        $listrows[$i]['directory']      = $theme['directory'];
        $listrows[$i]['name']           = $theme['name'];

        if (!isset($theme['state']) || empty($theme['state'])) {
            $theme['state'] = 1;
        }

        // class labels
        switch($theme['class']) {
            case '2':
                $listrows[$i]['classlabel'] = $data['class']['user'];
                break;
            case '1':
                $listrows[$i]['classlabel'] = $data['class']['utility'];
                break;
            case '0':
                $listrows[$i]['classlabel'] = $data['class']['system'];
                break;
            default:
                $listrows[$i]['classlabel'] = xarML('Unknown');
        }
        //initialize all vars
        $listrows[$i]['actionlabel2']    = '';
        // conditional data
        $statelabelclass = ''; //initialise
        if($theme['state'] ==  XARMOD_STATE_UNINITIALISED){
            // this theme is 'Uninitialised'   - set labels and links
            $listrows[$i]['statelabel']         = xarML('Uninitialized');
            $listrows[$i]['statelabelicon']     = 'sprite xs-package-available';
            $statelabelclass = '';
            $listrows[$i]['state']              = XARMOD_STATE_UNINITIALISED;
            $listrows[$i]['actionlabel']        = $initialiselabel;
            $listrows[$i]['actionurl']          = $initialiseurl;
            $listrows[$i]['actionimg1']         = $img_initialise;

            $listrows[$i]['actionurl2']         = '';
            $listrows[$i]['actionlabel2']       = $nalabel;
            $listrows[$i]['actionimg2']         = $icondisabled.$img_default;

            $listrows[$i]['removeurl']          = '';
            $listrows[$i]['removelabel']        = $nalabel;
            $listrows[$i]['removeimg']          = $icondisabled.$img_remove; //$img_none;
            $listrows[$i]['configurl']          = '';
            $listrows[$i]['configimg']         = $icondisabled.$img_config;

        }elseif($theme['state'] ==  XARTHEME_STATE_INACTIVE){
            // this theme is 'Inactive'        - set labels and links
            $statelabelclass = '';
            $listrows[$i]['statelabel']         = xarML('Inactive');
            $listrows[$i]['statelabelicon']     = 'sprite xs-package-inactive';
            $listrows[$i]['state']              =  XARTHEME_STATE_INACTIVE;
            if ($issystem ) {
                $listrows[$i]['removeurl']      = '';
                $listrows[$i]['removelabel']    = xarML('System theme-cannot remove');
                $listrows[$i]['removeimg']      = $img_disabled;
                $listrows[$i]['actionlabel2']   = $disabledlabel;
                $listrows[$i]['actionurl2']     = '';
                $listrows[$i]['actionimg2']     = $icondisabled.$img_default;//$img_none;
                $listrows[$i]['configurl']          = '';
                $listrows[$i]['configimg']         = $icondisabled.$img_config;
            } else {
                $listrows[$i]['removeurl']      = $removeurl;
                $listrows[$i]['removelabel']    = $removelabel;
                $listrows[$i]['removeimg']      = $img_remove;

                $listrows[$i]['actionlabel2']   = $nalabel;
                $listrows[$i]['actionurl2']     = '';
                $listrows[$i]['actionimg2']     = $icondisabled.$img_default;//$img_none;
                $listrows[$i]['configurl']          = $configurl;
                $listrows[$i]['configimg']          = $img_config;
            }
            //allow configurable default
            if ($isdefault) {
                $listrows[$i]['configurl']          = $configurl;
                $listrows[$i]['configimg']          = $img_config;
            }
            $listrows[$i]['actionlabel']        = $activatelabel;
            $listrows[$i]['actionurl']          = $activateurl;
            $listrows[$i]['actionimg1']         = $img_activate;

        }elseif($theme['state'] ==  XARTHEME_STATE_ACTIVE){
            // this theme is 'Active'          - set labels and links
            $listrows[$i]['statelabel']     = xarML('Active');
            $listrows[$i]['statelabelicon']  = 'sprite xs-package-installed';
            $listrows[$i]['state']          = XARTHEME_STATE_ACTIVE;
            $statelabelclass = '';

            if ($issystem && !$isdefault ) {
                $listrows[$i]['configurl']          = '';
                $listrows[$i]['configimg']         = $icondisabled.$img_config;
            } else {
                $listrows[$i]['configurl']     = $configurl;
                $listrows[$i]['configimg']     = $img_config;
            }

            if ($isdefault || $isinstaller) {
                $listrows[$i]['actionlabel']    = $nalabel;
                $listrows[$i]['actionurl']      = '';
                $listrows[$i]['actionimg1']     = $icondisabled.$img_deactivate; //$img_none;
                $listrows[$i]['actionurl2']      = '';
                $listrows[$i]['actionimg2']      = $icondisabled.$img_default; //$img_none;
                $listrows[$i]['actionlabel2']    = xarML('Current default');
                $listrows[$i]['removeurl']      = '';
                $listrows[$i]['removelabel']    = xarML('Cannot remove system theme');
                $listrows[$i]['removeimg']      = $issystem?$img_disabled:$icondisabled.$img_remove; //$img_none;
           }elseif ($isutility) {
                $listrows[$i]['actionlabel']    = $deactivatelabel;
                $listrows[$i]['actionurl']      = $deactivateurl;
                $listrows[$i]['actionimg1']     = $img_deactivate;
                $listrows[$i]['actionurl2']     = '';
                $listrows[$i]['actionimg2']     = $img_disabled;
                $listrows[$i]['actionlabel2']   = $disabledlabel;
                $listrows[$i]['removeurl']      = '';
                $listrows[$i]['removelabel']    = $nalabel;
                $listrows[$i]['removeimg']      = $icondisabled.$img_remove; //$img_none;
            }else{
                $listrows[$i]['actionlabel']    = $deactivatelabel;
                $listrows[$i]['actionurl']      = $deactivateurl;
                $listrows[$i]['actionurl2']     = $defaulturl;
                $listrows[$i]['actionlabel2']   =$defaultlabel;
                $listrows[$i]['actionimg1']     = $img_deactivate;
                $listrows[$i]['actionimg2']     = $img_default;
                $listrows[$i]['removeurl']      = '';
                $listrows[$i]['removelabel']    = $issystem?$disabledlabel:$nalabel;
                $listrows[$i]['removeimg']      = $issystem?$img_disabled:$icondisabled.$img_remove; //$img_none;
            }
        }elseif($theme['state'] == XARTHEME_STATE_MISSING_FROM_UNINITIALISED ||
                $theme['state'] == XARTHEME_STATE_MISSING_FROM_INACTIVE ||
                $theme['state'] == XARTHEME_STATE_MISSING_FROM_ACTIVE ||
                $theme['state'] == XARTHEME_STATE_MISSING_FROM_UPGRADED){
            // this theme is 'Missing'         - set labels and links
            $statelabelclass = $missingclass;
            $listrows[$i]['statelabel']  = xarML('Missing');
            $listrows[$i]['statelabelicon']  = 'sprite xs-package-remove';
            $listrows[$i]['state']          = XARTHEME_STATE_MISSING_FROM_UNINITIALISED;


            $listrows[$i]['actionlabel']        = xarML('Files missing or not readable');
            $listrows[$i]['actionimg1']         = $img_error;
            $listrows[$i]['actionurl']          = $errorurl;

            $listrows[$i]['removeurl']          = $removeurl;
            $listrows[$i]['removelabel']        = $removelabel;
            $listrows[$i]['removeimg']          = $img_remove;

            $listrows[$i]['actionurl2']         = '';
            $listrows[$i]['actionlabel2']       = $nalabel;
            $listrows[$i]['actionimg2']         = $isutility?$img_disabled:$icondisabled.$img_default; //$img_none;
            $listrows[$i]['configurl']          = '';
            $listrows[$i]['configimg']         = $icondisabled.$img_config;
        }elseif($theme['state'] == XARTHEME_STATE_ERROR_UNINITIALISED ||
                $theme['state'] == XARTHEME_STATE_ERROR_INACTIVE ||
                $theme['state'] == XARTHEME_STATE_ERROR_ACTIVE ||
                $theme['state'] == XARTHEME_STATE_ERROR_UPGRADED){
            $statelabelclass = $errorclass;
            $listrows[$i]['statelabel']  =  $errorlabel;
            $listrows[$i]['statelabelicon'] = 'sprite xs-package-broken';
            $listrows[$i]['state'] = XARMOD_STATE_ERROR_UNINITIALISED;

            $listrows[$i]['actionlabel']        = $errorlabel;
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['actionimg1']         =  $img_error;

            $listrows[$i]['actionurl2']         = '';
            $listrows[$i]['actionlabel2']       = $nalabel;
            $listrows[$i]['actionimg2']         = $isutility?$img_disabled:$icondisabled.$img_default; //$img_none;

            $listrows[$i]['removeurl']          = $removeurl;
            $listrows[$i]['removelabel']        = $removelabel;
            $listrows[$i]['removeimg']          = $img_remove;
            $listrows[$i]['configurl']          = '';
            $listrows[$i]['configimg']         = $icondisabled.$img_config;
        }elseif($theme['state'] == XARTHEME_STATE_UPGRADED){
            // this theme is 'Upgraded'        - set labels and links
            $listrows[$i]['statelabel']     = xarML('Upgraded');
            $listrows[$i]['statelabelicon'] = 'sprite xs-package-upgrade';
            $listrows[$i]['state']          = XARTHEME_STATE_UPGRADED;
            $statelabelclass = '';
            $listrows[$i]['actionlabel']        = $upgradelabel;
            $listrows[$i]['actionurl']          = $upgradeurl;
            $listrows[$i]['actionurl2']          = '';
            $listrows[$i]['actionlabel2']       =$nalabel;
            $listrows[$i]['actionimg1']         = $img_upgrade;
            $listrows[$i]['actionimg2']         =  $isutility?$img_disabled:$icondisabled.$img_default; //$img_none;

            $listrows[$i]['removeurl']          = $removeurl;
            $listrows[$i]['removelabel']        = $removelabel;
            $listrows[$i]['removeimg']          = $img_remove;
            $listrows[$i]['configurl']          = '';
            $listrows[$i]['configimg']         = $icondisabled.$img_config;
        } else {
          // Something seriously wrong
            $statelabelclass = $errorclass;
            $listrows[$i]['statelabelicon'] = 'sprite xs-package-broken';
            $listrows[$i]['statelabel']  = xarML('Unknown');
            $listrows[$i]['state'] = $removelabel;

            $listrows[$i]['actionurl'] = $errorurl;
            $listrows[$i]['actionlabel'] = xarML('Remove (Bug! in list generation)');
            $listrows[$i]['actionimg1']         =  $img_error;
            $listrows[$i]['actionimg2']         =  $isutility?$img_disabled:$icondisabled.$img_default; //$img_none;
            $listrows[$i]['actionlabel2']       =  $nalabel;
            $listrows[$i]['actionurl2'] = '';
            $listrows[$i]['removeurl']          = $removeurl;
            $listrows[$i]['removelabel']        = $removelabel;
            $listrows[$i]['removeimg']          = $img_remove;
            $listrows[$i]['configurl']          = '';
            $listrows[$i]['configimg']         = $icondisabled.$img_config;
        }

        if ($isdefault) $listrows[$i]['statelabel'] .= '&#160;'.xarML('(Current Default)');

        if (!xarSecurityCheck('AdminTheme', 0, 'All', "$themename:$thisthemeid")) {
            $configurl = '';
            $listrows[$i]['configimg']         = $icondisabled.$img_config;
        }
        // nearly done
        $listrows[$i]['regid']          = $thisthemeid;
        $listrows[$i]['defaulturl']     = $defaulturl;
        $listrows[$i]['infourl']        = $infourl;
        $listrows[$i]['infourlnew']     = $infourlnew;
        $listrows[$i]['editurl']        = $editurl;
        $listrows[$i]['statelabelclass']  = $statelabelclass ;


        // preview images
        $themedir =xarConfigGetVar('Site.BL.ThemesDirectory');
        $previewpath = "$themedir/$theme[directory]/images/preview.jpg";

        $listrows[$i]['preview'] = file_exists($previewpath) ? $previewpath : '';

        $data['listrowsitems'] = $listrows;
        $i++;
    }
    // detailed info image url
    $data['infoimage'] = 'sprite xs-info';
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');
    // Send to template
    return $data;
}
function cmp($a, $b)
{
    return strcmp($a['displayname'], $b['displayname']);
}

?>
