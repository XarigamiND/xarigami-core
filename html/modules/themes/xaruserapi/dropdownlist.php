<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * get an array of themes (id => field) for use in dropdown lists
 *
 * @returns array
 * @return array of articles, or false on failure
 */
function themes_userapi_dropdownlist($args)
{
    if (empty($class)) {
      $class = 2;//only active themes of type 2
    }
    if (empty($state)) {
      $state = XARTHEME_STATE_ACTIVE;//only active themes
    }
    $filter = array();
    // Get the themes
    $themes = xarMod::apiFunc('themes','admin','getlist',
        array('filter'=>array('Class'=>$class,'State'=>$state))
        );
    if (!$themes) return;

    // Fill in the dropdown list
    $list = array();

    $defaulttheme = xarModGetVar('themes','default');
    foreach ($themes as $theme) {
        if (!isset($theme['name'])) continue;
        $list[$theme['regid']] = xarVarPrepForDisplay($theme['name']);
    }

    return $list;
}

?>
