<?php
/**
 *  Initialise meta block
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * initialise block
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_init()
{
    return array(
        'metakeywords' => '',
        'metadescription' => '',
        'defaultrss' => false,
        'rssurl'    => '',
        'usedk' => '',
        'usegeo' => false,
        'longitude' => '',
        'latitude' => '',
        'copyrightpage' => '',
        'helppage' => '',
        'glossary' => '',
        'extrameta' => '',
        'usesummary' =>0,
        'nocache' => 1, // don't cache by default
        'pageshared' => 0, // if you do, don't share across pages
        'usershared' => 1, // but share for group members
        'cacheexpire' => null);
}

/**
 * get information on block
 *
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function themes_metablock_info()
{
    return array(
        'text_type' => 'Meta',
        'text_type_long' => 'Meta',
        'module' => 'themes',
        'func_update' => 'themes_metablock_update',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display adminmenu block
 *
 * @access  public
 * @param   $blockinfo array containing usegeo, metakeywords, metadescription, longitude, latitude, usedk.
 * @return  data array on success or void on failure
 * @throws  no exceptions
 * @todo    complete
*/
function themes_metablock_display($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }
    $vars['usesummary'] = isset($vars['usesummary']) ? $vars['usesummary'] : 0;
    $vars['metadescription'] = isset($vars['metadescription']) ? $vars['metadescription'] : '';
    $meta = array();

    // Description
    $incomingdesc = xarCoreCache::getCached('Blocks.articles', 'summary');

    if (!empty($incomingdesc) and ($vars['usesummary'] == 1)) { //use articles or use both
        // Strip -all- html
        $htmlless = strip_tags($incomingdesc);
        $meta['description'] = trim($htmlless);
    } else {
        $meta['description'] = $vars['metadescription'];
    }

    // Dynamic Keywords
    $incomingkey = xarCoreCache::getCached('Blocks.articles', 'body');
    $incomingkeys = xarCoreCache::getCached('Blocks.keywords', 'keys');

    if (!empty($incomingkey) and $vars['usedk'] == 1) {
        // Keywords generated from articles module
        $meta['keywords'] = $incomingkey;
    } elseif ((!empty($incomingkeys)) and ($vars['usedk'] == 2)){
        // Keywords generated from keywords module
        $meta['keywords'] = $incomingkeys;
    } elseif ((!empty($incomingkeys)) and ($vars['usedk'] == 3)){
        $meta['keywords'] = $incomingkeys.','.$incomingkey;
    } else {
        $meta['keywords'] = $vars['metakeywords'];
    }

    // Character Set
    $meta['charset'] = xarMLSGetCharsetFromLocale(xarMLSGetCurrentLocale());
    $meta['generator'] = xarConfigGetVar('System.Core.VersionId');
    $meta['generator'] .= ' '.xarConfigGetVar('System.Core.VersionSub');
    $meta['generator'] .= ' - ';
    $meta['generator'] .= xarConfigGetVar('System.Core.VersionNum');

    // Geo Url
    $meta['longitude'] = $vars['longitude'];
    $meta['latitude'] = $vars['latitude'];

    // Active Page
    $vars['defaultrss'] = isset($vars['defaultrss']) ? $vars['defaultrss'] : false;

    $vars['rssurl'] = isset($vars['rssurl'])?$vars['rssurl'] : '';
    if (($vars['defaultrss'] == TRUE) && !empty($vars['rssurl'])) {
        $meta['activepagerss'] =$vars['rssurl'];
    } else {
        $meta['activepagerss'] = xarServer::getCurrentURL(array('theme' => 'rss'));
    }

    $meta['activepageatom'] = xarServer::getCurrentURL(array('theme' => 'atom'));
    $meta['activepageprint'] = xarServer::getCurrentURL(array('theme' => 'print'));

    $meta['baseurl'] = xarServer::getBaseURL();
    if (isset($vars['copyrightpage'])){
        $meta['copyrightpage'] = $vars['copyrightpage'];
    } else {
        $meta['copyrightpage'] = '';
    }

    if (isset($vars['helppage'])){
        $meta['helppage'] = $vars['helppage'];
    } else {
        $meta['helppage'] = '';
    }

    if (isset($vars['glossary'])){
        $meta['glossary'] = $vars['glossary'];
    } else {
        $meta['glossary'] = '';
    }


    //Pager Buttons
    $meta['refreshurl']     = xarCoreCache::getCached('Meta.refresh','url');
    $meta['refreshtime']    = xarCoreCache::getCached('Meta.refresh','time');
    $meta['first']          = xarCoreCache::getCached('Pager.first','leftarrow');
    $meta['last']           = xarCoreCache::getCached('Pager.last','rightarrow');

    $blockinfo['content'] = $meta;
    return $blockinfo;

}

?>