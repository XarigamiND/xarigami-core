<?php
/**
 * Standard function to get main menu links
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/*
 * Standard function to get main menu links
 */
function roles_userapi_getmenulinks()
{
    //If we have turned on role list (memberlist) display and users have requisite level to see them
    $memberliststate = xarModGetVar('roles', 'memberliststate');
    if (xarSecurityCheck('ViewRoles')) {
        if (($memberliststate >2) || xarSecurityCheck('AdminRole',0)){
                $menulinks[] = Array('url'   => xarModURL('roles','user','view'),
                                     'title' => xarML('View All Users'),
                                     'label' => xarML('Memberslist'),
                                     'active' => array('view','displayrole'),
                                     'activelabels' => array('',xarML('Display profile'))
                                     );

        }
    }
    if (xarUserIsLoggedIn()){
            $menulinks[] = Array('url'   => xarModURL('roles','user','account'),
                                 'title' => xarML('Your Custom Configuration'),
                                 'label' => xarML('Your Account'),
                                 'active' => array('account'),
                                 'activelabels' => array('',)
                                     );
   }
    //jojodee- Moved to Registration Module. Needed for reading/checking when registering. Most sites will require these
    //Can still be provided with custom pages, or install Registration module and turn registration off if you don't need it.
    // we don't really want to introduce dependency here on non-core module and poll for registration allowed.
    /*
    if (xarModGetVar('roles', 'showprivacy')){
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'user',
                                                  'privacy'),
                             'title' => xarML('Privacy Policy for this Website'),
                             'label' => xarML('Privacy Policy'));
    }
    if (xarModGetVar('roles', 'showterms')){
        $menulinks[] = Array('url'   => xarModURL('roles',
                                                  'user',
                                                  'terms'),
                             'title' => xarML('Terms of Use for this website'),
                             'label' => xarML('Terms of Use'));
    }
    */
    if (empty($menulinks)){
        $menulinks = '';
    }

    return $menulinks;
}

?>