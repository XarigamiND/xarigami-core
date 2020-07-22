<?php
/**
 * Menu Block
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * initialise block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function base_menublock_init()
{
    return array(
        'allmods' => true,
        'modlist' => '',
        'displayprint' => true,
        'displayrss' => false,
        'showlogout'=> true,
        'showlogin'=> true,
        'menutype'=> 0,
        'showadmin'=>0,
        'showchildren'=>0,
        'showarticlelinks'=>0,
        'maxarticles'=>0,
        'marker' => '[x]',
        'modtitle' => xarML('Modules'),
        'content' => 'http://xarigami.org/|Xarigami|Xarigami Application Development Framework|',
        'nocache' => 1, // don't cache by default
        'pageshared' => 0, // don't share across pages (depending on dynamic menu or not)
        'usershared' => 1, // share for group members
        'cacheexpire' => null);
}

/**
 * get information on block
 *
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_menublock_info()
{
    return array(
        'text_type' => 'Menu',
        'text_type_long' => 'Generic menu',
        'module' => 'base',
        'func_update' => 'base_menublock_insert',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display usermenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  data array on success or void on failure
 * @throws  no exceptions
 * @todo    implement centre and right menu position
*/
function base_menublock_display($blockinfo)
{

    // Break out options from our content field
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    $vars['showlogout'] = isset($vars['showlogout']) ? $vars['showlogout']: FALSE;
    $vars['showlogin'] = isset($vars['showlogin']) ? $vars['showlogin']: FALSE;
    $vars['showadmin'] = isset($vars['showadmin']) ? $vars['showadmin']: FALSE;
    $vars['showchildren'] = isset($vars['showchildren']) ? $vars['showchildren']: FALSE;
    $vars['showarticlelinks'] = isset($vars['showarticlelinks']) ? $vars['showarticlelinks']: FALSE;
    $vars['maxarticles'] = isset($vars['maxarticless']) ? $vars['maxarticles']:5;
    $vars['displayprint'] = isset($vars['displayprint']) ? $vars['displayprint']: FALSE;
    $vars['displayrss'] = isset($vars['displayrss']) ? $vars['displayrss']: FALSE;
    $vars['allmods'] = isset($vars['allmods']) ? $vars['allmods']: FALSE;

    if(!isset($vars['modtitle'])) {
        $vars['modtitle'] = '';
    }
    if(!isset($vars['menutype'])) {
        $vars['menutype'] = 0;
    }
    $modtitle = $vars['modtitle'];
    $mods = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('UserCapable' => 1)));
    if(empty($mods)) {
       // there aren't any user capable modules, dont display user menu
      return;
    }

    $vars['modlist'] = isset($vars['modlist']) && is_array($vars['modlist'])?$vars['modlist'] : array();
    //let's get the selected mods from our existing user capable module list
    $selectedmods= array();
    if (!empty($vars['modlist']) && !empty($mods)) {
        foreach ($vars['modlist']  as $modregid)
        {
            foreach ($mods as $mod=>$modinfo)
            {
                if ($modregid == $modinfo['id']) {
                        $selectedmods[] =$mods[$mod];
                }
            }
        }

        $mods = $selectedmods; //assign back to $mods array for backward compatibility
    }
    //otherwise $mods is the full module capable list
    if (empty($vars['marker'])) {
        $vars['marker'] = '[x]';
    }

    $marker = $vars['marker'];

    // which module is loaded atm?
    // we need it's name, type and function - dealing only with user type mods, aren't we?
    // This needs to be deprecated for multi-modules setups later on
    list($thismodname, $thismodtype, $thisfuncname) = xarRequest::getInfo();

    // Sort Order, Status, Common Labels and Links Display preparation
    $logoutlabel = xarVarPrepForDisplay(xarML('Logout'));
    $loginlabel = xarVarPrepForDisplay(xarML('Login'));
    $logouturl = xarModURL('authsystem','user', 'logout', array());
    $loginurl = xarModURL('authsystem','user', 'showloginform', array());
    $loggedin = xarUserIsLoggedIn();

    // Get current URL
    $truecurrenturl = xarServer::getCurrentURL(array(), false);
    $currenturl = xarServer::getCurrentURL();
    $currenturi = xarServer::getCurrentURI();
    $usermods = array();
    $tempmodlines = array();
    // Added list of modules if selected.
    if (($vars['allmods'] == true) ||  !empty($vars['modlist'])) {

        //we need to display the modules in the $mods array for all, or the user selected modlist
        //both should now be in the $mods array
        if (xarSecurityCheck('ReadBlock',0,'Block',"base:menu:$blockinfo[name]")) {
           $useAliasName=0;
           $aliasname='';

            foreach($mods as $mod){
                /* Check for active module alias */
                /* jojodee -  We need to review the module alias functions and, thereafter it's use here */
                $useAliasName=xarModGetVar($mod['name'], 'useModuleAlias');
                $aliasname= xarModGetVar($mod['name'],'aliasname');
                /* use the alias name if it exists for the label */

                if (isset($useAliasName) && $useAliasName==1 && isset($aliasname) && !empty($aliasname)) {
                    $label = ucfirst($aliasname);
                } else {
                    $label = isset($mod['displayname']) ? $mod['displayname'] :$mod['name'];
                }

                $title = $label;
                $link = xarModURL($mod['name'] ,'user', 'main', array());
            $tempmodlines[] = implode('|',array("[{$mod['name']}]",$label,$title));


                // depending on which module is currently loaded we display accordingly
                if($mod['name'] == $thismodname && $thismodtype == 'user'){

                    // Get list of links for modules
                    $labelDisplay = $label;
                    $usermods[] = array(   'label'     => $labelDisplay,
                                           'link'      => '',
                                           'desc'      => $title,
                                           'modactive' => 1);

                    // Lets check to see if the function exists and just skip it if it doesn't
                    // with the new api load, it causes some problems.  We need to load the api
                    // in order to do it right.
                    xarMod::apiLoad($mod['name'], 'user');
                    if (function_exists($label.'_userapi_getmenulinks') ||
                        file_exists("modules/$mod[osdirectory]/xaruserapi/getmenulinks.php")){
                        // The user API function is called.
                        $menulinks = xarMod::apiFunc($mod['name'],  'user', 'getmenulinks');
                    } else {
                        $menulinks = '';
                    }

                    if (!empty($menulinks)) {
                        $indlinks = array();
                        foreach($menulinks as $menulink){

                            // Compare with current URL
                            if ($menulink['url'] == $currenturl) {
                                $funcactive = 1;
                            } else {
                                $funcactive = 0;
                            }

                // Security Check
//                           if (xarSecurityCheck('ReadBaseBlock',0,'Block',"$blockinfo[title]:$menulink[title]:All")) {
                                $indlinks[] = array('userlink'      => $menulink['url'],
                                                    'userlabel'     => $menulink['label'],
                                                    'usertitle'     => $menulink['title'],
                                                    'funcactive'    => $funcactive);
                            }
//                                    }
                    } else {
                        $indlinks= '';
                    }

                }else{
                 $labelDisplay = $label;
                    $usermods[] = array('label' => $labelDisplay,
                                        'link' => $link,
                                        'desc' => $title,
                                        'modactive' => 0);
                }
            }
        } else {
            $modid = xarMod::getId('roles');
            $modinfo = xarMod::getInfo($modid);
            if ($modinfo){
                $title = $modinfo['description'];
            } else {
                  $title = xarML('No description');
            }
            $usermods[] = array('label' => xarMod::getDisplayableName('roles'),
                'link' => xarModUrl('roles', 'user', 'main'),
                'desc' => xarMod::getDisplayableDescription('roles'),
                'modactive' => 0);
        }
    } else {
        $usermods = '';
    }

    $articlelinks = array();
    // Added Content For non-modules list.
    if (!(empty($vars['content']) && empty($tempmodlines))) {
        $usercontent = array();
        $contentlines = explode("LINESPLIT", $vars['content']);
        //what about user mods? add them?
        if ($vars['menutype'] != 0) {
            $contentlines = array_merge($contentlines,$tempmodlines);
        }
        foreach ($contentlines as $contentline) {
            //list($url, $title, $comment, $child) = explode('|', $contentline);
            // FIXME: make sure we don't generate content lines with missing pieces elsewhere
            $parts = explode('|', $contentline);
            $url = $parts[0];

            // FIXME: this probably causes bug #3393
            $here = (substr($truecurrenturl, -strlen($url)) == $url) ? 'true' : '';
            $modtemp = '';
            $urltemp = '';
            $menulinks= '';

            if (!empty($url)){
                switch ($url[0])
                {
                    case '[': // module link
                    {
                        $modtemp = $url;
                        // Credit to Elek M???ton for further expansion
                        $sections = explode(']',substr($url,1));
                        $url = explode(':', $sections[0]);
                        // if the current module is active, then we are here
                        $basemodurl = xarConfigGetVar('BaseModURL');
                        if (!isset($basemodurl)) $basemodurl = 'index.php';
                        if ($url[0] == 'home') { //assumes no module called home
                           if (($currenturl == xarServer::getBaseURL())
                              || ($currenturl == xarServer::getBaseURL().$basemodurl)) {
                           $here = 'true';
                           }
                           $url = xarServer::getBaseURL();
                        } else {
                            if ($url[0] == $thismodname &&
                                (!isset($url[1]) || $url[1] == $thismodtype) &&
                                (!isset($url[2]) || $url[2] == $thisfuncname)) {
                                $here = 'true';
                            }
                            if (empty($url[1])) $url[1]="user";
                            if (empty($url[2])) $url[2]="main";
                            $urltemp = $url;
                            $url = xarModUrl($url[0],$url[1],$url[2]);

                            if(isset($sections[1])) {
                                $url .= xarVarPrepForDisplay($sections[1]);
                            }
                        }
                        break;
                    }
                    case '{': // article pubtype link
                    {
                        $modtemp = $url;
                        $url = explode(':', substr($url, 1,  - 1));
                        $tempurl = $url;
                        // Get current pubtype type (if any)
                        if (xarCoreCache::isCached('Blocks.articles', 'ptid')) {
                            $ptid = xarCoreCache::getCached('Blocks.articles', 'ptid');
                        }
                        if (empty($ptid)) {
                            // try to get ptid from input
                            xarVarFetch('ptid', 'isset', $ptid, NULL, XARVAR_DONT_SET);
                        }
                        // if the current pubtype is active, then we are here
                        if ($url[0] == $ptid) {
                            $here = 'true';
                        }
                        $url = xarModUrl('articles', 'user', 'view', array('ptid' => $url[0]));
                        break;
                    }
                    case '(': // category link
                    {
                        $modtemp = $url;
                        $url = explode(':', substr($url, 1,  - 1));
                        if (xarCoreCache::isCached('Blocks.categories','catid')) {
                            $catid = xarCoreCache::getCached('Blocks.categories','catid');
                        }
                        if (empty($catid)) {
                            // try to get catid from input
                            xarVarFetch('catid', 'isset', $catid, NULL, XARVAR_DONT_SET);
                        }
                        if (empty($catid) && xarCoreCache::isCached('Blocks.categories','cids')) {
                            $cids = xarCoreCache::getCached('Blocks.categories','cids');
                        } else {
                            $cids = array();
                        }
                        $catid = str_replace('_', '', $catid);
                        $ancestors = xarMod::apiFunc('categories','user','getancestors',
                                                  array('cid' => $catid,
                                                        'cids' => $cids,
                                                        'return_itself' => true));
                        if(!empty($ancestors)) {
                            $ancestorcids = array_keys($ancestors);
                            if (in_array($url[0], $ancestorcids)) {
                                // if we are on or below this category, then we are here
                                $here = 'true';
                            }
                        }
                        $url = xarModUrl('articles', 'user', 'view', array('catid' => $url[0]));
                        break;
                    }
                    case '#': // reference link
                        $url = $currenturi . $url;
                        // no break - fall back to standard URL

                    default : // standard URL
                        // BUG 2023: Make sure manual URLs are prepped for XML, consistent with xarModURL()
                        if (!empty($GLOBALS['xarMod_generateXMLURLs'])) {
                            $url = xarVarPrepForDisplay($url);
                        }
                }
            }
            $title = isset($parts[1])?$parts[1]:'';
            $comment = isset($parts[2])?$parts[2]:'';
            if (isset($vars['showchildren']) && $vars['showchildren'] == TRUE) {
                $child = isset($parts[3]) ? $parts[3] : '';
            } else {
                $child = '';
            }
            // Security Check
            //FIX: Should contain a check for the particular menu item
            //     Like "menu:$blockinfo[title]:$blockinfo[bid]:$title"?
            if (xarSecurityCheck('ReadBlock',0,'Block',"base:menu:$blockinfo[name]")) {
                $title = xarVarPrepForDisplay($title);
                $comment = xarVarPrepForDisplay($comment);
                $child = xarVarPrepForDisplay($child);
                $usercontent[] = array('title' => $title, 'url' => $url, 'comment' => $comment, 'child'=> $child, 'here'=> $here);
            }

            //what about child links for this if a mod with short cut url
            if ($vars['showchildren'] == TRUE AND $vars['menutype'] > 0) {
                if (!empty($modtemp)) {
                   switch ($modtemp[0])
                   {
                        case '[':
                        {
                           //what about child links?
                            if (isset($urltemp[0]) && file_exists(sys::code()."modules/$urltemp[0]/xaruserapi/getmenulinks.php")){
                                // The user API function is called.
                                $menulinks = xarMod::apiFunc($urltemp[0], 'user', 'getmenulinks');
                            }
                            if (!empty($menulinks)) {
                                foreach($menulinks as $menulink){
                                    // Compare with current URL
                                    if ($menulink['url'] == $currenturl) {
                                        $here= 1;
                                    } else {
                                        $here = 0;
                                    }
                                    $usercontent[] = array('title'=>$menulink['label'], 'url'=>$menulink['url'], 'comment'=>$menulink['title'], 'child'=>1, 'here'=>$here);
                                }
                            }
                            break;
                        }
                        case '{': //pubtype
                        {
                            if ($vars['showarticlelinks']== TRUE && $vars['maxarticles'] > 0) {
                                $ptids= explode('}',substr($modtemp,1));
                                $ptid = (int)$ptids[0];
                                $settings = array();
                                try {
                                    $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
                                } catch (Exception $e) {

                                }
                                $sort = $settings['defaultsort']? $settings['defaultsort']: 'date';
                                $articlelinks = xarModAPIFunc('articles','user','getall',array('numitems'=>$vars['maxarticles'], 'ptid'=>$ptid, 'status' => array(2,3), 'sort'=>$sort));


                                if (count($articlelinks)>0) {
                                    foreach ($articlelinks as $k=>$article)
                                    {
                                       $articleurl = xarModURL('articles','user','display',array('aid'=>$article['aid'],'ptid'=>$article['pubtypeid']));
                                        if ( $articleurl == $currenturl) {
                                            $here= 1;
                                        } else {
                                            $here = 0;
                                        }
                                        $usercontent[] = array('title'=>$article['title'], 'url'=>$articleurl, 'comment'=>$article['title'], 'child'=>1, 'here'=>$here);
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }
    } else {
        $usercontent = '';
    }

    // prepare the data for template(s)
    $menustyle = xarVarPrepForDisplay(xarML('[by name]'));
    if (empty($indlinks)){
        $indlinks = '';
    }


    $rssurl         = xarServer::getCurrentURL(array('theme' => 'rss'));
    $printurl       = xarServer::getCurrentURL(array('theme' => 'print'));
    //create nested array with children for new layout
    //we know the existing array list is ordered with any top level followed directly by child list items (marked with child true)
    $parents = array();
    if (!empty($usercontent)) {
        $i=0;
        $j=0;
        $linknum = count($usercontent);
        for ($i = 0; $i <= $linknum; $i++) {
            if (!isset($usercontent[$i])) continue;
            $link = $usercontent[$i];
            $link['comment'] = !isset($link['comment']) || empty($link['comment']) ?$link['title']:'';
            if (isset($link['url']) && !empty($link['url'])) {
               $temp = array();
               if ($i==0 || !isset($link['child']) || $link['child'] !=1) {
                $j++;
                    $temp  = $link;
                    $temp['sublinks'] = array();
                    $parents[$j] = $temp; //we have a parent link

                    continue; //the first link in the array cannot be a child link
               }
               //we only have child one level deep at the moment
               if (isset($link['child']) && $link['child']==1)
               {
                    $temp =$link;

                    $temp['sublinks'] =array(); //maybe later
                    $parents[$j]['sublinks'][]=$temp;
               }
            }
        }
    }

    //add extra links
   // we dont want to show logout link if the user is anonymous or admin [BACKWARD COMPAT MENU ONLY]
    // admins have their own logout method, which is more robust
    // Security Check
    $showlogin = FALSE;
    $showlogout = FALSE;
    if (!xarUserIsLoggedIn() && $vars['showlogin'] == TRUE) $showlogin = TRUE;
    if (xarUserIsLoggedIn() && $vars['showlogout'] == TRUE) $showlogout = TRUE;


    $data = array(
        'usermods'         => $usermods,
        'indlinks'         => $indlinks,
        'logouturl'        => $logouturl,
        'loginurl'        => $loginurl,
        'logoutlabel'      => $logoutlabel,
        'loggedin'         => $loggedin,
        'loginlabel'      => $loginlabel,
        'usercontent'      => $usercontent,
        'marker'           => $marker,
        'modtitle'         => $modtitle,
        'showlogout'       =>  $showlogout,
        'showlogin'       =>  $showlogin,
        'where'            => $thismodname,
        'what'             => $thisfuncname,
        'displayrss'       => $vars['displayrss'],
        'displayprint'     => $vars['displayprint'],
        'printurl'         => $printurl,
        'rssurl'           => $rssurl,
        'parents'           => $parents,
        'showchildren'      => $vars['showchildren'],
        'showarticlelinks' => $vars['showarticlelinks'],
        'maxarticles'       => $vars['maxarticles'],
        'menutype'          =>$vars['menutype'],
        'showadmin'         => $vars['showadmin'],

    );

    // Populate block info and pass to BlockLayout.
    $blockinfo['content'] = $data;

    return $blockinfo;

}

?>
