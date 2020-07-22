<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update the configuration parameters of the
 * module given the information passed back by the modification form
 */
function base_admin_updateperformance()
{
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;
    // Security Check
    if (!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();

    //get some current values
    $oldcssaggregate   = xarModGetVar('themes', 'cssaggregate');
    $oldcssoptimize    = xarModGetVar('themes', 'cssoptimize');
    $oldjsaggregate    = xarModGetVar('themes', 'jsaggregate');
    $currentvalues = array('cssaggregate' =>  $oldcssaggregate,
                           'cssoptimize'  => $oldcssoptimize,
                           'jsaggregate'  => $oldjsaggregate
                            );

    // Get parameters
    if (!xarVarFetch('csscachedir', 'str', $csscachedir, null, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cachetemplates', 'checkbox', $cachetemplates, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('compress', 'checkbox', $compress, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('cachefilenumber', 'int', $cachefilenumber,100, XARVAR_NOT_REQUIRED)) return;

    if (!xarVarFetch('showphpcbit', 'checkbox', $showphpcbit, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('showtemplates', 'checkbox', $showtemplates, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('var_dump', 'checkbox', $var_dump, FALSE, XARVAR_NOT_REQUIRED)) return;

    //flush cache
    if (!xarVarFetch('flushcache', 'pre:lower:passthru:enum:all:templates:rss:adodb:styles', $flushcache, '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('cssoptimize', 'checkbox', $cssoptimize, FALSE, XARVAR_DONT_SET)) {return;}

    if (!xarVarFetch('jsaggregate', 'checkbox', $jsaggregate, FALSE, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('cssaggregate', 'checkbox', $cssaggregate, FALSE, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('cssdynamic', 'checkbox', $cssdynamic, TRUE, XARVAR_DONT_SET)) {return;}

    $arole = xarFindRole('Administrators');
    if (!isset($arole)) {
        $arole=xarUFindRole('Administrators');
    }
    $defaultadmin= $arole->getID();

    if (!xarVarFetch('debuggroup', 'int', $debuggroup,  $defaultadmin, XARVAR_DONT_SET)) return;
    xarModSetVar('themes', 'ShowPHPCommentBlockInTemplates', $showphpcbit);
    xarModSetVar('themes', 'ShowTemplates', $showtemplates);
    xarModSetVar('themes', 'compress', $compress);
    xarModSetVar('themes', 'var_dump', $var_dump);
    xarModSetVar('privileges', 'debuggroup', $debuggroup);

    $flushmsg = '';

    $newvalues = array('cssaggregate' =>$cssaggregate == TRUE?$cssaggregate:0,
                       'cssoptimize'    => $cssoptimize == TRUE?$cssoptimize:0,
                       'jsaggregate' => $jsaggregate== TRUE?$jsaggregate:0
                       );
    $changed= array_diff_assoc($newvalues,$currentvalues);
    foreach ( $changed as $setting =>$value) {
        $newval = isset($value) && $value === TRUE ? xarML('ON') :xarML('OFF');
        $msg = xarML('Setting for -  #(1) - was changed to #(2).',$setting,$newval);
        xarTplSetMessage($msg,'status');
    };
    //set the correct cache dir before we go to flush
    $defaultdir =  sys::varpath().'/cache/styles';

    if (isset($csscachedir) && is_dir($csscachedir) && is_writeable($csscachedir)) {
         xarModSetVar('themes','csscachedir',$csscachedir);
    } elseif (is_dir($defaultdir) && is_writeable($defaultdir) ){
         xarModSetVar('themes','csscachedir',$defaultdir);
         $flushmsg .= xarML(" \nWARNING:\n\n The cache directory at '#(1)' is not present, or writeable. Default Style directory at '#(2)' is used!", $csscachedir,$defaultdir);
        xarTplSetMessage($flushmsg,'alert');
    } else {
         xarModSetVar('themes','csscachedir','');
         $flushmsg .= xarML(" \nWARNING: \n\nThe cache directory at '#(1)' is not present or writeable. Please ensure a style directory is available, and writeable, at #(1)", $csscachedir);
         xarTplSetMessage($flushmsg,'alert');
         $cssaggregate = FALSE;
    }

    //we always have to flush the style cache at least
    if (!empty($flushcache) || ($changed === TRUE)) {
        $flushmsg = xarModAPIFunc('base','admin','deletecache',
                    array('flushcache'=>$flushcache,'cssaggregate'=>$cssaggregate,'cssdynamic'=>$cssdynamic,'jsaggregate'=>$jsaggregate));
    }
    xarModSetVar('themes', 'cssaggregate',$cssaggregate);
    // We can have optimise without aggregation now
    //$cssoptimize = !isset($cssaggregate) ||( FALSE == $cssaggregate) ? FALSE :$cssoptimize;
    xarModSetVar('themes', 'cssoptimize', $cssoptimize);
    xarModSetVar('themes', 'jsaggregate', $jsaggregate);
    xarModSetVar('themes', 'dynamic', $cssdynamic);
    xarModSetVar('themes', 'cachefilenumber', $cachefilenumber);

    $templatedir = sys::varpath().'/cache/templates';
    if (!is_writable($templatedir)) {
        $flushmsg = xarML("The template cache directory at '#(1)' is not writeable! Please make it writeable to cache templates.", $templatedir);
        xarTplSetMessage($flushmsg,'error');
    }
    xarConfigSetVar('Site.BL.CacheTemplates', $cachetemplates);

    xarLogMessage('BASE: Configuration settings for Performance and Debug were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    $successmsg = xarML('Configuration settings for Performance and Debug were successfully saved.');
    xarTplSetMessage($successmsg,'status');

    if (isset($returnurl)) {
        xarResponseRedirect($returnurl);
    } else {
        xarResponseRedirect(xarModURL('base', 'admin', 'performance'));
    }
    // Return
    return TRUE;
}

?>
