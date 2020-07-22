<?php
/**
 * Main admin function
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * the main administration function
 */
function roles_admin_loginas()
{
    // Security Check
    // no need to duplicate as in the canproxy call
    
    $canproxy = xarMod::apiFunc('roles','admin','canproxy');
    
    if (!$canproxy) return;
     
    if (!xarVarFetch('uid',       'int:0:', $uid,             0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('passcheck', 'str:0:100', $passcheck,       NULL, XARVAR_NOT_REQUIRED)) return;
    if (!isset($passcheck) || is_null($passcheck)) $passcheck = '';

    $return_url = xarModURL('roles','admin','showusers');
    //Check if we need to supply login credentials first
    $requirelogin = xarModGetVar('roles','requirelogin');
    if ($requirelogin == TRUE) {
        if (empty($passcheck)) {
            $data = array();
            //display a form for capturing password only
            $data['authid'] = xarSecGenAuthKey('roles');
            $data['uid'] = $uid;  
  
            return $data;
        } elseif (!empty($passcheck) && ($uid>0)) {
            if (!xarSecConfirmAuthKey()) return ;
            //get the proxy user info
            $proxyid = xarUserGetVar('uid');
            $proxyinfo = xarMod::apiFunc('roles','user','get',array('uid'=>$proxyid));
            $passcheckmd5 = MD5($passcheck);

            //if ($passcheck != $proxyinfo['pass']) {
              if (strcmp($passcheckmd5,$proxyinfo['pass']) != 0)  {
                return xarTplModule('roles','user','errors',array('errortype' => 'loginas_fail'));
            }
        } else {

        }
    }
    //included group
    $proxiedgroup = xarModGetVar('roles','proxygroup');
    
    //check group
    $ismember = xarMod::apiFunc('roles','user','checkgroup',array('uid'=>$uid, 'gid'=>$proxiedgroup));
    if (!$ismember) {
        return xarTplModule('roles','user','errors',array('errortype' => 'loginas_notavailable'));
    }
    //now login the user programatically    
    //get the username
    $user = xarMod::apiFunc('roles','user','get',array('uid'=>$uid));

    if (!is_array($user)) {
        xarResponseRedirect($return_url);
    }

    $userName = $user['uname'];
    $password = $user['pass'];
    $loginok = xarUserLogIn($userName,$password);
    if ($loginok) {
    // success
      xarResponseRedirect(xarServer::getBaseURL());
    } else {
      xarResponseRedirect($return_url);
    }
}
?>