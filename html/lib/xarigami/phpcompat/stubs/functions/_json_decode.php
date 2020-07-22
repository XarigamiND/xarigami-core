<?php
/**
 * Function json_decode
 * @package PHP Version Compatibility Library
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2011-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

/**
 * Mimics the json_decode() function introduced in PHP 5.2.0
 * @link http://www.php.net/manual/en/function.json-decode.php
 * http://us2.php.net/manual/en/function.json-decode.php#100740
 */

function _json_decode($json)
{
    $comment = false;
    $out = '$x=';
    $s = strlen($json);
    for ($i=0; $i<$s; $i++) {
        if (!$comment) {
            if (($json[$i] == '{') || ($json[$i] == '['))
                $out .= ' array(';
            else if (($json[$i] == '}') || ($json[$i] == ']'))
                $out .= ')';
            else if ($json[$i] == ':')
                $out .= '=>';
            else
                $out .= $json[$i];
        }
        else
            $out .= $json[$i];
        if ($json[$i] == '"' && $json[($i-1)]!="\\")
            $comment = !$comment;
    }
    eval($out . ';');
    return $x;
}

?>