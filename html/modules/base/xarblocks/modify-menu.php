<?php
/**
 * Menu Block add/modify
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * modify block settings
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_menublock_modify($blockinfo)
{

    if (!xarVarFetch('moveaction', 'str:1:', $moveaction, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('linkid', 'str', $linkid, '0', XARVAR_NOT_REQUIRED)) return;
    // Break out options from our content field
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['style'])) {
        $vars['style'] = 1;
    }

    $vars['displayrss'] = isset($vars['displayrss']) ? $vars['displayrss']: false;
    $vars['displayprint'] = isset($vars['displayprint']) ?  $vars['displayprint']: false;
    $vars['showlogout'] = isset($vars['showlogout']) ?  $vars['showlogout']: true;
    $vars['showlogin'] = isset($vars['showlogin']) ?  $vars['showlogin']: true;
    $vars['showchildren'] = isset($vars['showchildren']) ?  $vars['showchildren']: true;
    $vars['showarticlelinks'] = isset($vars['showarticlelinks']) ? $vars['showarticlelinks']: FALSE;
    $vars['maxarticles'] = isset($vars['maxarticless']) ? $vars['maxarticles']:5;
    $vars['allmods'] = isset($vars['allmods']) ? $vars['allmods']: true;
    if(!isset($vars['menutype']))  $vars['menutype'] = 0;
    if(!isset($vars['modtitle']))  $vars['modtitle'] = '';
    if (empty($vars['marker'])) {
        $vars['marker'] = '[x]';
    }

   if(!isset($vars['modlist'])) $vars['modlist'] =array();

    //are there any user modules, then get the whole list sorted by names
    $activemods= xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('UserCapable' => 1)));

    foreach($activemods as $mod=>$info) {
        if ($info['name'] == 'blocks') {
            $vars['activemods'][$info['id']]=$info['roles'].xarML(' <span class="xar-error">Are you sure? May be required for login.</span>');
        } else {
            $vars['activemods'][$info['id']]=$info['name'];
        }
    }
    $bid =     $blockinfo['bid'];
    // Prepare output array
    $c=0;

    if (!empty($vars['content'])) {
        $contentlines = explode("LINESPLIT", $vars['content']);
        $totallinks = count($contentlines);
        $vars['contentlines'] = array();
       // foreach ($contentlines as $contentline) {
       $auth = xarSecGenAuthKey();
        for ($i = 0; $i <  $totallinks; $i++) {
            $link = explode('|', $contentlines[$i]);

             //do link reorder
            if ($i <> 0 ) {
                $position['upurl'] = xarModURL('blocks', 'admin', 'modify_instance',array('bid' => $bid,'linkid'=>$i, 'moveaction' => 'up','authid'=>$auth)
                );
            } elseif ($i ==0) {
                $position['upurl'] = '';
                $position['uptitle'] = '';
            } else {
                $position['upurl'] = '';
            }
            $position['uptitle'] = xarML('Move Up');
            if ($i <>$totallinks-1) {
               $position['downurl'] = xarModURL('blocks', 'admin', 'modify_instance',  array('bid' => $bid, 'linkid'=>$i,'moveaction' => 'down','authid'=>$auth)
                );
                $position['downtitle'] = xarML('Move Down');
            } elseif ($i == $totallinks-1) {
                $position['downurl'] = '';
                $position['downtitle'] = '';
            } else {
                $position['downurl'] = '';
                $position['downtitle'] = xarML('Move Down');
            }
            $link[4] = $position;
            $link['position'] = $i;
            $links[] = $link;
            $c++;
        }

        //check we didn't get some reordering
        if (!empty($moveaction)) {
            $currentorderid=$linkid;
            foreach ($links as $link =>$linkitem) {
                // some fiddling - array starts with zero ..
                if (($linkitem['position'] == $currentorderid) && strtolower($moveaction) == 'up') {
                    // We need to find the position  before (less)
                    $temp = $links[$link-1];
                    $links[$link-1] = $linkitem;
                    $links[$link-1]['position'] = $link-1;
                    $links[$link] = $temp;
                    $links[$link]['position'] = $link;
                    $swapposition = $link;
                } elseif (($linkitem['position']  == $currentorderid) && strtolower($moveaction) == 'down') {
                    // We need to find the position after (more)
                    $temp = $links[$link+1];
                    $links[$link+1] = $linkitem;
                    $links[$link+1]['position'] = $link+1;
                    $links[$link] = $temp;
                    $links[$link]['position'] = $link;
                }
            }
            //clean it up
            $newlinks = array();
            foreach ($links as $link) {
                $newlinks[] = implode('|',array($link[0],$link[1],$link[2],$link[3]));
            }

            $vars['content'] = implode("LINESPLIT", $newlinks);
            $blockinfo['content'] = $vars;
            //update the block
            $blockinfo['directreturn'] = 1;
            xarModAPIFunc('blocks','admin','update_instance',$blockinfo);
            return;
        }
        $vars['contentlines'] = $links;
    }
    $vars['menutypes'] = array(
                         array('id' => 0, 'name' => xarML('Standard (backward compatible)')),
                         array('id' => 1, 'name' => xarML('Horizontal')),
                         array('id' => 2, 'name' => xarML('Vertical')),
                        //later .. array('id' => 3, 'name' => xarML('Horizontal navbar'))
                         );
    $vars['blockid'] = $blockinfo['bid'];

    return $vars;
}

/**
 * update block settings
 *
 * @access  public
 * @param   $blockinfo
 * @return  $blockinfo data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_menublock_update($blockinfo)
{

     // Global options.
    if (!xarVarFetch('showlogout', 'checkbox', $vars['showlogout'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showlogin', 'checkbox', $vars['showlogin'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayrss', 'checkbox', $vars['displayrss'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('displayprint', 'checkbox', $vars['displayprint'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('marker', 'str:1', $vars['marker'], '[x]', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('allmods', 'checkbox', $vars['allmods'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('modlist', 'isset', $vars['modlist'], array(), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('menutype' , 'int:0:3'  , $vars['menutype'] , 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('modtitle' , 'str::'  , $vars['modtitle'] , '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showchildren', 'checkbox', $vars['showchildren'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showarticlelinks', 'checkbox', $vars['showarticlelinks'], false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('maxarticles' , 'int:0'  , $vars['maxarticles'] , 0, XARVAR_NOT_REQUIRED)) return;
    // User links.
    $content = array();
    $c = 0;
    if (!xarVarFetch('linkname', 'array', $linkname, NULL, XARVAR_NOT_REQUIRED)) return;
    if (isset($linkname)) {
        if (!xarVarFetch('linkurl',  'list:str', $linkurl,  NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('linkdesc',  'list:str', $linkdesc,  NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('linkchild', 'list:str', $linkchild, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('linkdelete', 'list:checkbox', $linkdelete, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('linkinsert', 'list:checkbox', $linkinsert, NULL, XARVAR_NOT_REQUIRED)) return;

        foreach ($linkname as $v) {
            if (!isset($linkdelete[$c]) || $linkdelete[$c] == false) {
                // FIXME: MrB, i added the @ to avoid testing whether all fields contains something useful
                @$content[] = "$linkurl[$c]|$linkname[$c]|$linkdesc[$c]|$linkchild[$c]";
            }
            if (!empty($linkinsert[$c])) {
                $content[] = "||";
            }
            $c++;
        }
    }

    if (!xarVarFetch('new_linkname', 'str', $new_linkname, '', XARVAR_NOT_REQUIRED)) return;
    if (!empty($new_linkname)) {
        if (!xarVarFetch('new_linkurl', 'str', $new_linkurl, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('new_linkdesc', 'str', $new_linkdesc, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('new_linkchild', 'str', $new_linkchild, '', XARVAR_NOT_REQUIRED)) return;

        $content[] = $new_linkurl . '|' . $new_linkname . '|' . $new_linkdesc . '|' . $new_linkchild;
    }
    if (!xarVarFetch('new_linkinsert', 'checkbox', $new_linkinsert, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('links_position', 'int:0:3', $link_position, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('links_relation', 'int:0:', $link_relation, 0, XARVAR_NOT_REQUIRED)) return;

    if (!empty($new_linkinsert)) {
        $content[] = "||";
    }
    if ($vars['allmods'] == TRUE) {
       $vars['modlist'] = array();
    }

    $vars['content'] = implode("LINESPLIT", $content);

    $blockinfo['content'] = $vars;

    return($blockinfo);
}

?>