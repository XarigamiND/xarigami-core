<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
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
 * @deprecated at 1.3.4 remove at 1.5.0
 */
function base_javascriptapi_modulefile($args)
{
    extract($args);

    $result = true;

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    } else {
        $position = addslashes($position);
    }
    $type = isset($type) && !empty($type) ?addslashes($type) :'body';
    $weight = isset($weight) ? $weight: 10;
    // Filename can be an array of files to include, or a
    // comma-separated list. This allows a bunch of files
    // to be included from a source module in one go.
    if (!is_array($args['filename'])) {
        $files = explode(',', $args['filename']);
    }

    foreach ($files as $file) {
        $file = trim($file);
        if (substr($file,-3) != '.js') {
            $file = $file.'.js'; //make sure there is a js extension
        }
        $args['filename'] = addslashes($file);
        $filePath = xarMod::apiFunc('base', 'javascript', '_findfile', $args);
        // A failure to find a file is recorded, but does not stop subsequent files.
        if (!empty($filePath)) {
            $index = isset($index) && !empty($index) ? $index : $filePath;
            $filePath = xarServer::getBaseURL() . $filePath;
            $result = $result & xarTplAddJavaScript($position, $type, $filePath, $index, $weight);
        } else {
            $result = false;
        }
    }

    return $result;
}

?>
