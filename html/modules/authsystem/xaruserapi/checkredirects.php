<?php
/**
 *Check for redirects during login
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Check if first login and subsequent login redirects are active
 * @param  $redirecturl: default redirect url
 * @return $redirecturl
 */
function authsystem_userapi_checkredirects($args)
{
    extract($args);
    $redirectdata = array();
    //
    if (!isset($redirecturl)) {
        $redirecturl = xarServer::getBaseURL(); //default
    }
    $externalurl = false; //used as a flag for userhome external url
    if (xarModGetVar('roles', 'loginredirect'))
    {
        //only redirect to home page if this option is set
        if (xarMod::apiFunc('roles','admin','checkduv',array('name' => 'setuserhome', 'state' => 1)))
        {
            $truecurrenturl = xarServer::getCurrentURL(array(), false);
            $role = xarUFindRole($uname);
            $url = $lastresort ? '[base]' : $role->getHome();
            if (!isset($url) || empty($url))
            {
                //jojodee - we now have primary parent implemented so can use this if activated
                if (xarModGetVar('roles','setprimaryparent'))
                {
                    //primary parent is activated
                    $primaryparent = $role->getPrimaryParent();
                    $primaryparentrole = xarUFindRole($primaryparent);
                    $parenturl = $primaryparentrole->getHome();
                    if (!empty($parenturl))
                        $url= $parenturl;
               } else {
                    // take the first home url encountered?
                    //let's just set it as frontpage default
                    $url = xarServer::getBaseURL();
                    
                    foreach ($role->getParents() as $parent)
                    {
                        $parenturl = $parent->getHome();
                        if (!empty($parenturl))
                        {
                            $url = $parenturl;
                            break;
                        }
                    }
                    
                }
            }

            /* move the half page of code out to a Roles function. No need to repeat everytime it's used */
            $urldata = xarMod::apiFunc(
                'roles','user','userhome',
                array('url'=>$url,'truecurrenturl'=>$truecurrenturl)
            );
           
            $data=array();
            if (!is_array($urldata) || !$urldata)
            {
                $externalurl=false;
                $redirecturl=xarServer::getBaseURL();
            } else
            {
                $externalurl=$urldata['externalurl'];
                $redirecturl=$urldata['redirecturl'];
            }
        }
    } //end get homepage redirect data

    //Check for redirection on first login - overrides others
    $first = xarModGetVar('roles','firstloginurl');
    $firstloginurl = isset($first) && !empty($first)?$first:'';
    $firstlogin = xarSession::getVar('roles_firstlogin');
    
    if (!empty($firstloginurl) && (TRUE== $firstlogin)) {
        $redirecturl = $firstloginurl;
    } elseif ($externalurl) {
        /* Open in IFrame - works if you need it */
        /* $data['page'] = $redirecturl;
           $data['title'] = xarML('Home Page');
           return xarTplModule('roles','user','homedisplay', $data);
         */
         $redirecturl  = $redirecturl;
    } else {
         $redirecturl  = $redirecturl;
    }

    return $redirecturl;
}

?>