<?php
/**
 * Extract function and arguments from short URLs for this module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * Supported URLs :
 *
 * /authsystem/
 * /authsystem/login
 * /authsystem/logout
 * /authsystem/password
 *
 * @param $params array containing the different elements of the virtual path
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function authsystem_userapi_decode_shorturl($params)
{
    $args = array();
    $module = 'authsystem';
    $useralias = false;
    $aliasname = NULL;
    $hidemodurl = xarModGetVar('authsystem','hidemoduleurl');
    $obfuscated = FALSE;

    if (($hidemodurl == TRUE) && ($params[0] == $module)) {
        $obfuscated = TRUE;
        //this will go to a valid url if it returns now
        //we want to hide that. Let's send it directly to the not found page
        //jojo - fix this - right response but code is not finished/correct :)
        return xarResponseNotFound();

    } else {
        if ($params[0] != $module) { /* it's possibly some type of alias */
            $aliasisset = xarModGetVar('authsystem', 'useModuleAlias');
            $aliasname = xarModGetVar('authsystem','aliasname');
            if (($aliasisset) && isset($aliasname)) {
                $usealias   = true;
            }
        }


        if (empty($params[1])) {
            // nothing specified -> we'll go to the main function
            return array('main', $args);

        } elseif (preg_match('/^index/i',$params[1])) {
            // some search engine/someone tried using index.html (or similar)
            // -> we'll go to the main function
            return array('main', $args);

        } elseif (preg_match('/^password/i',$params[1])) {
            return array('lostpassword', $args);

        } elseif (preg_match('/^login/i',$params[1])) {
            return array('showloginform', $args);


        } elseif (preg_match('/^logout/i',$params[1])) {
            return array('logout', $args);

        } else {
            // we have no idea what this virtual path could be, so we'll just
            // forget about trying to decode this thing

            // you *could* return the main function here if you want to
            // return array('main', $args);
        }
        // default : return nothing -> no short URL decoded
    }
}

?>