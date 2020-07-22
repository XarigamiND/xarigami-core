<?php
/**
 * Function json_decode
 * @package PHP Version Compatibility Library
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Cache package
 * @copyright (C) 2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */

/**
 * Mimics the json_encode() function introduced in PHP 5.2.0
 * @link http://www.php.net/manual/en/function.json-encode.php
 */

function _json_encode($tojson =false)
{
    // Some basic debugging to ensure we have something returned
    if (is_null($tojson)) return 'null';
    if ($tojson  === false) return 'false';
    if ($tojson  === true) return 'true';
    if (is_scalar($tojson))
    {
        if (is_float($tojson))
        {
            // Always use '.' for floats.
            return floatval(str_replace(',', '.', strval($tojson)));
        }
        if (is_string($tojson))
        {
            static $jsonReplaces = array(array('\\', '/', "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
            return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $tojson) . '"';
        } else {
            return $tojson;
        }
    }
    $isList = true;
    for ($i = 0, reset($tojson); true; $i++) {
        if (key($tojson) !== $i)
        {
            $isList = false;
            break;
        }
    }
    $result = array();
    if ($isList)
    {
        foreach ($tojson as $v) $result[] = json_encode($v);
        return '[' . join(',', $result) . ']';
    } else {
        foreach ($tojson as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
        return '{' . join(',', $result) . '}';
    }
}

?>
