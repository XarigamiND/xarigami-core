<?php
/**
 * Handle style tag
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * Handler for the xar:style tag
 *
 * Attributes:
 * file       - CDATA                   - basename of the style file to include
 * scope     -  [module|(theme)|system] - where to look for it
 * type      - (text/css)               - what content is to be expected
 * media     - (all)                    - for which media are we including style info (space separated list)
 * alternate - [yes|(no)]               - this style is an alternative to the main styling?
 * title     - ""                       - what title can we attach to the styling, if any
 * method    - [(import)|link]          - what method do we use to include the style info
 * condition - [IE|(IE5)|(!IE6)|(lt IE7)] - encase in conditional comment (for serving to ie-win of various flavours)
 *
 * <xar:style file="basename" scope="theme" type="text/css" media="all" alternate="no" title="Great style" method="import" />
 */
function themes_cssapi_registercss($args)
{

    $out = "xarMod::apiFunc('themes', 'user', 'register',\n";
    $out .= " array(\n";
    foreach ($args as $key => $val) {
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= " '$key' => $val,\n";
        } else {
            $out .= " '$key' => '$val',\n";
        }
    }
    $out .= "));";

    return $out;

}

?>