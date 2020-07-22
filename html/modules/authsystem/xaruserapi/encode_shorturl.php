<?php
/**
 * Return the path for a short URL to xarModURL for this module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * return the path for a short URL to xarModURL for this module
 *
 * Supported URLs :
 *
 * /authsystem/
 * /authsystem/login
 * /authsystem/logout
 * /authsystem/password
 * @param $args the function and arguments passed to xarModURL
 * @return string path to be added to index.php for a short URL, or empty if failed
 */
function authsystem_userapi_encode_shorturl($args)
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
    $module = 'authsystem';
    $aliasisset = xarModGetVar($module, 'useModuleAlias');
    $aliasname = xarModGetVar($module, 'aliasname');
    if (!empty($aliasisset) && !empty($aliasname)) {
        // Check this alias really is a module alias, by mapping
        // it back to its module name.
        $module_for_alias = xarModGetAlias($aliasname);

        if ($module_for_alias == $module) {
            // Yes, we have a valid module alias, so use it
            // now instead of the module name.
            $module = $aliasname;
        }
    }

    switch($func) {
        case 'main':
            // Note : if your main function calls some other function by default,
            // you should set the path to directly to that other function
            break;
        case 'lostpassword':
            $path[] = 'password';
            break;
        case 'showloginform':
            $path[] = 'login';
            break;

        case 'logout':
            $path[] = $func;
            break;

        default:
            break;
    }


    // If no short URL path was obtained above, then there is no encoding.
    if (empty($path)) {
        // Return without a short URL.
        return;
    }

    /* Modify some other module arguments as standard URL parameters.
     * Turn a 'cids' array into a 'catid' string.
     */
    /* if (!empty($cids) && count($cids) > 0) {
        unset($args['cids']);
        if (!empty($andcids)) {
            $args['catid'] = join('+', $cids);
        } else {
            $args['catid'] = join('-', $cids);
        }
    }
    */
    // Slip the module name or alias in at the start of the path.
    array_unshift($path, $module);

    return array(
        'path' => $path,
        'get' => $args
    );
}
?>