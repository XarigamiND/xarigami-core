<?php
/**
 * Modify the configuration parameters
 *
 * @package modules
 * @subpackage Xarigami Themes module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 *
 * @author Xarigami Core Development Team
 */
function base_admin_performance()
{
    // Security Check
    if (!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();

    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();

    // everything else happens in Template for now
    // prepare labels and values for display by the template

    $data['submitbutton']   = xarVarPrepForDisplay(xarML('Submit'));

    $data['cssdynamic']     = xarModGetVar('themes', 'dynamic');
    $data['cachefilenumber']     = xarModGetVar('themes', 'cachefilenumber');
    $data['cssaggregate']   = xarModGetVar('themes', 'cssaggregate');
    $data['cssoptimize']    = xarModGetVar('themes', 'cssoptimize');
    $data['jsaggregate']    = xarModGetVar('themes', 'jsaggregate');

    $data['cachecss']       = xarModGetVar('themes', 'cachecss');
    $data['csscachedir']    = trim(xarModGetVar('themes', 'csscachedir'));
    if (empty($data['csscachedir']))  $data['csscachedir'] = './var/cache/styles';
       $data['compress']    = xarModGetVar('themes', 'compress');
    $currentvalues = array('cssaggregate'=> $data['cssaggregate'],
                                   'cssoptimize' =>$data['cssoptimize'],
                                   'jsaggregate'=>$data['jsaggregate']
                                   );
    $data['currentvalues']  = serialize($currentvalues);

    $data['ShowTemplates']  = xarModGetVar('themes', 'ShowTemplates');
    $data['cachetemplates'] = xarConfigGetVar('Site.BL.CacheTemplates');

    $data['flushcachelabel'] = xarML('Flush Cache');
    $data['flushcachevalue'] = isset($flushcachevalue)?$flushcachevalue:'';

    $data['cachedirs'] =array(''            => xarML('None'),
                              'all'         =>  xarML('All'),
                              'templates'   => xarML('Templates'),
                              'rss'         => xarML('RSS'),
                               'styles'     => xarML('CSS and JS')
                            );
    $data['ShowPHPCommentBlockInTemplates'] = xarModGetVar('themes', 'ShowPHPCommentBlockInTemplates');
    $data['ShowTemplates'] = xarModGetVar('themes', 'ShowTemplates');
    $data['cachetemplates'] =xarConfigGetVar('Site.BL.CacheTemplates');

    $data['var_dump'] = xarModGetVar('themes', 'var_dump');
    //check cache files are writeable for templates
    $templateswriteable = is_writeable(sys::varpath().'/cache/templates');

    $debuggroup = xarModGetVar('privileges', 'debuggroup');

    if (!isset($debuggroup) || empty($debuggroup)) {
        $arole = xarFindRole('Administrators');
        if (!isset($arole)) {
            $arole=xarUFindRole('Administrators');
        }
         $debuggroup = $arole->getID();
    }
    $data['debuggroup'] = $debuggroup;
    if (!$templateswriteable) {
        xarSession::setVar('statusmsg.base',xarML('Your template cache at #(1) is not writeable. Please correct this.',sys::varpath().'/cache/templates'));
            //don't change the cache setting in the db - just display it as unset so it's noticed
          $data['cachetemplates'] = false;
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');

    // everything else happens in Template for now
    return $data;
}
?>
