<?php
/**
 * Handle dynamic data tags
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
// TODO: move this to some common place in Xarigami (base module ?)
 * Handle <xar:data-getitems ...> getitems tags
 * Format : <xar:data-getitems name="$properties" value="$values" module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" .../>
 *       or <xar:data-getitems name="$properties" value="$values" object="$object" ... />
 *
 * This call will return an array containing all items requested as an object and an array of the items in $value
 * @param $args array containing the items that you want to display, or fields
 * @param string value The name for the array to return the values in.
 * @param string name The name for the object to return
 * @return mixed A string of the PHP code needed to invoke getitemstag() in the BL template and return an array of property objects and items
 */
function dynamicdata_userapi_handleGetItemsTag($args)
{
    // if we already have an object, we simply invoke its showView() method
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object' || $key == 'name' || $key == 'value') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return $args['value'] . ' =& '.$args['object'].'->getItems(array('.join(', ',$parts).')); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        } else {
            return $args['value'] . ' =& '.$args['object'].'->getItems(); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = 'list('.$args['name'].','.$args['value'] . ") = xarMod::apiFunc('dynamicdata',
                   'user',
                   'getitemsforview',\n";
    // PHP >= 4.2.0 only
    //$out .= var_export($args);
    $out .= "                   array(\n";
    foreach ($args as $key => $val) {
        if ($key == 'name' || $key == 'value') continue;
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= "                         '$key' => $val,\n";
        } else {
            $out .= "                         '$key' => '$val',\n";
        }
    }
    $out .= "                         ));";
    return $out;
}

?>