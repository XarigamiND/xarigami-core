<?php
/**
 * Dynamic data browse function
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * @param array args
 * @return array Filelist
 */
function dynamicdata_adminapi_browse($args)
{
    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    if (empty($args['basedir']) || empty($args['filetype'])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'base directory', 'admin', 'browse', 'dynamicdata');
        throw new EmptyParameterException(null,$msg);
    }

    // Security check - we require OVERVIEW rights here for now...
    if(!xarSecurityCheck('ViewDynamicData')) return;

    // Get arguments from argument array
    extract($args);

    if (empty($filematch)) {
        $filematch = '';
    }
    if (!isset($recursive)) {
        $recursive = true;
    }

    $todo = array();
    $basedir = realpath($basedir);
    $filelist = array();
    array_push($todo, $basedir);
    while (count($todo) > 0) {
        $curdir = array_shift($todo);

        if ($dir = @opendir($curdir)) {
            while(($file = @readdir($dir)) !== false) {
                $curfile = $curdir . '/' . $file;
                if (preg_match("/$filematch\.$filetype$/",$file) && is_file($curfile)) {
                    // ugly fix for Windows boxes
                    $tmpdir = strtr($basedir,array('\\' => '\\\\'));
                    $curfile = preg_replace("#$tmpdir/#",'',$curfile);
                    $filelist[] = $curfile;
                } elseif ($file != '.' && $file != '..' && is_dir($curfile) && !empty($recursive)) {
                    array_push($todo, $curfile);
                }
            }
            closedir($dir);
        }
    }
    return $filelist;
}
?>