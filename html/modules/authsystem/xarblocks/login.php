<?php
/**
 * Login via a block.
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
 * Login via a block: initialise block
 *
 * @author Jim McDonald
 * @return array
 */
function authsystem_loginblock_init()
{
    return array(
        'showlogout' => 0,
        'logouttitle' => '',
        'nocache' => 1, // don't cache by default
        'pageshared' => 1, // if you do, share across pages
        'usershared' => 0, // but don't share for different users
        'cacheexpire' => null
    );
}

/**
 * get information on block
 */
function authsystem_loginblock_info()
{
    return array(
        'text_type' => 'Login',
        'module' => 'authsystem',
        'text_type_long' => 'User login'
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function authsystem_loginblock_display($blockinfo)
{

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Display logout block if user is already logged in
    // e.g. when the login/logout block also contains a search box
    if (xarUserIsLoggedIn()) {
        if (!empty($vars['showlogout'])) {
            $args['name'] = xarUserGetVar('name');

            // Since we are logged in, set the template base to 'logout'.
            // FIXME: not allowed to set BL variables directly
            $blockinfo['_bl_template_base'] = 'logout';

            if (!empty($vars['logouttitle'])) {
                $blockinfo['title'] = $vars['logouttitle'];
            }
        } else {
            return;
        }
    } elseif (xarServer::getVar('REQUEST_METHOD') == 'GET') {
        // URL of this page
        $args['return_url'] = xarServer::getCurrentURL();
    } else {
        // Base URL of the site
        $args['return_url'] = xarServer::getBaseURL();
    }
     $useauthcheck = xarModGetVar('authsystem','useauthcheck');
     $args['authid'] = '';
     if ($useauthcheck == TRUE) {
          $args['authid']           = xarSecGenAuthKey();
      }


    // Used in the templates.
    $args['blockid'] = $blockinfo['bid'];

    $blockinfo['content'] = $args;
    return $blockinfo;
}

?>