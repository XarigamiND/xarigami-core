<?php
/**
 * Shows the user login form when login block is not active
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Shows the user login form when login block is not active
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare <jojodeexaraya.com>
 */
function authsystem_user_showloginform(Array $args = array())
{

    extract($args);
    if (!isset($redirecturl)) $redirecturl = xarServer::getBaseURL();
    xarVarFetch('redirecturl', 'str:1:254', $data['redirecturl'], $redirecturl, XARVAR_NOT_REQUIRED);

    //check is this a hidden url and short urls on??
    $hidemodurl = xarModGetVar('authsystem','hidemoduleurl');
    $useauthcheck = xarModGetVar('authsystem','useauthcheck');
    $useAliasName= xarModGetVar('authsystem','useModuleAlias');
    $currenturl= xarServerGetCurrentURL();

    $testurl = parse_url($currenturl);
    if (($hidemodurl == TRUE) && ($useAliasName == TRUE) ) {
        if (isset($testurl['query'])) {
            $isblocked =   stripos($testurl['query'], 'authsystem');
        } elseif (isset($testurl['path'])) {
            $isblocked =   stripos($testurl['path'], 'authsystem');
        }
        if ($isblocked == TRUE) {
             return xarResponseNotFound();
        }
    }
    if (!xarUserIsLoggedIn()) {
      // Security check
      // TODO: if exception redirects are set to ON we end up here, if further
      // more anon has no priv for ViewAuthSystem, we end up here again => infinite loop
      // 1. augment (i.e. hack it in) to force the check to go?
      // 2. why is this security check here in the first place (a usecase would be nice)
      //if (!xarSecurityCheck('ViewAuthsystem')) return; //jojodee - review this

      $data['loginlabel'] = xarML('Log In');
      
      $data['loginurl']=  xarModURL('authsystem','user','login');
      $data['authid'] ='';
      if ($useauthcheck == TRUE) {
          $data['authid']           = xarSecGenAuthKey();
      }
      return $data;
    } else {
      xarResponseRedirect($data['redirecturl']);
    }
}
?>