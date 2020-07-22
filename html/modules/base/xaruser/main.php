<?php
/**
 * Main function
 *
 * @package modules
 * @copyright (C) 2005-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * @author Paul Rosania
 * @param string page The page to use if the admin has enabled different page templates
 * @return mixed
 */
function base_user_main($args)
{
    // Security Check
    if(!xarSecurityCheck('ViewBase')) return;

    /* fetch some optional 'page' argument or parameter */
    extract($args);
    if (!xarVarFetch('page','str',$page,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('tab','str',$tab,'',XARVAR_NOT_REQUIRED)) return;
    $data = array();
    if (!empty($page)){
        xarTplSetPageTitle($page);
        /* Cache the custom page name so it is accessible elsewhere */
        xarCoreCache::setCached('Base.pages','page',$page);
    } else {
        $pageTemplate = xarModGetVar('base', 'AlternatePageTemplateName');
        if (xarModGetVar('base', 'UseAlternatePageTemplate') != '' &&
            $pageTemplate != '') {
            xarTplSetPageTemplateName($pageTemplate);
        } else {
            $page = 'main';
        }
        $SiteSlogan = xarModGetVar('themes', 'SiteSlogan');
        xarTplSetPageTitle(xarVarPrepForDisplay($SiteSlogan));
    }
    if (!empty($tab)) $data['tab'] = $tab;
    if (!empty($page)) $data['page'] = $page;

    /* if you want to include different pages in your user-main template
     * return array('page' => $page);
     * if you want to use different user-main-<page> templates
     */
    return xarTplModule('base','user','main',$data,$page);
}
?>