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
// TODO: move this to some common place in Xarigami (base module ?)
 * Handle <xar:data-view ...> view tags
 * Format : <xar:data-view module="123" itemtype="0" itemids="$idlist" fieldlist="$fieldlist" static="yes" .../>
 *       or <xar:data-view items="$items" labels="$labels" ... />
 *       or <xar:data-view object="$object" ... />
 *
 * @param $args array containing the items that you want to display, or fields
 * @return string the PHP code needed to invoke showview() in the BL template
 */
function dynamicdata_userapi_handleViewTag($args)
{
    // if we already have an object, we simply invoke its showView() method
    if (!empty($args['object'])) {
        if (count($args) > 1) {
            $parts = array();
            foreach ($args as $key => $val) {
                if ($key == 'object') continue;
                if (is_numeric($val) || substr($val,0,1) == '$') {
                    $parts[] = "'$key' => ".$val;
                } else {
                    $parts[] = "'$key' => '".$val."'";
                }
            }
            return 'echo '.$args['object'].'->showView(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showView(); ';
        }
    }

    // if we don't have an object yet, we'll make one below
    $out = "echo xarMod::apiFunc('dynamicdata',
                   'user',
                   'showview',\n";
    // PHP >= 4.2.0 only
    //$out .= var_export($args);
    $out .= "                   array(\n";
    foreach ($args as $key => $val) {
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