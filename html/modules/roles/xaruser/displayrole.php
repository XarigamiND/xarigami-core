<?php
/**
 * display user
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * display user role information - independent of the user role account
 * @author Jo dalle Nogare <jojodee@xaraya.com>
 */
function roles_user_displayrole($args)
{
    extract($args);
    xarSession::delVar("roles.usermenu"); //clear some session vars
    if (!xarVarFetch('uid','int:1:',$uid, 0)) return;
    if (!xarVarFetch('itemid','int:1:',$itemid, xarUserGetVar('uid'))) return;

    if (empty($uid) && !empty($itemid)) {
         $uid = (int)$itemid;
    }

    // Get user information
    $data = xarMod::apiFunc('roles', 'user', 'get', array('uid' => $uid));

    if ($data == false) return;

    //current user should always be able to see their own profile?
    $currentuserid = xarUserGetVar('uid');
    if (($uid !=$currentuserid)  && !xarSecurityCheck('ReadRole',1,'Roles',$uid)) return;

    $data['email'] = xarVarPrepForDisplay($data['email']);

    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = 0; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));

    //Setup user home url if Userhome is activated and user can set the URL
    //So it can display in link of User account page
    $externalurl=false; //used as a flag for userhome external url
    if (xarMod::apiFunc('roles','admin','checkduv',array('name' => 'setuserhome', 'state' => 1))) {

        $role = xarUFindRole(xarUserGetVar('uname',$uid));
        $url  = $role->getHome(); //what about last resort here?
        if (!isset($url) || empty($url)) {
            //we now have primary parent implemented so can use this if activated
            if (xarModGetVar('roles','setprimaryparent')) { //primary parent is activated
                $primaryparent = $role->getPrimaryParent();
                if (!empty($primaryparent)) {
                    $primaryparentrole = xarUFindRole($primaryparent);
                    $parenturl = $primaryparentrole->getHome();
                    if (!empty($parenturl)) $url= $parenturl;
                }
            } else {
                // take the first home url encountered - other viable option atm?
                foreach ($role->getParents() as $parent) {
                    $parenturl = $parent->getHome();
                    if (!empty($parenturl))  {
                        $url = $parenturl;
                        break;
                    }
                }
            }
        }
        //We have a home url - let us see if it is a shortcut, or internal, or external URL
        $homeurldata =xarMod::apiFunc('roles','user','userhome',array('url'=>$url,'truecurrenturl'=>$item['returnurl']));
        if (!is_array($homeurldata) || !$homeurldata) {
            $externalurl = false;
            $homeurl = xarServer::getBaseURL(array(),false);
        } else{
           $externalurl = $homeurldata['externalurl'];
           $homeurl     = $homeurldata['redirecturl'];
        }

        $data['externalurl'] = $externalurl;
        $data['homelink']    = $homeurl;
    } else {
        $data['externalurl'] = false;
        $data['homelink']    = '';
    }
    if (xarModGetVar('roles','setuserlastlogin')) {
        //jojo 1/10/2008- let's return the data to the template
        // it is up to the theme and site designer to decide whether to show it
        if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) {
            $data['userlastlogin']    = xarSessionGetVar('roles_thislastlogin');
            $data['usercurrentlogin'] = xarModUserVars::get('roles','userlastlogin',$uid);
        }else { //if (xarSecurityCheck('AdminRole',0)){
            $data['usercurrentlogin'] = ''; //not available unless current user and logged in
            $data['userlastlogin']    = xarModUserVars::get('roles','userlastlogin',$uid);
        }
    }else{
        $data['userlastlogin']    = '';
        $data['usercurrentlogin'] = '';
    }
    if (xarModGetVar('roles','setuserlastvisit')) {
        $data['userlastvisit'] = xarModUserVars::get('roles', 'userlastvisit', $uid);
    }
    //timezone
    if (xarModGetVar('roles','setusertimezone')) {
      $usertimezone      =  unserialize(xarModUserVars::get('roles','usertimezone',$uid));
      $data['utimezone'] = $usertimezone['timezone'];
      $offset            = $usertimezone['offset'];
      //make it pretty
      if (isset($offset)) {
          $hours = intval($offset);
          if ($hours != $offset) {
              $minutes = abs($offset - $hours) * 60;
          } else {
              $minutes = 0;
          }
          if ($hours > 0) {
              $data['offset'] = sprintf("%+d:%02d",$hours,$minutes);
          } else {
              $data['offset'] = sprintf("%+d:%02d",$hours,$minutes);
          }

      }
    } else {
        $data['utimezone'] = '';
        $data['offset']    = '';
    }
    //optional community properties
    //we only want properties for display not all properties
    $properties = array();
    $propertieslist = array();
   if (xarMod::isHooked('dynamicdata','roles')) {
        $objectinfo= xarMod::apiFunc('dynamicdata','user','getitem',
                            array(  'module'=>'roles',
                                    'itemtype'=>0,
                                    'itemid'=>$uid,
                                    'getobject'=>1,
                                    ));
        if ($objectinfo) {
            $propertieslist = $objectinfo->getProperties();
            foreach($propertieslist as $name=>$info) {
                //do not show hidden properties or input only properties - we need to do this ourselves here
                if (($info->status !=Dynamic_Property_Master::DD_DISPLAYSTATE_HIDDEN) &&
                    ($info->status !=Dynamic_Property_Master::DD_DISPLAYSTATE_INPUTONLY)) {
                    $properties[$name]= $propertieslist[$name];
                }
            }
        }
    }
    $data['properties'] = $properties;
    $data['propertieslist'] = $propertieslist;

    $isonline = xarMod::apiFunc('roles','user','getallactive', array('uid'=>$uid));

    $isonline = current($isonline);
    $data['showonline'] = xarUserGetVar('showonline',$uid);

    if (is_array($isonline) && $isonlinle['uid'] = $uid) {
        $data['isonline'] = true;
    } else {
        $data['isonline'] = false;
    }
    $data['avatar_type']= xarUserGetVar('avatar_type',$uid);
    //end optional

    $hooks = array();
    $hooks = xarMod::callHooks('item', 'display', $uid, $item);
    $data['hooks'] = $hooks;
    $data['pagetitle'] = xarML('Profile for #(1)',xarVarPrepForDisplay($data['name']));

    xarTplSetPageTitle($data['pagetitle']);
    return $data;
}

?>
