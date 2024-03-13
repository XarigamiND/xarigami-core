<?php
/**
 * Define a module name as an alias for some other module
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * define a module name as an alias for some other module
 * (only used for short URL support at the moment)
 *
 * @author Xarigami Development Team
 * @access public
 * @param modName name of the 'real' module you want to assign it to
 * @param aliasModName name of the 'fake' module you want to define
 * @returns bool
 * @return true on success, false on failure
 * @throws EmptyParameterException or DuplicateException
 */
function modules_adminapi_add_module_alias($args)
{
    extract($args);

    if (empty($modName)) {
         throw new  EmptyParameterException('modName');
    }
    if (empty($aliasModName)) {
         throw new EmptyParameterException('aliasModName');
    }

    // Check if the module name we want to define is already in use
    if (xarMod::getBaseInfo($aliasModName)) {
        $msg = xarML('Alias already exits for #(1) and module #(2)',$aliasModName,$modName);
        xarTplSetMessage($msg,'error');//don't throw exception as hooks can call this. It is too strong.
        return;
        //throw new DuplicateException(array('module alias',$aliasModName, $modName));
    }else {
        // What about multiple aliases for this module ..

    }

    // Check if the alias we want to set it to *does* exist
    if (!xarMod::getBaseInfo($modName)) return;

    // Get the list of current aliases
    $aliases = xarConfigVars::get(null, 'System.ModuleAliases', false);
    if (!$aliases) {
        $aliases = array();
    }
    if (!empty($aliases[$aliasModName]) && $aliases[$aliasModName] != $modName) {
        $msg = xarML('Module alias #(1) is already used by module #(2)', $aliasModName, $aliases[$aliasModName]);
            xarTplSetMessage($msg,'error');
            return;
    }

    // the direction is fake module name -> true module, not the reverse !
    $aliases[$aliasModName] = $modName;
    xarConfigSetVar('System.ModuleAliases', $aliases);

    return true;
}

?>
