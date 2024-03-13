<?php
/**
 * Remove an alias for a module name
 *
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * remove an alias for a module name
 * (only used for short URL support at the moment)
 *
 * @access public
 * @param aliasModName name of the 'fake' module you want to remove
 * @param modName name of the 'real' module it was assigned to
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_delete_module_alias($args)
{
    extract($args);

    if (empty($aliasModName)) {
        throw new EmptyParameterException('aliasModName');
    }

    $aliases = xarConfigVars::get(null, 'System.ModuleAliases', false);
    if (!$aliases || !isset($aliases[$aliasModName])) return false;
    // don't remove alias if it's already assigned to some other module !
    if ($aliases[$aliasModName] != $modName) return false;
    unset($aliases[$aliasModName]);
    xarConfigSetVar('System.ModuleAliases',$aliases);

    return true;
}

?>
