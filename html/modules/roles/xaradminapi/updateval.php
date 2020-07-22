<?php
/**
 * Update a role validation column
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * Update a user's role validation column
 *
 * @param $args['uid'] user ID
 * @param $args['name'] user display name
 * @param $args['uname'] user nick name
 * @param $args['email'] user email address
 * @param $args['valcode'] user password
 * @param $args['url'] user url
 */
function roles_adminapi_updateval($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($uid)) ||
        (!isset($name)) ||
        (!isset($uname)) ||
        (!isset($email)) ||
        (!isset($valcode))) {
        $msg = xarML('Invalid Parameter Count');
        throw new BadParameterException(null,$msg);
    }
    $item = xarMod::apiFunc('roles', 'user', 'get',
            array('uid' => $uid));

    if ($item == false) {
        
        new IDNotFoundException($uid);
    }

    $dbconn = xarDB::$dbconn;
    $xartable = &xarDB::$tables;

    $rolesTable = $xartable['roles'];
    if (!empty($valcode)){
        $cryptcode=md5($valcode);
    } else {
        $cryptcode ='';
    }
    $query = "UPDATE $rolesTable
              SET  xar_valcode = ?
              WHERE xar_uid = ?";
        $bindvars = array($cryptcode,$uid);

    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['name'] = $name;
    $item['uname'] = $uname;
    $item['email'] = $email;
    
     //Don't call hooks here - we have not updated the role as such just preparing for possible pw change
    //xarMod::callHooks('item', 'update', $uid, $item);

    return true;
}

?>
