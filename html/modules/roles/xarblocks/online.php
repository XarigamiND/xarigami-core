<?php
/**
 * Online Block
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Online Block
 * @author Jim McDonald, Greg Allan, John Cox, Michael Makushev
 */
/*
 * initialise block
 */
function roles_onlineblock_init()
{
    // No parameters accepted by this block.
    return array(
        'groups' => array(),
        'max_users' => 20,
        'nocache' => 0, // cache by default
        'pageshared' => 1, // share across pages
        'usershared' => 1, // share for group members
        'cacheexpire' => null);
}

/**
 * get information on block
 */
function roles_onlineblock_info()
{
    return array(
        'text_type' => 'Online',
        'module' => 'roles',
        'text_type_long' => 'Display who is online',
        'func_update' => 'roles_onlineblock_update',
        'allow_multiple' => true,
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function roles_onlineblock_display($blockinfo)
{
    /*
     [jojo] - we need two numbers, online user names and last registered (successful) user
            - how do we get them most efficiently
     1. Total online (active) users
     2. Anonymous users - query sessions table
     3. Last registered - must be an active user, else don't show any for now
    */

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    //get a count of all active sessions/users
    //do not use unique here - we need all active users to start with
    // including all anon sessions
    $allusersonline = xarMod::apiFunc('roles', 'user', 'countallactive',
                array(
                    'include_anonymous' => true,
                    'include_myself' => false,
                    'unique'=>FALSE

                ));

    // get all our active (online and state active) members
    $grouplist = !empty($vars['groups']) ? implode(',', $vars['groups']) : NULL;
    $activeusers =  xarMod::apiFunc('roles', 'user', 'getallactive',
                array(
                    'include_anonymous' => false,
                    'order'=> 'name',
                    'startnum' => 0,
                    'include_myself' => false,
                    'unique' => TRUE,
                    'group' => $grouplist,
                    'numitems' => (!empty($vars['max_users']) ? $vars['max_users'] : NULL),
                ));

    $args['test1'] = array();
    $args['test2'] = array(); //alternative array that honours online status
    //get info on our active users
    if (!empty($activeusers)) {
        foreach ($activeusers as $key => $aa) {
            $showonline = xarUserGetVar('showonline',$aa['uid']) ?xarUserGetVar('showonline',$aa['uid']) : TRUE ;
            $userurl = xarModURL( 'roles', 'user', 'display', array('uid' => $aa['uid']));
            $args['test1'][$key] = array(
                'uid' => $aa['uid'],
                'name' => $aa['name'],
                'userurl' => $userurl,
                'total' => '',
                'unread' => '',
                'messagesurl' => ''
            );
            if ($showonline == TRUE) {
                $args['test2'][$key] = array(
                    'uid' => $aa['uid'],
                    'name' => $aa['name'],
                    'userurl' => $userurl,
                    'total' => '',
                    'unread' => '',
                    'messagesurl' => ''
                     );
            }

            if (xarMod::isAvailable('messages')) {
                $totalmessages =    xarMod::apiFunc('messages', 'user', 'count_total',
                                            array('uid'=>$aa['uid']));

                $unread =           xarMod::apiFunc('messages', 'user', 'count_unread',
                                            array('uid'=>$aa['uid']));

                $messagesurl  =      xarModURL('messages', 'user', 'display',
                                            array('uid'=>$aa['uid']));

                if ($aa['name'] == xarUserGetVar('name')) {
                    $args['test1'][$key]['total'] = $totalmessages;
                    $args['test1'][$key]['unread'] = $unread;
                    $args['test1'][$key]['messagesurl'] = $messagesurl;
                    if ($showonline = TRUE) {
                        $args['test2'][$key]['total'] = $totalmessages;
                        $args['test2'][$key]['unread'] = $unread;
                        $args['test2'][$key]['messagesurl'] = $messagesurl;
                    }
                }
            }
        }
    }

    $args['numusers'] = count($args['test1']);
    $args['numusers2'] = count($args['test2']);

    //how many anon users?
    $args['numguests'] = $allusersonline - $args['numusers'] ;
    $args['numguests2'] = $allusersonline - $args['numusers2'] ;

    // Pluralise
    if ( $args['numguests'] == 1) {
         $args['guests'] = xarML('guest');
    } else {
         $args['guests'] = xarML('guests');
    }

    if ($args['numusers'] == 1) {
         $args['users'] = xarML('user');
    } else {
         $args['users'] = xarML('users');
    }

    $uid = xarModGetVar('roles', 'lastuser');
    // Make sure we have an activated lastuser
    if (!empty($uid)) {
        if(!is_numeric($uid)) {
        //Remove this further down the line
            $status = xarMod::apiFunc('roles', 'user', 'get', array('uname' => $uid, 'state'=>3)); //activated only
        } else {
            $status = xarMod::apiFunc('roles', 'user', 'get', array('uid' => $uid, 'state'=>3));//activated only
        }

        // Check return
        if ($status) {$args['lastuser'] = $status;}
        //if group, are they in one or other of the given groups
        if ($status && isset($vars['groups'])) {
            $isingroup = xarMod::apiFunc('roles','user','checkgroup',array('gidlist'=>$vars['groups'], 'uid' =>$uid));
            if (!$isingroup) $args['lastuser'] = NULL;
        }
        //If not, what do we want to do? For now, don't show lastuser.
    }
    $args['blockid'] = $blockinfo['bid'];
    $blockinfo['content'] = $args;
    return $blockinfo;
}
?>