<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Handle render javascript form field tags
 * Handle <xar:base-event-javascript ...> form field tags
 * Format : <xar:base-event-javascript definition="$definition" /> with $definition an array
 *       or <xar:base-event-javascript position="head|body|whatever|" type="code|src|whatever|"/>
 * Default position is ''; default type is ''.
 * Typical use in the head section is: <xar:base-render-javascript position="head"/>
 *
 * @author Jason Judge
 * @param array $args['definition']     Form field definition or the type, position, ...
 * @param string $args['position']      Name or ID of the tag ('body', 'mytag', etc.)
 * @param string $args['type']          Type of event ('onload', 'onmouseup', etc.)
 * @param string $args['index']         Unique index  
 * @return string empty string
 */
function base_javascriptapi_handleeventjavascript($args)
{
    extract($args);

    // The whole lot can be passed in as an array.
    if (isset($definition) && is_array($definition)) {
        extract($definition);
    }

    // Position and type are mandatory.
    // 'position' is the name or ID of the tag ('body', 'mytag', etc.).
    // 'type' is the type of event ('onload', 'onmouseup', etc.)
    if (empty($position)) {
        $position = '';
    } else {
        $position = addslashes($position);
    }
    if (empty($type)) {
        $type = '';
    } else {
        $type = addslashes($type);
    }
    if (!isset($index) ||empty($index)) {
        $index = '';
    }
    // Concatenate the JavaScript trigger code fragments.
    // Only pick up the event type JavaScript and send it to the template for display
     $eventjs = xarMod::apiFunc('base', 'javascript', 'geteventjs', array('position'=>'$position', 'type'=>'$type', 'index'=>'$index'));
     if (!empty($eventjs)) {
         return "echo htmlspecialchars(xarTplModule('base', 'javascript', 'event', array('javascript'=>$eventjs, 'position'=>'$position', 'type'=>'$type', 'index'=>'$index'));";
    } else {
        return '';
    }
}

?>