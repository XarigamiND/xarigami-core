<?php
/**
 * Base JavaScript management functions
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xarigami.com/projects/xartinymce
 * /

/**
 * Handle <xar:base-include-javascript libname="$somelib" libfile="$somefile" plugin="$plugin" pluginfile="$pluginfilename" style="$pluginstyle" />
 *
 * @param string $args['code']          String containing JS code
 * @param string $args['filename']      Name of the js file or list of files separated by commas
 * @param string $args['module']        Name of module containing the file
 * @param string $args['position']      Position to place the js (default= 'head')
 * @param string $args['index']         Unique index
 * @param string $args['libname']       Library name  points to scripts/[libname]
 * $param string $args['libfile']       Name of the library/framework file if different to libname
 * @param string $args['plugin']        Name of the plugin, points to scripts/[$libname]/[$plugin]
 * @param string $args['pluginfile']    Name of the plugin filename(s) if different to $plugin as name or comma separated list
 * $param string $args['weight']        Weight of the js correlates to order of load.
 * @param string $args['style']         Name of the plug style file or files to load (comma separated) looks in the plugin dir or theme style dir
 * @return bool true=success; null=fail
 * @deprecated at 1.3.4 remove at 1.5.0
 */
function base_javascriptapi_libraryfile($args)
{
    extract($args);

    if (!isset($plugin) && !isset($libname)) return false;

    $result = true;

    if (isset($plugin) && !empty($plugin) && isset($libname)) {
        $plugin =trim($plugin);
        $libname = trim($libname);
         //do any plugin styles
        if (isset($style) && !empty($style)) {
            if(is_string($style)) {
                $stylefiles = explode(',', $style);
            }
            foreach ($stylefiles as $stylesheet) {
                //themes_userapi_register
                $file = $libname . '/plugins/' . $plugin . '/' . preg_replace('/\.css$/', '', $stylesheet);
                $provider = (isset($module) && !empty($module)) ? $module :'';
                $styleload = xarMod::apiFunc('themes','user','register', array(
                    'scope' => 'script',
                    'module' => $provider,
                    'file' => $file
                ));
            }
        }
    }
    //Try loading the framework if there is one specified in case it's not already loaded
    //there is an alternative libfile to the library name
    if (isset($libfile) && !empty($libfile)) { //there is an alternative framework script
        $libfile = trim($libfile);
        $libfilename = $libname.'/'.$libfile;
    } else {
        $libfilename = $libname.'/'.$libname;
    }
    if (substr($libfilename,-3) != '.js') {
        $libfilename = $libfilename.'.js'; //make sure there is a js extension
    }

    $filePath = xarMod::apiFunc('base', 'javascript', '_findfile', array('filename'=>$libfilename));
    if (!empty($filePath)) {
        //check the load order
        $newweight = $weight;
        if (isset($pluginfile) && !empty($pluginfile)) { //we're loading plugin too - make sure the libfile loads first
            //arbitrary but we set it to ensure library files are always less than the default 10
            //if you need a specific weight set it, the plugin will always be greater than default 10
            //TODO - work out a more robust way to do this without hard coding.
            if ($newweight>=10) $newweight = 9;
        }
        $fullfilePath = xarServer::getBaseURL() . $filePath;
        $result = xarTplAddJavaScript($position,$type, $fullfilePath, $filePath, $newweight);
    }

    //if there is some other plugin file names load them along with the library filename
    if (isset($pluginfile) && !empty($pluginfile) && is_string($pluginfile)) {
       $pluginlist = explode(',',$pluginfile);
       $weightcount = 10;
       foreach ($pluginlist as $pluginname) {
            $pluginname= trim($pluginname);
            $filename = $libname.'/plugins/'.$plugin.'/'.$pluginname;
            if (substr($filename,-3) != '.js') {
                $filename = $filename.'.js'; //make sure there is a js extension
            }
            $args['filename'] = $filename;
            $thisfilePath = xarMod::apiFunc('base', 'javascript', '_findfile', $args);

            if (!empty($filePath)) {
               $thisindex = isset($index) && !empty($index) ? $index : $thisfilePath;
                $filePath = xarServer::getBaseURL() . $thisfilePath;
                //plugins are always loaded with weight> default currently 10
                $result = $result & xarTplAddJavaScript($position, $type, $filePath, $thisindex, $weight+$weightcount);
                $weightcount = $weightcount+1; //we are making sure plugins are loaded in order listed
            } else {
                $result = false;
            }
        }
    } else {
        $filename = $libname.'/plugins/'.$plugin.'/'.$plugin;
        $args['filename'] = $filename;
        $filePath = xarMod::apiFunc('base', 'javascript', '_findfile', $args);

        if (!empty($filePath)) {
               $index = isset($index) && !empty($index) ? $index : $filePath;
                $filePath = xarServer::getBaseURL() . $filePath;
                $result = $result & xarTplAddJavaScript($position, 'src', $filePath, $index, $weight+10);
        } else {
            $result = false;
        }
    }

    return $result;
}
?>
