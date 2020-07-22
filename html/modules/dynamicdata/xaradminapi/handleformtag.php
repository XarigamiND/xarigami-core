<?php
/**
 * Handle form tags
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
/**
 * @todo move this to some common place in Xarigami(base module ?)
 * Handle <xar:data-form ...> form tags
 * Format : <xar:data-form module="123" itemtype="0" itemid="555" fieldlist="$fieldlist" static="yes" ... />
 *       or <xar:data-form fields="$fields" ... />
 *       or <xar:data-form object="$object" ... />
 *
 * @param $args array containing the item for which you want to show a form, or fields
 * @return string the PHP code needed to invoke showform() in the BL template
 */
function dynamicdata_adminapi_handleFormTag($args)
{
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
            return 'echo '.$args['object'].'->showForm(array('.join(', ',$parts).')); ';
        } else {
            return 'echo '.$args['object'].'->showForm(); ';
        }
    }

    $out = "echo xarMod::apiFunc('dynamicdata',
                   'admin',
                   'showform',\n";
    if (isset($args['definition'])) {
        $out .= '                   '.$args['definition']."\n";
        $out .= '                  );';
    } else {
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "                         ));";
    }
    return $out;
}
?>