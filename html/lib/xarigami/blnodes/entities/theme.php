<?php

/**
* xarTpl__XarThemeEntityNode
 *
 * Theme variables entities, basically wraps xarThemeGetVar($theme,$varname)
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarThemeEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) < 2) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-theme entity.', $this);
            return;
        }
        $theme= $this->parameters[0];
        $name = $this->parameters[1];
        $transform= isset($this->parameters[2])?$this->parameters[2]:0;

        return "xarThemeVars::get('".$theme."', '".$name."','0', '".$transform."')";
    }

    function needExceptionsControl()
    {
        return true;
    }
}
?>