<?php
/**
 * Get the list of active event handlers
 *
 * @copyright (C) 2005-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @ xarigami Modules subsystem
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Get the list of active event handlers
 *
 * @author Xaraya Development Team
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @throws NO_PERMISSION
 */
function modules_adminapi_geteventhandlers()
{
    static $check = false;

    //Now with dependency checking, this function may be called multiple times
    //Let's check if it already return ok and stop the processing here
    if ($check) {return true;}

    $modlist = xarMod::apiFunc('modules','admin','getlist',
                             array('filter' => array('State' => XARMOD_STATE_ACTIVE)));

    $todo = array();
    foreach ($modlist as $mod) {
        $modName = $mod['name'];
        $modDir = $mod['osdirectory'];
        // use the directory here, not the name
        $xarapifile =  sys::code()."modules/{$modDir}/xareventapi.php";
        $fileexist= file_exists($xarapifile);
        if (!$fileexist) continue;
         //we need to include the file
         sys::import("modules.{$modDir}.xareventapi");
        $modName = strtolower($modName);
        $todo[$modName] = $modDir;

    }

    $handlers = array();
    if (count($todo) > 0) {
        // get the list of all defined functions
        $functions = get_defined_functions();

        // get the list of all relevant modules
        $filter = join('|', array_keys($todo));
        // see if we have some <module>_eventapi_on<eventname> functions
        foreach ($functions['user'] as $userfunc) {
            if (preg_match("/^($filter)_eventapi_on(.+)$/i", $userfunc, $matches)) {

                $modname = $matches[1];
                $eventname = $matches[2];
                if (!empty($todo[$modname])) {
                    if (!isset($handlers[$eventname])) {
                        $handlers[$eventname] = array();
                    }
                    // save the module directory here too
                    $handlers[$eventname][$modname] = $todo[$modname];
                } else {
                    // ignore event handlers from unknown/inactive modules
                }
            }
        }
    }

    // this gets serialized internally
    xarConfigSetVar('Site.Evt.Handlers',$handlers);

    $check = true;

    return true;
}

?>