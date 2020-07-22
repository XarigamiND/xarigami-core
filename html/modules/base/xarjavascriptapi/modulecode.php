<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Base JavaScript management functions
 * Include a module JavaScript link in a page.
 *
 * @author Jason Judge
 * @param $args['module'] module name; or
 * @param $args['moduleid'] module ID
 * @param $args['filename'] file name list (comma-separated or array)
 * @param $args['position'] position on the page; generally 'head' or 'body'
 * @return bool true=success; null=fail
 */
function base_javascriptapi_modulecode($args)
{
    extract($args);

    $result = true;

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    }

    // Filename can be an array of files to include, or a
    // comma-separated list. This allows a bunch of files
    // to be included from a source module in one go.
    if (!is_array($args['filename'])) {
        $files = explode(',', $args['filename']);
    }

    foreach ($files as $file) {
        $args['filename'] = $file;
        $filePath = xarMod::apiFunc('base', 'javascript', '_findfile', $args);
        
        if (empty($filePath)) {
            $result = false;
            break;
        }

        // Read the file.
        $fp = fopen($filePath, 'rb');

        if (! $fp) {
            $result = false;
            // Continue with the next file.
            break;
        }
        if (!isset($type)) $type = 'code';
        $code = fread($fp, filesize($filePath));
        fclose($fp);

        $index = !isset($index) || empty($index) ? $filePath : $index;
        // A failure to find a file is recorded, but does not stop subsequent files.
        $newweight = isset($weight)? $weight: 20;
        $result = $result & xarTplAddJavaScript($position, $type, $code, $index, $newweight);
    }

    // False if any one file is not found.
    return $result;
}

?>