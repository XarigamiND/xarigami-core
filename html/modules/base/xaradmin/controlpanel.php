<?php
/**
 * Main admin GUI function
 *
 * @package modules
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

/**
 * Main admin gui function
 * @return bool true on success of return to sysinfo
 */
function base_admin_controlpanel($args)
{
// Security Check - don't throw an exception, handle the forbidden page
    if(!xarSecurityCheck('ModerateBase',0)) return ;

    extract($args);
    if (!xarVarFetch('page','str',$page,'',XARVAR_NOT_REQUIRED)) return;
    if (!empty($page)){
        xarTplSetPageTitle($page);
        /* Cache the custom page name so it is accessible elsewhere */
        xarCoreCache::setCached('Base.pages','adminpage',$page);
    } else {
        $pageTemplate = xarModGetVar('base', 'AlternatePageTemplateName');
        if (xarModGetVar('base', 'UseAlternatePageTemplate') != '' && $pageTemplate != '') {
            xarTplSetPageTemplateName($pageTemplate);
        }
        $SiteSlogan = xarModGetVar('themes', 'SiteSlogan');
        xarTplSetPageTitle(xarVarPrepForDisplay($SiteSlogan));
    }
    /* if you want to include different pages in your admin-main template
     * return array('page' => $page);
     * if you want to use different admin-main-<page> templates
     */
    return xarTplModule('base','admin','controlpanel',array(),$page);

    // success
    return true;
}

?>