<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Delete cache files
 *
 * Mostly moved from sitetools module now part of core
 */
function base_adminapi_deletecache()
{
    // Confirm authorisation code
    // Security Check
   // if (!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();
    // Get parameters
    if (!xarVarFetch('flushcache', 'pre:lower:passthru:enum:all:templates:rss:adodb:styles', $flushcache, '', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('cssaggregate', 'checkbox', $cssaggregate, false, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('cssdynamic', 'checkbox', $cssdynamic, false, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('jsaggregate', 'checkbox', $cssdynamic, false, XARVAR_DONT_SET)) {return;}
    $flushmsg = '';
   //we always have to flush the style cache at least
    
    
    $flusharray = array();
    if ($flushcache == 'all') {
        $flusharray = array('templates','rss','styles');
    } else {
        $flusharray = explode(',',$flushcache);
        if ($cssaggregate == false) {
            if (!in_array('styles',$flusharray)) {
                $flusharray = array_merge($flusharray,array('styles'));
            }
        }
    }
    
    if (in_array('styles', $flusharray) && ($cssaggregate || $cssdynamic)) {
        // Flush the output cache
        if (class_exists('xarPageCache')) xarPageCache::flushCached('');
        // Flush the sessionless cache
        if (class_exists('xarOutputCache')) {
            $dir = xarOutputCache::$cacheDir . "/page/";
            if (is_dir($dir)) {
                $files = glob($dir);
                foreach ($files as $file) {
                    @unlink($file);
                }
                unset($files2);
            }
        }
    }

    foreach ($flusharray as $cachetoflush)
    {
        if (!empty($cachetoflush)) {
            if ($cachetoflush != 'styles') {
                $cachebasedir = sys::varpath().'/cache/' . $cachetoflush;
            }elseif ($cachetoflush == 'styles') {
                $cachebasedir = xarModGetVar('themes','csscachedir');
            }
            $var = is_dir($cachebasedir);
            if ($var) {
                if (!is_writable($cachebasedir)) {
                    $flushmsg = xarML("The #(1) cache directory or files are not writeable and could not be deleted!", $cachebasedir);
                    break; //just one message enough
                } else {
                    $handle=opendir($cachebasedir);
                    $skip_array = array('.','..','SCCS','_MTN','index.htm','index.html','DS_Store');
                    while (false !== ($file = readdir($handle))) {
                        if ($cachetoflush == 'adodb') {
                            if (!in_array($file,$skip_array)) {
                                $subhandle=opendir("{$cachebasedir}/{$file}");
                                $skip_array2 = array('.','..','SCCS');
                                while (false !== ($sfile = readdir($subhandle))) {
                                    if(!in_array($sfile,$skip_array2)) {
                                        unlink("{$cachebasedir}/{$file}/{$sfile}");
                                    }
                                }
                                closedir($subhandle);
                                rmdir("{$cachebasedir}/{$file}");
                            }
                        } else {
                            while (false !== ($file = readdir($handle))) {
                                if (!in_array($file,$skip_array)) {
                                    unlink($cachebasedir."/".$file);
                                }
                             }
                        }
                    }
                    closedir($handle);
                }
            }
            if ($flushcache == 'all') {
                 $flushmsg = xarML("The cache directories and files were successfully deleted!");
                 xarTplSetMessage($flushmsg,'status', FALSE);
            } else {
                 $flushmsg = xarML("The cache files at <strong>#(1)</strong> were successfully deleted!", $cachebasedir);
                 xarTplSetMessage($flushmsg,'status');
            }
        }
    }
    // Return
    return $flushmsg;
}

?>
