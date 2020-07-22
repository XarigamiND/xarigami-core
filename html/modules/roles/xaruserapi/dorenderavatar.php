<?php
/**
 * @package modules
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2008-2012 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/*
 * Show avatar in template
 *
 * @param $args array containing the definition of the avatar :
 *        $uid int user role id
 *        $size int width of image display - optional
 *        $link boolean - optional, to display a link to the user's role display
 *        $text string - optional, alt text to display in image
 * @return string containing the HTML (or other) text to output in the BL template
 * @return the PHP code needed to display avatarin the BL template
 */
function roles_userapi_dorenderavatar($args)
{
    extract($args);

    if(!isset($uid)) {
        $uid = xarUserGetVar('uid');
    }
    $uid = (int)$uid;
    $width= isset($size) ? $size: 80;

    $text = isset($text) ? $text : '';
    $link = isset($link) ? $link : 0;

    $myavatar = ' ';
    $out = '';
    $anon = FALSE;
    $genericavatar = xarTplGetImage('avatar.jpg','roles');
     //we have no avatars for anon
     //set  a default
    if ($uid == xarConfigGetVar('Site.User.AnonymousUID'))
    {
        $anon = TRUE;
        if (!is_null($genericavatar)) {
            $out = '&#160;<img src="'.$genericavatar.'" alt="'.xarML('My avatar').'"  width="'.$width.'" title="'.xarML('My avatar').'"/>&#160;';
        }
        return $out;
    }
    $avatar_type = xarUserGetVar('avatar_type',$uid);
    $avatar_type = (int)$avatar_type;

    $name = xarUserGetVar('name',$uid);

    if (empty($text)) {
        $alttext = isset($name) && !empty($name)? xarML('Member #(1)',$name): xarML('Member #(1)',$uid);
    } else {
        $alttext = $text;
    }

    if (($avatar_type) == 0) {
        if (!is_null($genericavatar)) {
            $out = '&#160;<img src="'.$genericavatar.'" alt="'.$alttext.'"  width="'.$width.'" title="'.$alttext.'"/>&#160;';
        }
        return $out; //no avatar let's return asap

    } elseif (($avatar_type == 4) || ($avatar_type == 2)) { //gravatar or other URL
        //we don't need any other info for now so get it and go
        $useremail = xarUserGetVar('email',$uid);
        //prepare it
        $useremail = trim(strtolower($useremail));
        $emailmd5 = md5($useremail);

        if ($avatar_type == 4) {
            $gravatarurl = 'http://www.gravatar.com/avatar/'.$emailmd5.'.jpg?s='.$width;
            $myavatar= $gravatarurl;
        } else {
            $myavatar = xarUserGetVar('avatar_url');
        }
        if ($link == TRUE) {
            $out = '&#160;<a href="'.xarModURL('roles','user','display',array('uid'=>$uid)).'"><img src="'.$myavatar.'" alt="'.$alttext.'" width="'.$width.'" title="'.$alttext.'"/></a>&#160;';
        } else {
            $out = '&#160;<img src="'.$myavatar.'" alt="'.$alttext.'"  width="'.$width.'" title="'.$alttext.'"/>&#160;';
        }
        return $out;
    }

    //only grab the object and item if we need it
    $roleobject= xarMod::apiFunc('dynamicdata','user','getitem',
                array('module'   => 'roles',
                      'itemtype' => 0,
                      'getobject'=>1,
                      'itemid'=>$uid,
                      'fieldlist' => array('avatar_select','avatar_url','avatar_upload','avatar_type')));

    if (empty($roleobject)) {
        return '';
    }

    switch ($avatar_type) {

        case 1: // Selected
             $myavatar =$roleobject->properties['avatar_select'];

             $basedir = $myavatar->xv_basedir;
             $value = $myavatar->value;
             $baseurl = $myavatar->xv_baseurl;
             $baseurl = isset($baseurl) ? $baseurl : $basedir;

             $srcpath= $baseurl.'/'.$value;

            if (!empty($baseurl) && !empty($value)) {
                if ($link == TRUE) {
                    $out = '&#160;<a href="'.xarModURL('roles','user','display',array('uid'=>$uid)).'"><img src="'.$srcpath.'" width="'.$width.'" alt="'.$alttext.'" title="'.$alttext.'"/></a>&#160;';
                } else {
                    $out = '&#160;<img src="'.$srcpath.'" width="'.$width.'" alt="'.$alttext.'" title="'.$alttext.'"/>&#160;';
                }
            } else {
                $out = '';
            }
            break;

         case 3: // upload
             $myavatar = xarUserGetVar('avatar_upload',$uid);

            $myavatar =$roleobject->properties['avatar_upload'];

            $out = '';
            if ((isset($myavatar->UploadsModule_isHooked) && $myavatar->UploadsModule_isHooked) ) {
                $id = trim(str_replace(';','',$myavatar->value));
                $id = (int)$id;
                   if (isset($id) && $id>0) {
                        $fileurl = xarModURL('uploads', 'user', 'download', array('fileId' => $id));
                        if ($link == TRUE) {
                            $out = '&#160;<a href="'.xarModURL('roles','user','display',array('uid'=>$uid)).'"><img src="'.$fileurl.'" width="'.$width.'" alt="'.$alttext.'" title="'.$alttext.'" /></a>&#160;';
                        } else {
                            $out = '&#160;<img src="'.$fileurl.'" width="'.$width.'" alt="'.$alttext.'" title="'.$alttext.'" />&#160;';
                        }
                   }
            } else {

                $basePath = isset($myavatar->xv_basepath) ?$myavatar->xv_basepath : '';
                $value = $myavatar->value;
                $basedir = $myavatar->xv_basedir;

                $dir = $basePath . ($basedir == '' ? '' : '/') . $basedir;

                $file_path = $dir. '/'. $value;

                $web_root = getcwd();

                if (preg_match('/' . preg_quote($web_root . '/', '/') . '/', $file_path)) {
                    $basedir = dirname(preg_replace('/^' . preg_quote($web_root . '/', '/') . '/', '', $file_path));
                }
                $fileName = basename($value);
                $fileName = xarVarPrepForDisplay($fileName);

               if (!empty($basedir) && !empty($value)) {
                    if ($link == TRUE) {
                        $out = '&#160;<a href="'.xarModURL('roles','user','display',array('uid'=>$uid)).'"><img src="'.$basedir.'/'.$fileName.'" width="'.$width.'" alt="'.$alttext.'" title="'.$alttext.'" /></a>&#160;';
                    } else {
                        $out = '&#160;<img src="'.$basedir.'/'.$fileName.'" width="'.$width.'" alt="'.$alttext.'" title="'.$alttext.'" />&#160;';
                    }
               } else {
                     $out = '';
               }
            }

            break;
        default:  //shouldn't get to here but in case ..
            $out = '';
            break;
    }

    return $out;
}
?>