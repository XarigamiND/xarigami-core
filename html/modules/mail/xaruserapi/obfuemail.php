<?php
/**
 * Obfuscation function
 *
 * @package Xarigami
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2003-2011 2skies.com
 * @link http://xarigami.com/project/xarigami
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */

/**
 * Obfuscation for email - another method
 *
 * @author Jo Dalle Nogare
 * Takes an email address, optional text  for the link text
 * If no text supplied then the email address is used for the link text and partial obfuscated for the display
 * @$param text $email email address to be encoded
 * @$param text $text optional text string to be displayed in email link
 * @$param boolean $image optional flag to display email image, false by default
 * @$param int $obmethod optional method of obfuscation
 *      default - 0/none : bin2hex, 1 - rot13 and js: only full link return is useful
 * @return array $maildata with values of
 *    $maildata['encoded'] the encoded email
 *    $maildata['text'] the text displayed, defaults to slight obfuscated replaced email address
 *    $maildata['link'] full link with displayed text if required
 */
function mail_userapi_obfuemail($args)
{
extract($args);

    if (!isset($email) || empty($email)) {return;}
    $newemail = $email;
    $maildata = array();
    $img = xarTplGetImage('icons/mail.png','base');
    $encoded = bin2hex($newemail);
    $encoded = chunk_split($encoded, 2, '%');
    $encoded = '%' . substr($encoded, 0, strlen($encoded) - 1);
    $maildata['encoded']=$encoded;
    if (!isset($text)) $text = '';
    $maildata['text']  = '';

    if (!empty($text)) {
            $maildata['text']=$text;
    }else{
        $newaddress = '';
        for($intCounter = 0; $intCounter < strlen($email); $intCounter++){
            $newaddress .= "&#" . ord(substr($email,$intCounter,1)) . ";";
        }
        $newtext=explode("&#64;", $newaddress);
        $at = xarML(' AT ');
        $dot = xarML(' DOT ');

            $maildata['text'] = $newtext[0].$at. str_replace("&#46;",$dot,$newtext[1]);
    }

    if (isset($image) && TRUE==$image) {

      if (!empty($maildata['text'])) {
           $maildata['link']= "<a href=\"mailto:{$maildata['encoded']}\"><img src=\"{$img}\" alt=\"{$maildata['text']}\" />&#160;".$maildata['text'] . "</a>";
      } else {
            $maildata['link']= "<a href=\"mailto:{$maildata['encoded']}\"><img src=\"{$img}\" alt=\"{$maildata['text']}\" title=\"{$maildata['text']}\" /></a>";
      }
    }else {
        $maildata['link']= "<a href=\"mailto:{$maildata['encoded']}\">" .$maildata['text'] . "</a>";
    }

    $noscript = '<noscript>'.$maildata['link'].'</noscript>';

    if (isset($obmethod) && ($obmethod == 1)) {
        $newdata = array();
        $encoded = str_rot13($newemail);
        if (empty($text)) $text = $newemail;
        $newdata['text']=$text;
        if (isset($image) && TRUE==$image) {
           if (!empty($text)) {
                $encrypted = str_rot13('<a href="mailto:'.$newemail.'"><img src=\"'.$img.'\" alt=\"'.$text.'\" />&#160;'.$text.'</a>');
            } else {
                $encrypted = str_rot13('<a href="mailto:'.$newemail.'"><img src=\"'.$img.'\" alt=\"'.$alt.'\" title=\"'.$alt.'\" /></a>');
            }
        } else {
            $encrypted = str_rot13('<a href="mailto:'.$newemail.'">'.$text.'</a>');
        }
        $jsstring  = '<script type="text/javascript">';

        //load the js
         xarMod::apiFunc('base', 'javascript', 'modulefile',
                 array('module'=>'mail',
                    'filename'=>addslashes('rot13.js'),
                  'position'=>'head'));

        $newdata['link'] = '<script type="text/javascript">Rot13.write(\''.$encrypted.'\');</script>'.$noscript;
        $maildata = $newdata;
    }

    return $maildata;
}
?>