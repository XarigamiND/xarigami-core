<?php
/**
 * @package modules
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2008-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/*
 * Handle rendering of an avatar
 *
 * ProvHandling for <xar:roles-avatar ...>  tags
 * Format : <xar:roles-avatar /> or
 *          Typical usage is <xar:roles-avatar size="80" uid="$uid" default="someimage.gif" link="1|0' /> 
 *                    
 *                    will display the avatar 80 px in width and default to 'someimage.gif' for a forced default
 *                    a link value of 1 will provide click through to the user's role display
 *          Placed in template  where you wish the avatar to appear
 *          The attributes are optional and image defaults to size of 80 px
 * @author jojodee
 * @param $args containing option for $size  and $default and $link
 * @returns string
 * @return the PHP code needed to display avatarin the BL template
 */
function roles_userapi_renderavatar($args)
{
    if (!isset($args['uid']) )
    {
     $args['uid'] = xarUserGetVar('uid');
    }
    
    $out = "echo xarMod::apiFunc('roles',
                   'user',
                   'dorenderavatar',\n";
        $out .= "                   array(\n";
        foreach ($args as $key => $val) {
            if (is_numeric($val) || substr($val,0,1) == '$') {
                $out .= "                         '$key' => $val,\n";
            } else {
                $out .= "                         '$key' => '$val',\n";
            }
        }
        $out .= "));";
    return $out;
}
?>