<?php
/**
 * List modules and current settings
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * List modules and current settings
 * @author Xarigami Development Team
 * @param several params from the associated form in template
 * @todo  finish cleanup, styles, filters and sort orders
 */
function modules_admin_list()
{

    // Security Check
    if(!xarSecurityCheck('AdminModules',0)) return xarResponseForbidden();
    $defnumitems = xarModGetVar('modules','itemsperpage')?xarModGetVar('modules','itemsperpage'):80;
    // form parameters
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('numitems', 'isset', $numitems,  $defnumitems , XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('regen',    'isset', $regen,    NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('invalid',  'array', $invalid,'', XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('sort',     'pre:trim:alpha:lower:enum:asc:desc', $sort, '',  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('order',    'str:0:', $order,    '', XARVAR_NOT_REQUIRED)) return;

    $lastquery = xarSession::getVar('list.modquery');

    $data['invalid'] = $invalid;
    // Specify common labels for display for one time translation
    $data['infolabel']      = xarML('Module Information');
    $data['reloadlabel']    = xarML('Reload');
    $removelabel            = xarML('Remove');
    $nalabel                = xarML('Not available');
    $data['nalabel'] = $nalabel;
    $authid                 = xarSecGenAuthKey();

    // pass tru some of the form variables (we dont store them anywhere, atm)
    $data['hidecore']                               = xarModUserVars::get('modules', 'hidecore');
    $data['regen']                                  = $regen;
    $data['selstyle']                               = xarModUserVars::get('modules', 'selstyle');
    $data['selfilter']                              = xarModUserVars::get('modules', 'selfilter');
    $data['selsort']                                = xarModUserVars::get('modules', 'selsort');

    $data['useicons']                               = xarModUserVars::get('modules', 'useicons');

    $data['filter'][XARMOD_STATE_ANY]               = xarML('All Modules');
    $data['filter'][XARMOD_STATE_INSTALLED]         = xarML('All Installed');
    $data['filter'][XARMOD_STATE_ACTIVE]            = xarML('All Active');
    $data['filter'][XARMOD_STATE_INACTIVE]          = xarML('All Inactive');
    $data['filter'][XARMOD_STATE_UPGRADED]          = xarML('All Upgraded');
    $data['filter'][XARMOD_STATE_UNINITIALISED]     = xarML('Not Installed');
    $data['filter'][XARMOD_STATE_MISSING_FROM_UNINITIALISED] = xarML('Missing (Not Installed)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_INACTIVE] = xarML('Missing (Inactive)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_ACTIVE]   = xarML('Missing (Active)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_UPGRADED] = xarML('Missing (Upgraded)');
    $data['filter'][XARMOD_STATE_ERROR_UNINITIALISED]  = xarML('Update (Not Installed)');
    $data['filter'][XARMOD_STATE_ERROR_INACTIVE]       = xarML('Update (Inactive)');
    $data['filter'][XARMOD_STATE_ERROR_ACTIVE]         = xarML('Update (Active)');
    $data['filter'][XARMOD_STATE_ERROR_UPGRADED]       = xarML('Update (Upgraded)');

    $data['filter'][XARMOD_STATE_CORE_ERROR_UNINITIALISED]  = xarML('Core Conflict (Not Installed)');
    $data['filter'][XARMOD_STATE_CORE_ERROR_INACTIVE]       = xarML('Core Conflict (Inactive)');
    $data['filter'][XARMOD_STATE_CORE_ERROR_ACTIVE]         = xarML('Core Conflict (Active)');
    $data['filter'][XARMOD_STATE_CORE_ERROR_UPGRADED]       = xarML('Core Conflict (Upgraded)');

    $data['sort']['nameasc']                        = xarML('Name [a-z]');
    $data['sort']['namedesc']                       = xarML('Name [z-a]');
    // reset session-based message var
    xarSession::delVar('statusmsg');

    // obtain list of modules based on filtering criteria
    // think we need to always check the filesystem
    if(!xarMod::apiFunc('modules', 'admin', 'regenerate')) return;

    $data['numitems'] = $numitems;
    $itemcountlist = xarMod::apiFunc('modules','admin','getlist',array('filter' => array('State' => $data['selfilter'],'count'=>1)));
    $modlist = xarMod::apiFunc('modules','admin','getlist',array('filter' => array('State' => $data['selfilter'], 'orderby'=> $order, 'sort'=>$sort, 'numitems' =>$numitems,'startnum'=>$startnum)));
    $itemcount = count($itemcountlist);
    // get action icons/images classes
    $icondisabled       = 'xar-icon-disabled ';
    $img_disabled       = 'sprite xs-disabled';
    $img_none           = 'sprite xs-none';
    $img_activate       = 'sprite xs-activate';
    $img_deactivate     = 'sprite xs-deactivate';
    $img_upgrade        = 'sprite xs-software-upgrade';
    $img_initialise     = 'sprite xs-software-install';
    $img_remove         = 'esprite xs-remove';
    $img_warning        = 'sprite xs-dialog-warning';
    $img_error        = 'sprite xs-dialog-error';
    //classes
    $infoclass = 'xar-info';
    $errorclass = 'xar-errorstate';
    $missingclass = 'xar-missing';
    // get other images css class
    $data['infoimg']    = 'sprite xs-info';
    $data['editimg']    = 'sprite xs-hooks';
    $data['dummyimage'] = xarTplGetImage('blank.gif','base');

    $data['listrowsitems'] = array();
    $listrows = array();
    $i = 0;
    $data['order'] = !empty($order) ?$order : (isset($lastquery['order']) ?$lastquery['order']:'name') ;
    $data['sort'] = !empty($sort) ? $sort : (isset($lastquery['sort']) ? $lastquery['sort']:'asc') ;

    $orderclause = $data['order'].','.strtoupper($data['sort']);
    $args['orderclause'] = $orderclause;
    $args['order']= $data['order'];
    $args['sort']= $data['sort'];
    // now we can prepare data for template
    // we will use standard xarMod api calls as much as possible
    // 20/04/2008 - Authentication has class 'Core Authentication' as per Module identification requirements for core
    // now we can rely on the class if we test for substr($dbModules[$name]['class'], 0, 4) == 'Core'
    //$coreMods = array('base','roles','privileges','blocks','themes','authsystem','mail','dynamicdata','installer','modules');

    foreach($modlist as $mod){

        // we're going to use the module regid in many places
        $thismodid = (int)$mod['regid'];
        $listrows[$i]['modid'] = (int)$thismodid;
        // if this module has been classified as 'Core'
        // we will disable certain actions
        $modinfo = xarMod::getInfo($thismodid);

        if(substr($modinfo['class'], 0, 4)  == 'Core'){
            $coremod = true;
        }else{
            $coremod = false;
        }

        // lets omit core modules if a user chosen to hide them from the list
        if($coremod && $data['hidecore']) continue;

        // for the sake of clarity, lets prepare all our links in advance
        $actionargs = array();
        $actionargs['id'] = $thismodid;
        $actionargs['authid'] = $authid;
        if (isset($startnum)) $actionargs['startnum']= $startnum;
        if (isset($numitems)) $actionargs['numitems']= $numitems;
        if (isset($data['order'])) $actionargs['order']= $data['order'];
        if (isset($data['sort'])) $actionargs['sort']= $data['sort'];

        $installurl                = xarModURL('modules','admin','install', $actionargs);
        $deactivateurl              = xarModURL('modules','admin','deactivate',$actionargs);
        $removeurl                  = xarModURL('modules', 'admin','remove',$actionargs);
        $upgradeurl                 = xarModURL('modules','admin','upgrade',$actionargs);

        $errorurl                   = xarModURL('modules','admin','viewerror',$actionargs);

        $hookurl =                  xarModURL('modules','admin','modify',array( 'id' => $thismodid));

        $editurl                    = ($mod['state'] == XARMOD_STATE_ACTIVE || $mod['state'] == XARMOD_STATE_INACTIVE) ?$hookurl:'';

        // link to module main admin function if any
        $listrows[$i]['modconfigurl'] = '';
        $listrows[$i]['configurl'] = '';
        if(isset($mod['admin']) && $mod['admin'] == 1 && $mod['state'] == XARMOD_STATE_ACTIVE){
            $listrows[$i]['modconfigurl'] = xarModURL($mod['name'], 'admin');
            // link title for modules main admin function - common
            $listrows[$i]['adminurltitle'] = xarML('Go to administration of');
        }

        // common urls


        $listrows[$i]['infourl']    = xarModURL('modules','admin','modinfo',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));

        if(isset($mod['admin']) && $mod['admin'] == 1 && $mod['state'] == XARMOD_STATE_ACTIVE){
            $listrows[$i]['configurl'] = xarModURL($mod['name'],
                                        'admin',
                                        'modifyconfig');
        }

        // image urls

        $statelabelicon = '';
        // common listitems
        $listrows[$i]['coremod']        = $coremod;
        $listrows[$i]['name']           = $mod['name'];
        $listrows[$i]['displayname']    = isset($mod['displayname']) ?$mod['displayname'] : $mod['name'];
        $listrows[$i]['version']        = $mod['version'];
        $listrows[$i]['regid']          = (int)$thismodid;
        $listrows[$i]['edit']           = xarML('On/Off');
        $listrows[$i]['prop']           = xarML('Modify');
        $statelabelclass = ''; //initialise
        // conditional data
        if($mod['state'] == XARMOD_STATE_UNINITIALISED){
            // this module is 'Uninitialised' or 'Not Installed' - set labels and links
            $statelabel = xarML('Not Installed');
            $statelabelicon = 'sprite xs-package-available';
            $statelabelclass = '';
            $listrows[$i]['state'] = XARMOD_STATE_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('Install');
            $listrows[$i]['actionurl']          = $installurl;
            $listrows[$i]['actionimg1']         = $img_initialise;

            $listrows[$i]['removeurl']          = '';
            $listrows[$i]['actionlabel2']       = $nalabel;
            $listrows[$i]['actionimg2']         = $icondisabled.$img_remove;//$img_none;

        }elseif($mod['state'] == XARMOD_STATE_INACTIVE){
            // this module is 'Inactive'        - set labels and links
            $statelabel = xarML('Inactive');
            $listrows[$i]['state'] = XARMOD_STATE_INACTIVE;
            $statelabelicon =  'sprite xs-package-inactive';
            $statelabelclass = '';

            $listrows[$i]['actionlabel']        = xarML('Activate');
            $listrows[$i]['actionurl']          = $installurl;
            $listrows[$i]['actionimg1']         = $img_activate;

            $listrows[$i]['removelabel']        = $removelabel;
            $listrows[$i]['actionlabel2']       = $removelabel;
            $listrows[$i]['removeurl']          = $removeurl;
            $listrows[$i]['actionimg2']         = $img_remove;

            $listrows[$i]['configurl']          = '';

        }elseif($mod['state'] == XARMOD_STATE_ACTIVE){
            // this module is 'Active'          - set labels and links
            $statelabel = xarML('Active');
            $statelabelicon =  'sprite xs-package-installed';
             $statelabelclass = '';
            $listrows[$i]['state'] = XARMOD_STATE_ACTIVE;
            // here we are checking for module class
            // to prevent ppl messing with the core modules
            if(!$coremod){
                $listrows[$i]['actionlabel']    = xarML('Deactivate');
                $listrows[$i]['actionurl']      = $deactivateurl;
                $listrows[$i]['actionimg1']     = $img_deactivate;

                $listrows[$i]['actionlabel2']    = $nalabel;
                $listrows[$i]['removeurl']      = '';
                $listrows[$i]['actionimg2']     = $icondisabled.$img_remove;//$img_none;
            }else{
                $listrows[$i]['actionlabel']    = xarML('[core module]');
                $listrows[$i]['actionurl']      = '';
                 $listrows[$i]['actionimg1']     = $img_disabled;

                $listrows[$i]['removeurl']      = '';
                $listrows[$i]['actionlabel2']    = $nalabel;
                $listrows[$i]['actionimg2']     = $img_disabled;
            }
        }elseif($mod['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED){
            // this module is 'Missing'         - set labels and links
            $statelabel = xarML('Missing');
            $listrows[$i]['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
            $statelabelicon =  'sprite xs-package-remove';
            $statelabelclass = $missingclass;
            $listrows[$i]['actionlabel2']       = $removelabel;
            $listrows[$i]['actionlabel']        = xarML('Module is missing');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['removeurl']          = $removeurl;

            $listrows[$i]['actionimg1']         = $img_warning;
            $listrows[$i]['actionimg2']         = $img_remove;

            $listrows[$i]['configurl']          = '';

        }elseif($mod['state'] == XARMOD_STATE_ERROR_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_ERROR_INACTIVE ||
                $mod['state'] == XARMOD_STATE_ERROR_ACTIVE ||
                $mod['state'] == XARMOD_STATE_ERROR_UPGRADED){
            // Bug 1664 - this module db version is greater than file version
            // 'Error' - set labels and links
            $statelabel = xarML('Error');
            $statelabelicon =  'sprite xs-package-broken';
            $statelabelclass = $errorclass;
            $listrows[$i]['state'] = XARMOD_STATE_ERROR_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('View Error');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['actionimg1']         = $img_warning;

            $listrows[$i]['actionlabel2']        = $nalabel;
            $listrows[$i]['removeurl']          = '';
            $listrows[$i]['actionimg2']         = $img_disabled;

        }elseif($mod['state'] == XARMOD_STATE_UPGRADED){
            // this module is 'Upgraded'        - set labels and links
            $statelabel = xarML('New version');
            $statelabelicon =  'sprite xs-package-upgrade';
             $statelabelclass = '';
            $listrows[$i]['state'] = XARMOD_STATE_UPGRADED;
            $listrows[$i]['actionlabel']        = xarML('Upgrade');
            $listrows[$i]['actionimg1']         = $img_upgrade;
            $listrows[$i]['actionurl']          = $upgradeurl;

            $listrows[$i]['removeurl']          = '';
            $listrows[$i]['actionlabel2']       = $nalabel;
            $listrows[$i]['actionimg2']         = $icondisabled.$img_remove;//$img_none;
        }elseif($mod['state'] == XARMOD_STATE_CORE_ERROR_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_CORE_ERROR_INACTIVE ||
                $mod['state'] == XARMOD_STATE_CORE_ERROR_ACTIVE ||
                $mod['state'] == XARMOD_STATE_CORE_ERROR_UPGRADED){
            // this module is incompatible with current core version
            // 'Core Conflict' - set labels and links
            $statelabel = xarML('Core Conflict');
            $statelabelicon =  'sprite xs-package-broken';
             $statelabelclass = '';
            $listrows[$i]['state'] = XARMOD_STATE_CORE_ERROR_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('View Error');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_warning;
            $listrows[$i]['actionimg2']         = $img_remove;
            $listrows[$i]['actionlabel2']        = xarML('View Error');
            $listrows[$i]['actionclass1']         = 'xar-errorstate';
            $listrows[$i]['actionclass2']         = 'xar-remove';

            $listrows[$i]['configurl']          = '';


        } else {
            // Something seriously wrong
            $statelabel = xarML('Unknown');
            $statelabelclass = $errorclass;
            $listrows[$i]['removeurl'] = $removeurl;
            $listrows[$i]['actionimg1'] = $img_warning;
            $listrows[$i]['actionlabel'] = xarML('Remove (Bug! in list generation)');
            $listrows[$i]['actionlabel2'] = xarML('Remove (Bug! in list generation)');
            $listrows[$i]['state'] = $removelabel;
            $listrows[$i]['actionurl'] ='';
            $listrows[$i]['actionimg2'] = $img_remove;

        }
        $listrows[$i]['editurl'] = $editurl;
        // nearly done
        $listrows[$i]['statelabel']     = $statelabel;
        $listrows[$i]['statelabelclass']= $statelabelclass ;
        $listrows[$i]['statelabelicon'] = $statelabelicon;
        $listrows[$i]['removelabel']    =  isset($listrows[$i]['removelabel'])?$listrows[$i]['removelabel']:$removelabel;
        $data['listrowsitems'] = $listrows;
        $i++;
    }

    // total count of items
    $data['totalitems'] = $i;
    // not ideal but would do for now - reverse sort by module names
    if($data['selsort'] == 'namedesc') krsort($data['listrowsitems']);
    $data['totalmods']= count($data['listrowsitems']);
    $data['sortimgclass'] = '';
    $data['sortimglabel'] = '';
    if ($data['sort'] == 'asc') {
        $data['sortimgclass'] = 'esprite xs-sorted-asc';
        $data['sortimglabel'] = xarML('Ascending');
    } else {
        $data['sortimgclass'] = 'esprite xs-sorted-desc';
         $data['sortimglabel'] = xarML('Descending');
    }
    //decide what image goes where
    $sortimage = array();

    $headerarray= array('name','status','regid','status');
    foreach ($headerarray as $headername) {
        $sortimage[$headername] = false;
        if ($data['order'] == $headername) $sortimage[$headername] = true;
    }

    $data['sortimage'] = $sortimage;
    $data['dsort'] = ($data['sort'] == 'asc') ? 'desc' : 'asc';
    if ($order == "status") {
       $cmp = 'cmps';
    } else {
        $cmp = 'cmp';
    }

    usort($data['listrowsitems'],$cmp);
    if ($sort == 'desc') {
        $data['listrowsitems'] = array_reverse($data['listrowsitems']);
        $newsort = 'asc';
        $sortimage = 'sprite xs-sorted-desc';
        $sortlabel = xarML('Sorted Descending');
    } else {
        $newsort = 'desc';
        $sortimage = 'sprite xs-sorted-asc';
        $sortlabel = xarML('Sorted Ascending');
    }
    //common admin menu

    $data['menulinks'] = xarMod::apiFunc('modules','admin','getmenulinks');
    $data['return_url'] = xarServer::getCurrentURL();
    $pagerdata= array();
    $pagerdata['startnum'] = '%%';
    $pagerdata['numitems'] = $numitems;
    $pagerdata['order']    = $data['order'];
    $pagerdata['sort']     = $data['sort'];
    $pagerdata['regen']    = $data['regen'];
    $pagerdata['State']    = $data['selfilter'];

    $data['pager']      = xarTplGetPager($startnum,
                                         $itemcount,
                                         xarModURL('modules', 'admin', 'list', $pagerdata),
                                         $numitems);

    // Send to BL.
    $data['itemcount'] = $itemcount;
    return $data;
}
function cmp($a,$b)
{
    return strcmp($a['name'], $b['name']);
}
function cmps($a,$b)
{
    return strcmp($a['statelabel'], $b['statelabel']);
}

?>
