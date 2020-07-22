<?php

/**
* xarTpl__XarForNode: <xar:for> tag class
 *
 * Takes care of the "for(start, test, iteration) {"  construct
 *
 * @package blocklayout
 * @access private
 */
class xarTpl__XarForNode extends xarTpl__TplTagNode
{
    function __construct($parser, $tagName, $parentTagName='', $parameters=array()) 
    {
        parent::__construct($parser, $tagName, $parentTagName, $parameters);
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }

    function renderBeginTag()
    {
        extract($this->attributes);
        
        if (!isset($start)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'start\' attribute in <xar:for> tag.');
            return;
        }
        
        if (!isset($test)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'test\' attribute in <xar:for> tag.');
            return;
        }
        
        if (!isset($iter)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'iter\' attribute in <xar:for> tag.');
            return;
        }
        
        $start = xarTpl__ExpressionTransformer::transformPHPExpression($start);
        if (!isset($start)) return; // throw back
        
        $test = xarTpl__ExpressionTransformer::transformPHPExpression($test);
        if (!isset($test)) return; // throw back
        
        $iter = xarTpl__ExpressionTransformer::transformPHPExpression($iter);
        if (!isset($iter)) return; // throw back
        
        return "for ($start; $test; $iter) { ";
    }
    
    function renderEndTag()
    {
        return "} ";
    }
}
?>