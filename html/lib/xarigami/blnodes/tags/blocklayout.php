<?php

/**
* xarTpl__XarBlocklayoutNode : blocklayouts root tag
 *
 * xar:blocklayout is the root tage for the blocklayout xml dialect
 *
 * @package blocklayout
 * @access  private
 * @todo check if we are in a page template, and whether we already have the root tag
 */
class xarTpl__XarBlocklayoutNode extends xarTpl__TplTagNode
{
    function __construct($parser,$tagName, $parentTagName='', $attributes=array())
    {
        parent::__construct($parser, $tagName, $parentTagName, $attributes);
        $parser->tagRootSeen = true; // Ladies and gentlemen, we got him!
        $this->hasChildren = true;
        $this->hasText = true;
        $this->isAssignable = false;
    }
    
    function renderBeginTag()
    {
        $content = 'text/html'; // Default content type
        $dtd = '';              // We dont force a DTD if not specified
        extract($this->attributes);
        if(!isset($version)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'version\' attribute in <xar:blocklayout> tag.');
            return;
        }
        
        // Literally copy the content type, charset is determined by MLS
        // FIXME: this explicitly limits to one locale per page, do we want that?
        $docTypeString = DTDIdentifiers::get($dtd);
        // set dtd in globals to load correct core css, among other things
        xarTplSetDoctype($dtd);
        $headercode = '
        xarTpl::setDoctype(\''.$dtd.'\');
        $_bl_locale  = xarMLSGetCurrentLocale();
        $_bl_charset = xarMLSGetCharsetFromLocale($_bl_locale);
        header("Content-Type: ' . $content . '; charset=$_bl_charset");
        echo \''.$docTypeString."';";
        return trim($headercode);
    }
    
    function renderEndTag()
    {
        return ' ';
    }
}
?>