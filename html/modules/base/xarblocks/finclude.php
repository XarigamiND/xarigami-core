<?php
/**
 * Finclude block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Block init - holds security.
 * @author Patrick Kellum
 */
function base_fincludeblock_init()
{
    return array(
        'url' => 'http://www.example.com/',
        'nocache' => 0, // cache by default
        'pageshared' => 1, // share across pages here
        'usershared' => 1, // and for group members
        'cacheexpire' => null
    );
}

/**
 * Block info array
 */
function base_fincludeblock_info()
{
    return array('text_type' => 'finclude',
         'text_type_long' => 'Simple File Include',
         'module' => 'base',
         'func_update' => 'base_fincludeblock_update',
         'allow_multiple' => true,
         'form_content' => false,
         'form_refresh' => false,
         'show_preview' => true);
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_display($blockinfo)
{

    if (!is_array($blockinfo['content'])) {
        $blockinfo['content'] = unserialize($blockinfo['content']);
    } else {
        $blockinfo['content'] = $blockinfo['content'];
    }

    if (empty($blockinfo['content']['url'])){
        $blockinfo['content'] = xarML('Block has no file defined to include');
    } else {
        $blockinfo['url'] = $blockinfo['content']['url'];
        if (!file_exists($blockinfo['url'])) {
            $blockinfo['content'] = xarML('Warning: File to include does not exist. Check file definition in finclude block instance.');
        } else {
            $blockinfo['content'] = implode('', file($blockinfo['url']));
        }
    }

    return $blockinfo;
}

/**
 * Modify Function to the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_modify($blockinfo)
{
    if (!empty($blockinfo['url'])) {
        $args['url'] = $blockinfo['url'];
    } else {
        $args['url'] = '';
    }
    $args['blockid'] = $blockinfo['bid'];

    return $args;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_fincludeblock_update($blockinfo)
{
    $vars = array();
    if (!xarVarFetch('url', 'isset', $vars['url'], xarML('Error - No Url Specified'), XARVAR_DONT_SET)) {return;}

    $blockinfo['content'] = $vars;
    return $blockinfo;
}

?>
