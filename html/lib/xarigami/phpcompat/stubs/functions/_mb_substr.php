<?php
/**
 * Function mb_substr
 * 
 * @package PHP Version Compatibility Library
 * @copyright (C) 2004 by the Xaraya Development Team.
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @copyright (C) 2009 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author - original code by Moxiecode 
 * @copyright 2004-2007, Moxiecode Systems AB 
 */

/**
 * Mimics the mb_substr() found in mbstring extension for php
 * 
 * @link http://php.net/manual/en/function.mb-substr.php
 */

function _mb_substr($str, $start, $len = '', $encoding="UTF-8")
{
    $limit = strlen($str);
    for ($s = 0; $start > 0;--$start) {// found the real start
        if ($s >= $limit)
            break;
        if ($str[$s] <= "\x7F")
            ++$s;
        else {
            ++$s; // skip length
            while ($str[$s] >= "\x80" && $str[$s] <= "\xBF")
                ++$s;
        }
    }
    if ($len == '')
        return substr($str, $s);
    else
        for ($e = $s; $len > 0; --$len) {//found the real end
            if ($e >= $limit)
                break;
            if ($str[$e] <= "\x7F")
                ++$e;
            else {
                ++$e;//skip length
                while ($e < $limit && $str[$e] >= "\x80" && $str[$e] <= "\xBF") 
                    ++$e;
            }
        }
    return substr($str, $s, $e - $s);
}
?>