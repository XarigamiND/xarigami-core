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
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Handle <xar:data-label ...> label tag
 * Format : <xar:data-label object="$object" /> with $object some Dynamic Object
 *       or <xar:data-label property="$property" /> with $property some Dynamic Property
 *       <xar:data-label property="$property" label="id" /> will use <label for="dd_$property->id">...</label>
 *       <xar:data-label property="$property" label="name" /> will use <label for="$property->name">...</label>
 *       <xar:data-label property="$property" label="something" required="true" /> will use <label for="something">...</label>
 *      <xar:data-label property="$property" label="something" class="someclass" />
 * @param $args array containing the object or property
 * @return string the PHP code needed to show the object or property label in the BL template
 * jojo - this is inconstant and ambiguous usage imho. 'label' is actually being used as the 'for' attribute in the function showLabel
 */
function dynamicdata_userapi_handleLabelTag($args)
{
    if (empty($args['class'])) $args['class'] = '';
    if (!empty($args['object'])) {
        return 'echo xarVarPrepForDisplay('.$args['object'].'->label); ';
    } elseif (!empty($args['property'])) {
        $argsarray = array('for','required','class','label','name','id'); //allowed list of tags
        if (!empty($args['label'])) {
            //we need the label attribute defined to enable passing of attributes to property
            $out =   ' echo '.$args['property'].'->showLabel(array(';
            $aout = '';
            foreach ($args as $key => $val) {
                if (in_array($key,$argsarray)) {
                    if (is_numeric($val) || substr($val,0,1) == '$') {
                        $aout .= " '$key' => \"$val\",";
                    } else {
                        $aout .= " '$key' => '$val',";
                    }
                }
            }
            //clean up a little for output
            $aout = trim($aout);
            $aout = rtrim($aout,',');
            $out = $out.$aout. '));';
            return $out;
        } else {
            return 'echo xarVarPrepForDisplay('.$args['property'].'->label); ';
        }
    } elseif(isset($args['label'])) {
        // Plain label, we want to use the template nevertheless
        $argsstring = "array('label'=>'".$args['label']."'";
        if(isset($args['for'])){
            $argsstring.=",'for'=>'".$args['for']."'";
        }
        if (isset($args['required'])) {
            $argsstring.=",'required'=>'".$args['required']."'";
        }
        if (isset($args['class'])) {
                    $argsstring.=",'class'=>'".$args['class']."'";
        }
        if (isset($args['title'])) {
                    $argsstring.=",'title'=>'".$args['title']."'";
        }
        $argsstring.=")";
        return "echo xarTplProperty('dynamicdata','label','showoutput',$argsstring,'label');";
    } else {
        return 'echo "I need an object or a property or a label attribute"; ';
    }
}

?>
