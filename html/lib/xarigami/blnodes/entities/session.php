<?php

/**
 * xarTpl__XarSessionEntityNode
 *
 * Session variables entities, wrapps xarSession::getVar()
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarSessionEntityNode extends xarTpl__EntityNode
{
    function render()
    {
        if (count($this->parameters) != 1) {
            $this->raiseError(XAR_BL_MISSING_PARAMETER,'Parameters mismatch in &xar-session entity.', $this);
            return;
        }
        $name = $this->parameters[0];
        return "xarSession::getVar('".$name."')";
    }
}
?>