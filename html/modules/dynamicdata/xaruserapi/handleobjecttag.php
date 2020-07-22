<?php
/**
 * Handle dynamic data tags
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle <xar:data-object ...> object tag
 * Format : <xar:data-object object="$object" property="$property" /> with $object some object and $property some property of this object
 *       or <xar:data-object object="$object" method="$method" arguments="$args" /> with $object some object and $method some method of this object
 *
 * @param $args array containing the object and property/method
 * @return string the PHP code needed to show the object property or call the object method in the BL template
 */
function dynamicdata_userapi_handleObjectTag($args)
{
    if (!empty($args['object'])) {
        if (!empty($args['property'])) {
            return 'echo '.$args['object'].'->'.$args['property'].'; ';
        } elseif (!empty($args['method'])) {
            if (!empty($args['arguments'])) {
                return 'echo '.$args['object'].'->'.$args['method'].'('.$args['arguments'].'); ';
            } else {
                return 'echo '.$args['object'].'->'.$args['method'].'(); ';
            }
        } else {
            return 'echo "I need a property or a method for this object"; ';
        }
    } else {
        return 'echo "I need an object"; ';
    }
}

?>