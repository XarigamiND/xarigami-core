<?php
/**
 * Admin panels block management
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * Admin menu block option handler
 * Modify the instance configuration
 * @param $blockinfo array containing title,content
 */
function base_adminmenublock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if(!isset($vars['showlogout'])) $vars['showlogout'] = 0;
    if(!isset($vars['showmarker'])) $vars['showmarker'] = 0;
    if(!isset($vars['menustyle']))  $vars['menustyle'] = 'bycat'; //xarModGetVar('base','menustyle');
    if(!isset($vars['menutype']))  $vars['menutype'] = 'vertical';
    if(!isset($vars['showhelp'])) $vars['showhelp'] = 0;
    if(!isset($vars['allmods'])) $vars['allmods'] = 1; //show all active modules
    if(!isset($vars['modlist'])) $vars['modlist'] =array(13);
    if(empty($vars['showall'])) $vars['showall'] = false; //show all sublinks

    // Set the config values
    $args['showlogout'] = $vars['showlogout'];
    $args['menustyle']  = $vars['menustyle'];
    $args['menutype']  = $vars['menutype'];
    $args['showhelp']   = $vars['showhelp'];
    $args['allmods']    = $vars['allmods'];
    $args['modlist']    = $vars['modlist'];
    $args['showall']    = $vars['showall'];

    // are there any admin modules, then get the whole list sorted by names
    $activemods= xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('AdminCapable' => 1)));
    foreach($activemods as $mod=>$info) {
        if ($info['name'] == 'blocks') {
            $args['activemods'][$info['id']]=$info['name'].xarML(' <span class="xar-error xar-sub"> Required for admin!</span>');
        } else {
            $args['activemods'][$info['id']]=$info['name'];
        }
    }
    $args['menutypes']= array('vertical'    => xarML('Vertical'),
                              'horizontal'  => xarML('Horizontal'),
                              'adminpanel'  => xarML('Horizontal adminpanel'),
                              );
    // Set the template data we need
    $sortorder = array('byname' => xarML('By Name'),
                       'bycat'  => xarML('By Category'));
    $args['sortorder'] = $sortorder;
    $args['blockid'] = $blockinfo['bid'];
    return $args;
}

/**
 * Update the instance configuration
 * @param $blockinfo array containing title,content
 */
function base_adminmenublock_update($blockinfo)
{
    if (!xarVarFetch('showlogout', 'int:0:1', $vars['showlogout'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('menustyle' , 'str::'  , $vars['menustyle'] , 'byname', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('menutype' , 'str::'  , $vars['menutype'] , 'vertical', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showhelp', 'int:0:1', $vars['showhelp'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('allmods', 'int:0:1', $vars['allmods'], 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('modlist', 'isset', $vars['modlist'], array(), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showall', 'checkbox', $vars['showall'], false, XARVAR_NOT_REQUIRED)) return;


    $blockinfo['content'] = $vars;

    return $blockinfo;
}

?>