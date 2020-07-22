<?php
/**
 * Return the path for a short URL to xarModURL for this module
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
 * return the path for a short URL to xarModURL for this module
 *
 * Supported URLs :
 *
 * /roles/
 * /roles/123
 * /roles/[username]
 * /roles/account
 * /roles/account/[module]
 *
 * /roles/list
 * /roles/list/viewall
 * /roles/list/X
 * /roles/list/viewall/X
 *
 * /roles/password
 * /roles/settings
 * /roles/settings/form (deprecated)
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author the roles module development team
 * @param $args the function and arguments passed to xarModURL
 * @returns string
 * @return path to be added to index.php for a short URL, or empty if failed
 */
function roles_userapi_encode_shorturl($args)
{
    // Get arguments from argument array
    extract($args);

    // Check if we have something to work with
    if (!isset($func)) {
        return;
    }
    unset($args['func']);

    // Initialise the path.
    $path = array();

    // we can't rely on xarMod::getName() here -> you must specify the modname.
    $module = 'roles';
    $aliasisset = xarModGetVar($module, 'useModuleAlias');
    $aliasname = xarModGetVar($module, 'aliasname');
    
    if (!empty($aliasisset) && !empty($aliasname)) {
        $module_for_alias = xarModGetAlias($aliasname);
        if ($module_for_alias == $module) {
            $module = $aliasname;
        }
    }
    switch($func) {
        case 'main':
            // Note : if your main function calls some other function by default,
            // you should set the path to directly to that other function
            break;
        case 'view':
            $path[] = 'list';
            if (!empty($phase) && $phase == 'viewall') {
                unset($args['phase']);
                $path[] = 'viewall';
            }
            if (!empty($phase) && $phase == 'active') {
                unset($args['phase']);
                $path[] = 'online';
            }            
            if (!empty($letter)) {
                unset($args['letter']);
                $path[] = $letter;
            }
            break;

        case 'lostpassword':
            $path[] = 'password';
            break;
            
        case 'resetpassword':
            $path[] = 'resetpassword';
            /*if (!empty($phase) && $phase == 'valreset') {
                unset($args['phase']);
                $args['phase'] = 'valreset';
            }*/
            break;
         case 'account':
            $path[] = 'account';
            if(!empty($moduleload)) {
                // Note: this handles usermenu requests for hooked modules (including roles itself).
                unset($args['moduleload']);
                $path[] = $moduleload;
            }
            break;

          case 'usermenu':
            $path[] = 'settings';
            if (!empty($phase) && ($phase == 'formbasic' || $phase == 'form')) {
                // Note : this URL format is no longer in use
                unset($args['phase']);
                $path[] = 'form';
            }
            break;

            case 'display':
            // check for required parameters
            if (isset($uid) && is_numeric($uid)) {
                unset($args['uid']);
                $userRole = xarMod::apiFunc('roles',  'user',  'get',
                                       array('uid' => $uid));
               if (empty($userRole)) {
                   
                    break;
                } else {
                    $uname = xarUserGetVar('uname',$uid);
                }
                if (xarModGetVar('roles','usernameurls')) {
                    $path[]= xarVarPrepForDisplay($uname);
                } else {
                    $path[] = $uid;
                }
            }
            break;

        default:
            break;
    }


    // If no short URL path was obtained above, then there is no encoding.
    if (empty($path)) {
        // Return without a short URL.
        return;
    }

    // Modify some other module arguments as standard URL parameters.
    // Turn a 'cids' array into a 'catid' string.
    if (!empty($cids) && count($cids) > 0) {
        unset($args['cids']);
        if (!empty($andcids)) {
            $args['catid'] = join('+', $cids);
        } else {
            $args['catid'] = join('-', $cids);
        }
    }

    // Slip the module name or alias in at the start of the path.
    array_unshift($path, $module);

    return array(
        'path' => $path,
        'get' => $args
    );
}

?>