<?php
/**
 * Utility function to replace %%calls%%
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 * @author John Cox 
 */
/**
 * utility function utility function to replace %%calls%%
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @return array containing the search and replace items
 */
function mail_adminapi_replace($args)
{
    extract ($args);

    $sitename   = xarModGetVar('themes', 'SiteName');
    $siteslogan = xarModGetVar('themes', 'SiteSlogan');
    $siteadmin  = xarModGetVar('mail', 'adminname');
    $siteurl    = xarServer::getBaseURL();

    $name = xarUserGetVar('name');
    $uid = xarUserGetVar('uid');

    $search = array('/%%name%%/',
                    '/%%sitename%%/',
                    '/%%siteslogan%%/',
                    '/%%siteurl%%/',
                    '/%%uid%%/',
                    '/%%siteadmin%%/');

    $replace = array("$name",
                     "$sitename",
                     "$siteslogan",
                     "$siteurl",
                     "$uid",
                     "$siteadmin");

    $searchstrings = xarModGetVar('mail','searchstrings');
    if (!empty($searchstrings)) {
        $searchstrings = unserialize($searchstrings);
        $searchstrings = explode("\r\n", $searchstrings);
        foreach ($searchstrings as $key) {
            $search[] = '/'. $key .'/';
        }
    }

    $replacestrings = xarModGetVar('mail','replacestrings');
    if (!empty($replacestrings)) {
        $replacestrings = unserialize($replacestrings);
        $replacestrings = explode("\r\n", $replacestrings);
        foreach ($replacestrings as $key) {
            $replace[] = $key;
        }
    }

    $message = preg_replace($search,
                            $replace,
                            $message);

    $subject = preg_replace($search,
                            $replace,
                            $subject);

    $htmlmessage = preg_replace($search,
                                $replace,
                                $htmlmessage);


    return array('message'      => $message,
                 'subject'      => $subject,
                 'htmlmessage'  => $htmlmessage);

}
?>