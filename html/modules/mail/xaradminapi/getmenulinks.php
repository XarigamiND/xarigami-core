<?php
/**
 * Pass individual menu items to the admin panels
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */

/**
 * utility function pass individual menu items to the admin panels
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @return array containing the menulinks for the main menu items.
 */
function mail_adminapi_getmenulinks()
{
    // Security Check
    $menulinks = array();
    if (xarSecurityCheck('AdminMail', 0)) {

        $menulinks[] = Array('url' => xarModURL('mail','admin','compose'),
            'title' => xarML('Test your email configuration'),
            'label' => xarML('Test Configuration'),
            'active' => array('compose')
            
            );
        $menulinks[] = Array('url' => xarModURL('mail','admin','viewq',array('sentstatus'=>2)),
                'title' => xarML('View all mails scheduled to be sent later'),
                'label' => xarML('View Mail Queue'),
                'active' => array('viewq')                
                );
        $menulinks[] = Array('url' => xarModURL('mail','admin','template'),
            'title' => xarML('Change the mail template for notifications'),
            'label' => xarML('Notification Template'),
            'active' => array('template')            
            );
            
        $menulinks[] = Array('url' => xarModURL('mail','admin','modifyconfig'),
            'title' => xarML('Modify the configuration for the utility mail module'),
            'label' => xarML('Modify Config'),
            'active' => array('modifyconfig')            
            );
    }
    return $menulinks;
}
?>