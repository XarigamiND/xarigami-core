<?php
/**
 * Modify Content block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Modify Function to the Blocks Admin
 * @author Jason Judge
 * @param $blockinfo array containing title,content
 */
function base_contentblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    $vars['content_text']  = isset($vars['content_text'] )?$vars['content_text'] :'';
    $vars['content_type']  = isset($vars['content_type'] )?$vars['content_type'] :'text';
    $vars['expire']  = isset($vars['expire'] )?$vars['expire'] :0;
    $vars['start_date']  = isset($vars['start_date'] )?$vars['start_date'] :'';
    $vars['end_date']  = isset($vars['end_date'] )?$vars['end_date'] :'';
    $vars['custom_format']  = isset($vars['custom_format'] )?$vars['custom_format'] :'';
    // Drop-down list defining content type.
    $content_types = array();
    $content_types[] = array('value' => 'text', 'label' => xarML('Text'));
    $content_types[] = array('value' => 'html', 'label' => xarML('HTML'));
    $content_types[] = array('value' => 'blt', 'label' => xarML('HTML/Block Layout'));
    $content_types[] = array('value' => 'php', 'label' => xarML('PHP (echo capture)'));
    $content_types[] = array('value' => 'data', 'label' => xarML('PHP (template data)'));
    $vars['content_types'] = $content_types;

    $vars['bid'] = $blockinfo['bid'];

    return $vars;
}

/**
 * Updates the Block config from the Blocks Admin
 * @param $blockinfo array containing title,content
 */
function base_contentblock_update($blockinfo)
{
    // Ensure content is an array.
    // TODO: remove this once all blocks can accept content arrays.
    if (!is_array($blockinfo['content'])) {
        $blockinfo['content'] = unserialize($blockinfo['content']);
    }

    // Pointer to content array.
    $vars =& $blockinfo['content'];

    //if (!xarVarFetch('expire', 'int', $expire, 0, XARVAR_NOT_REQUIRED)) {return;}
    if (xarVarFetch('content_type', 'pre:lower:passthru:enum:text:html:php:custom:data:blt', $content_type, 'text', XARVAR_NOT_REQUIRED)) {
        $vars['content_type'] = $content_type;
    }

    // TODO: check the flags that allow a posted value to override the existing value.
    if (xarVarFetch('content_text', 'str:1', $content_text, '', XARVAR_NOT_REQUIRED)) {
        $vars['content_text'] = $content_text;
    }

    if (xarVarFetch('hide_errors', 'checkbox', $hide_errors, false, XARVAR_NOT_REQUIRED)) {
        $vars['hide_errors'] = $hide_errors;
    }

    if (xarVarFetch('hide_empty', 'checkbox', $hide_empty, false, XARVAR_NOT_REQUIRED)) {
        $vars['hide_empty'] = $hide_empty;
    }

    if (xarVarFetch('custom_format', 'pre:lower:ftoken:str:0:20', $custom_format, '', XARVAR_NOT_REQUIRED)) {
        $vars['custom_format'] = $custom_format;
    }

    if (xarVarFetch('start_date', 'str', $start_date, '0', XARVAR_NOT_REQUIRED)) {
        // Convert the start date into a datetime format.
        // TODO: is this the way we should be converting dates from the calendar property?
        if (!empty($start_date)) {
            $vars['start_date'] = strtotime($start_date);
        } else {
            $vars['start_date'] = '';
        }
    }

    if (xarVarFetch('end_date', 'str', $end_date, '0', XARVAR_NOT_REQUIRED)) {
        // Convert the end date into a datetime format.
        // TODO: is this the way we should be converting dates from the calendar property?
        if (!empty($end_date)) {
            $vars['end_date'] = strtotime($end_date);
        } else {
            $vars['end_date'] = '';
        }
    }

    return $blockinfo;
}

?>