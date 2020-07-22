<?php
/**
 * Register all css template tags
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 * @author Andy Varganov <andyv@xaraya.com>
 */
/**
 * register all css template tags
 *
 * @author Andy Varganov AndyV_at_Xaraya_dot_Com
 * @param none
 * @returns bool
 */
function themes_cssapi_registercsstags($args)
{
    // just resetting default tags here, nothing else
    // unregister all - just in case they got corrupted or fiddled with via gui
    xarTplUnregisterTag('additional-styles');
    xarTplUnregisterTag('style');
    xarTplUnregisterTag('setskinvar');
    xarTplUnregisterTag('skinvar');

    // use in theme to render all extra styles tags
    xarTplRegisterTag( 'themes', 'additional-styles', array(), 'themes_cssapi_delivercss');

    // Register the tag which is used to include style information
    $cssTagAttributes = array(  new xarTemplateAttribute('file'     , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('scope'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('method'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('module'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('type'     , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('alternate', XAR_TPL_OPTIONAL | XAR_TPL_BOOLEAN),
                                new xarTemplateAttribute('media'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('title'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('source'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('weight'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('version'  , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('condition', XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'style', $cssTagAttributes, 'themes_cssapi_registercss');

    $cssTagAttributes = array(  new xarTemplateAttribute('name'     , XAR_TPL_STRING),
                                new xarTemplateAttribute('value'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('target'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('theme'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'setskinvar', $cssTagAttributes, 'themes_cssapi_setvar',
        XAR_TPL_TAG_HASCHILDREN | XAR_TPL_TAG_ISPHPCODE | XAR_TPL_TAG_HASTEXT | XAR_TPL_TAG_NEEDASSIGNMENT);

   $cssTagAttributes = array(   new xarTemplateAttribute('name'     , XAR_TPL_STRING),
                                new xarTemplateAttribute('method'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('type'     , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('value'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('target'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('theme'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING));

    xarTplRegisterTag( 'themes', 'skinvar', $cssTagAttributes, 'themes_cssapi_skinvar');
   // return
    return true;
}

?>