<?php
/**
 * Handle skin var tag

 * @subpackage Xarigami Themes
 * @copyright (C) 2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team

 */
/**
 *
 * <xar:skinvar method="" />
 */
function themes_cssapi_skinvar($args)
{
    $out = "xarSkinFactory::call(\n";
    $out .= " array(\n";
    foreach ($args as $key => $val) {
        if (($b = is_numeric($val)) || strpos($val, '$') !== FALSE) {
            if (!$b && strpos($val, '#') !== FALSE) {
                // Support passing var #$varname#
                $val = str_replace('#$', '${', $val);
                $val = str_replace('#', '}', $val);
                $out .= " '$key' => \"$val\",\n";
            } else if (!$b && strpos($val, '{') !== FALSE) {
                // Should directly support {$varname}
                $out .= " '$key' => \"$val\",\n";
            } else {
                $out .= " '$key' => $val,\n";
            }
        } else {
            $out .= " '$key' => '$val',\n";
        }
    }
    $out .= "));";

    return $out;

}

?>