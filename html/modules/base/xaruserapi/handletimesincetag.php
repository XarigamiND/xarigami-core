<?php
/**
 * Time Since Tag Handler
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
 * ProvHandling for <xar:base-timesince ...>  tags
 *
 * Format: <xar:base-timesince stamp="originaltime" />
 *         stamp attribute has a value of a unix timestamp
 *         Output is a string in years, months, week, days, hours, minutes ago format
 *
 * @author jojodee
 * @param timestamp $stamp
 * @return string the PHP code needed to invoke timesince in the BL template
 */
function base_userapi_handletimesincetag($args)
{
    $out = "echo xarMod::apiFunc('base', 'user', 'timesince',\n";
    $out .= " array(\n";
    foreach ($args as $key => $val) {
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= " '$key' => $val,\n";
        } else {
            $out .= " '$key' => '.$val.',\n";
        }
    }
    $out .= "));";

    return $out;
}

?>