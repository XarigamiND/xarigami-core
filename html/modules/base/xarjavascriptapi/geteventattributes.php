<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Get JavaScript event attributes for a tag.
 * Returns all submitted JavaScript fragments for a position (tag) and type (list of event names)
 * as a single string. Each attribute will be returned in name="value" format. Only non-empty
 * attributes will be returned.
 *
 * Examples:
 * Add an 'onload' trigger to the page (both examples do the same thing):
 *   <xar:base-include-javascript position="body" type="onload" code="alert('hello, world')" />
 *   xarTplAddJavaScript('body', 'onload', "alert('hello, world')");
 *
 * Get all the event attributes for all body tag events (this can be fetched in a page template):
 *   xarMod::apiFunc('base', 'javascript', 'geteventjs', array('position'=>'body', 'type'=>'onload,onunload'));
 *
 * @author Jason Judge
 * @param $args[position] the location of the event trigger; defaults to 'body'
 * @param $args[type] the type of event trigger; several as a comma-separated list
 * @return string empty string
 */
function base_javascriptapi_geteventattributes($args)
{
    extract($args);

    // Initialise the event attributes string.
    $result = '';

    // Position and type are mandatory.
    // 'position' is the name or ID of the tag ('body', 'mytag', etc.).
    if (empty($position)) {
        // The body tag is the most likely place the events will be used.
        $position = 'body';
    }

    // 'type' is the event types ('onload', 'onmouseup', etc.), supplied
    // as a comma-separated list.
    if (empty($type)) {
        return $result;
    } else {
        $types = explode(',', strtolower($type));
    }

    foreach($types as $type) {
        $js = xarMod::apiFunc(
            'base', 'javascript', 'geteventjs',
            array('position' => $position, 'type' => $type)
        );
        if (!empty($js)) {
            // Format the attribute.
            $result .= ' ' . $type . '="' . htmlspecialchars($js) . '"';
        }
    }

    // Return the result, in the form of a string containing attributes and values.
    return $result;
}

?>