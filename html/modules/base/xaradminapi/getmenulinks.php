<?php
/**
 * Get admin menu links
 * @package xarigami
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Pass individual menu items to the admin menu
 *
 * @author the Base module development team
 * @return array containing the menulinks for the admin menu items.
 */
function base_adminapi_getmenulinks()
{
     // Security Check
    $menulinks = array();
    if (xarSecurityCheck('AdminBase',0)) {

        $menulinks[] = array('url'   => xarModURL('base','admin','modifyconfig'),
                             'title' => xarML('Site and server general settings'),
                             'label' => xarML('Site Configuration'),
                             'active' => array('modifyconfig','security','urls','other'),
                             'activelabels' => array('',xarML('Server &amp; Sessions'),xarML('URL Rewriting'),xarML('Other'))
                             );
        $menulinks[] = array('url'   => xarModURL('base','admin','performance'),
                             'title' => xarML('Caching, performance and debugging options'),
                             'label' => xarML('Performance &amp; Debug'),
                             'active' => array('performance')
                             );
        $menulinks[] = array('url'   => xarModURL('base','admin','restrictions'),
                             'title' => xarML('Site lock and restrictions'),
                             'label' => xarML('Site restrictions'),
                             'active' => array('restrictions','restrict','sitelock'),
                             'activelabels' => array('',xarML('Site and User Restrictions'), xarML('Sitelock'))
                             );
        $menulinks[] = array('url'   => xarModURL('base','admin','languageandlocale'),
                             'title' => xarML('Set language, locale and timezones'),
                             'label' => xarML('Language &amp; Places'),
                             'active' => array('languageandlocale')
                             );
        $menulinks[] = array('url'    => xarModURL('base','admin','sysinfo'),
                             'title'  => xarML('Site Inforemation&amp; Resources'),
                             'label'  => xarML('Site Information'),
                             'active' => array('sysinfo')
                             );
    }

    return $menulinks;
}
?>