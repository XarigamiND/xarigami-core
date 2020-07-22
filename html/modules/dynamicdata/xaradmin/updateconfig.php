<?php
/**
 * Update configuration parameters of the module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update configuration parameters of the module
 * @return bool and redirect to modifyconfig
 */
function dynamicdata_admin_updateconfig($args)
{
    if (!xarVarFetch('itemsperpage', 'int',      $itemsperpage, 20, XARVAR_NOT_REQUIRED)) return; //value of 0 will break in some places
    if (!xarVarFetch('useritemsperpage', 'int',      $useritemsperpage, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('systemobjects',  'array', $systemobjects,array(1,2),XARVAR_NOT_REQUIRED)) return;

    if (!xarSecConfirmAuthKey()) return;
    xarModSetVar('dynamicdata', 'itemsperpage', $itemsperpage);
    xarModSetVar('dynamicdata', 'useritemsperpage', $useritemsperpage);
    //check systemobjects and ensure we have master objects there (objects, properties)
    //bleh - don't like hard coding here, review
    $masterarray = array(1,2);
    foreach($masterarray as $obids) {
        if (!in_array($obids,$systemobjects)) {
            $systemobjects[] = $obids;
        }
    }
    sort($systemobjects);
    xarModSetVar('dynamicdata', 'systemobjects', serialize($systemobjects));


    xarMod::callHooks('module','updateconfig','dynamicdata',
                   array('module' => 'dynamicdata'));

    $msg = xarML('Dynamic data module configuration has been successfully updated.');
    xarTplSetMessage($msg,'status');
     xarLogMessage('DYNAMICDATA: Configuration settings were modified by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyconfig'));

    return true;
}

?>