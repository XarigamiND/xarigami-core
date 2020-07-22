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
 * Handle <xar:data-getitem ...> getitem tags
 * Format : <xar:data-getitem name="$properties" module="123" itemtype="0" itemid="$id" fieldlist="$fieldlist" .../>
 *       or <xar:data-getitem name="$properties" object="$object" ... />
 *
 * Use this tag if you want to get an array. If you want an object returned, then use get-items
 * @param $args array containing the module and item that you want to display, or fields
 * @return mixed A string of the PHP code needed to invoke getitemtag() in the BL template and return an array of properties
 */
function dynamicdata_userapi_handleGetItemTag($args)
{
    // if we already have an object, we simply invoke its showView() method
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object' || $key == 'name') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return $args['object'].'->getItem(array('.join(', ',$parts).')); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        } else {
            return $args['object'].'->getItem(); ' .
                   $args['name'] . ' =& '.$args['object'].'->getProperties(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = 'list('.$args['name']. ") = xarMod::apiFunc('dynamicdata',
                   'user',
                   'getitemfordisplay',\n";
    // PHP >= 4.2.0 only
    //$out .= var_export($args);
    $out .= "                   array(\n";
    foreach ($args as $key => $val) {
        if ($key == 'name') continue;
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