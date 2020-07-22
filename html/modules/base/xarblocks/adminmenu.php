<?php
/**
 * Base block management
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * Initialise block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
function base_adminmenublock_init()
{
    // Nothing to configure...
    // TODO: ...yet
    return array('nocache'      => 1,
                 'showlogout'   => 1,
                 'menustyle'    => 'bycat',
                 'showall'      => false,
                 'showhelp'     => 1);
}

/**
 * Get information on block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_adminmenublock_info()
{
    // Values
    return array(
        'text_type' => 'adminmenu',
        'module' => 'base',
        'text_type_long' => 'Admin Menu',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => false
    );
}

/**
 * Display adminmenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @return  data array on success or void on failure
 * @todo    implement centre menu position
*/
function base_adminmenublock_display($blockinfo)
{

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    $showallmods = isset($vars['allmods']) ?$vars['allmods'] : 1;
    $selectedmodlist = isset($vars['modlist']) ?$vars['modlist'] : array(13);
    $selectedmodlist = join(',',$selectedmodlist);
    //failsafe - in case someone forgot to tick anything in the admin menu block
    if (empty($selectedmodlist)) $showallmods =1;
    // are there any admin modules, then get the whole list sorted by names
    // checking this as early as possible
    if ($showallmods ==1) {
        $mods = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('AdminCapable' => 1)));
    } elseif (!empty($selectedmodlist)) {
        $mods = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('AdminCapable' => 1,'modulelist' => $selectedmodlist)));
    }

    // which module is loaded atm?
    // we need it's name, type and function - dealing only with admin type mods, aren't we?
    list($thismodname, $thismodtype, $thisfuncname) = xarRequest::getInfo();

    // SETTING 1: Show a logout link in the block?
    $showlogout = false;
    if(isset($vars['showlogout']) && $vars['showlogout']) $showlogout = true;
    /// Show a help link
    $showhelp = false;
    if(isset($vars['showhelp'])&& $vars['showhelp']) $showhelp =true;

    // SETTING 2: Menustyle
    if(!isset($vars['menustyle'])) {
        // If it is not set, revert to the default setting
        $vars['menustyle'] = xarModGetVar('modules', 'menustyle');
    }
    // SETTING 3: Menutype
    if(!isset($vars['menutype'])) {
        $vars['menutype'] = 'vertical';
    }
    $showall = false;
    if(isset($vars['showall']) && $vars['showall']) $showall = true;

    // Get current URL for later comparisons because we need to compare
    // xhtml compliant url, we fetch the default 'XML'-formatted URL.
    $currenturl = xarServer::getCurrentURL();

    // Admin types
    // FIXME: this is quite ad-hoc here
    $admintypes = array('admin', 'util');
    switch(strtolower($vars['menustyle'])){
        case 'byname': // display by name
            foreach($mods as $mod) {
                $modname = $mod['name'];
                $labelDisplay = $mod['displayname'];
                $modDescription = $mod['displaydescription'];
                // get URL to module's main function
                $link = xarModURL($modname, 'admin', 'main', array());
                // if this module is loaded we probably want to display it with -current css rule in the menu
                $adminmods[$modname]['features'] = array(
                    'label'     => $labelDisplay,
                    'link'      => $link,
                    'modactive' => 0,
                    'overview'  => 0,
                    'description'=> $modDescription,
                    'maintitle' => xarML('Show administration options for module #(1)', $labelDisplay));

                if ($modname == $thismodname && in_array($thismodtype, $admintypes)) {
                    // this module is currently loaded (active), we need to display
                    // 1. blank label 2. no URL 3. no title text 4. links to module functions, when users looking at default main function
                    // 5. URL with title text, when user is looking at other than default function of this module

                    // adding attributes and flags to each module link for the template
                    if ($thisfuncname != 'main' || $thismodtype != 'admin'){
                        $adminmods[$modname]['features']['overview'] = 1;
                        $adminmods[$modname]['features']['maintitle'] = xarML('Display overview information for module #(1)', $labelDisplay);
                    }
                }
                if (($modname == $thismodname && in_array($thismodtype, $admintypes)) || $showall) {
                    // For active module we need to display the mod functions links
                    // call the api function to obtain function links, but don't raise an exception if it's not there
                    $menulinks = xarMod::apiFunc($modname, 'admin', 'getmenulinks', array(), false);

                    // scan array and prepare the links
                    if (!empty($menulinks)) {
                        foreach($menulinks as $menulink) {
                            $adminmods[$modname]['indlinks'][] = array(
                                'adminlink'     => $menulink['url'],
                                'adminlabel'    => $menulink['label'],
                                'admintitle'    => $menulink['title'],
                                'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                            );
                        }
                    }
                } // if
            } // foreach

            $template = 'verticallistbyname';
            $data = array('adminmods'     => $adminmods, 'catmods'=>array());
            break;

        default:
        case 'bycat': // sort by categories
            // <mrb> for the release we can do without the adminmenu table, if
            // that gains functionality consider putting it back.
            foreach ($mods as $mod) {

                // get URL to module's main function
                $modname=$mod['name'];
                $link = xarModURL($modname, 'admin', 'main', array());
                $labelDisplay = $mod['displayname'];
                $modDescription = $mod['displaydescription'];
                if(!isset($mod['category']) or $mod['category'] == '0') {
                    $mod['category'] = xarML('Unknown');
                }
                $cat = xarVarPrepForDisplay($mod['category']);

                // if this module is loaded we probably want to display it with -current css rule in the menu
                $catmods[$cat][$modname]['features'] = array(
                    'label'     => $labelDisplay,
                    'link'      => $link,
                    'modactive' => 0,
                    'overview'  => 0,
                    'description'=> $modDescription,
                    'maintitle' => xarML('Show administration options for module #(1)', $labelDisplay));
                if ($modname == $thismodname && in_array($thismodtype, $admintypes)) {
                    // this module is currently loaded (active), we need to display
                    // 1. blank label 2. no URL 3. no title text 4. links to module functions, when users looking at default main function
                    // 5. URL with title text, when user is looking at other than default function of this module

                    // adding attributes and flags to each module link for the template
                    $catmods[$cat][$modname]['features']['modactive'] = 1;
                    if ($thisfuncname != 'main' || $thismodtype != 'admin'){
                        $catmods[$cat][$modname]['features']['overview'] = 1;
                        $catmods[$cat][$modname]['features']['maintitle'] = xarML('Display overview information for module #(1)', $labelDisplay);
                    }

                }else {
                    unset($mod['displayname']);
                }
                if (($modname == $thismodname && in_array($thismodtype, $admintypes)) || $showall) {

                    // For active module we need to display the mod functions links
                    // call the api function to obtain function links, but don't raise an exception if it's not there
                    $menulinks = xarMod::apiFunc($modname, 'admin', 'getmenulinks', array(), false);

                    // scan array and prepare the links
                    if (!empty($menulinks)) {
                        foreach($menulinks as $menulink) {
                            $catmods[$cat][$modname]['indlinks'][] = array(
                                'adminlink'     => $menulink['url'],
                                'adminlabel'    => $menulink['label'],
                                'admintitle'    => $menulink['title'],
                                'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                            );
                        }
                    }

                }
            } //inner foreach

            $template = 'verticallistbycats';
            $data = array(
                'catmods'       => isset($catmods)?$catmods:array(),
                'adminmods'     => array()
            );
            break;
    }


    $finaldisplayname = '';
    if ( $vars['menutype'] == 'adminpanel') {
             $template = 'verticallistbycats-top';
             $displayname = xarUserGetVar('name');
             $usedisplay = xarModGetVar('roles','requiredisplayname');
             $username = xarUserGetVar('uname');
             $finaldisplayname = $usedisplay == TRUE && !empty($displayname)? $displayname:$username;
    }
    $data['userdisplayname'] = $finaldisplayname;
    //making a few assumptions here for now about modname and directory
    //very rough - but let's use what we have for now
    //Leave way open for real help system
    //TODO : move any final help functions to some module or api when decided

    if (file_exists('modules/'.$thismodname.'/xaradmin/overview.php')) {
        if ($thisfuncname<>'overview' && $thisfuncname<>'main') {
            $overviewlink=xarModURL($thismodname,'admin','overview',array(),NULL,$thisfuncname);
        } else {
            $overviewlink=xarModURL($thismodname,'admin','overview');
        }
    } else { //no overview exists;
        $overviewlink=xarModURL('base','admin','overview',array('template'=>'nooverview'));
    }
    $data['cplink'] = xarModURL('base','admin','controlpanel');
    $data['overviewlink']=$overviewlink;
    // Set template base.
    // FIXME: not allowed to set private variables of BL directly
    $blockinfo['_bl_template_base'] = $template;

    // Populate block info and pass to BlockLayout.
    $data['showlogout'] = $showlogout;
    $data['showhelp']  =  $showhelp;
    $data['showall']  =  $showall;
    $data['menutype']  =  $vars['menutype'];
    $data['menustyle']  = $vars['menustyle'];
    $blockinfo['content'] = $data;
    return $blockinfo;
}

?>