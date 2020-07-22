<?php
/**
 * Update configuration parameters of the module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update configuration parameters of the module
 * This is a standard function to update the configuration parameters of the
 * module given the information passed back by the modification form
 *
 * @return bool and redirect to modifyconfig
 */
function dynamicdata_admin_updateproplist($args)
{
    extract($args);

    if (!xarVarFetch('flushPropertyCache', 'isset', $flushPropertyCache,  NULL, XARVAR_DONT_SET)) {return;}

    // Security Check
    if (!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if (!xarSecConfirmAuthKey()) return;

    if ( isset($flushPropertyCache) && ($flushPropertyCache == true) )
    {
        $args['flush'] = 'true';
        $args['returnerrors'] = 'true';
        //jojo - we don't use the properties here, we just want to know if there were problems and report if relevant
        //let's get a list of errors instead of breaking the propery loading
        $success = xarMod::apiFunc('dynamicdata','admin','importpropertytypes', $args);

        if(is_array($success)) //can be empty
        {
            if (!empty($success)) {
                $msg = xarML('There were problems flushing the property cache. Please review the errors.');
                 xarTplSetMessage($msg,'error');
            } else {

                $msg = xarML('The property cache was successfully flushed and regenerated.');
                 xarTplSetMessage($msg,'status');
            }

            xarResponseRedirect(xarModURL('dynamicdata','admin','manageproplist',array('invalid'=>$success)));
            return true;
        } else {
           // return xarML('Unknown error while clearing and reloading Property Definition Cache.');
        }
    }

    if (!xarVarFetch('label','list:str:',$label,NULL,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('validation','list:str:',$validation,NULL,XARVAR_NOT_REQUIRED)) return;

    if (empty($label) && empty($validation)) {
        xarResponseRedirect(xarModURL('dynamicdata','admin','manageproplist'));
        return true;
    }

    $proptypes = xarMod::apiFunc('dynamicdata','user','getproptypes');

    $dbconn = xarDB::$dbconn;
    $xartable = xarDBGetTables();

    $dynamicproptypes = $xartable['dynamic_properties_def'];

    foreach ($proptypes as $proptype) {
        $id = (int) $proptype['id'];
        if (empty($label[$id])) {
            $query = "DELETE FROM $dynamicproptypes
                            WHERE xar_prop_id = ?";
            $bindvars = array($id);
            $result = $dbconn->Execute($query,$bindvars);
            if (!$result) return;
        } elseif ($label[$id] != $proptype['label'] || $validation[$id] != $proptype['validation']) {
            $query = "UPDATE $dynamicproptypes
                         SET xar_prop_label = ?,
                             xar_prop_validation = ?
                       WHERE xar_prop_id = ?";
            $bindvars = array($label[$id],$validation[$id],$id);
            $result = $dbconn->Execute($query,$bindvars);
            if (!$result) return;
        }
    }
    $msg = xarML('The property table was successfully regenerated.');
    xarTplSetMessage($msg,'status');
    xarLogMessage('DYNAMICDATA: Dynamic Data property list was flushed and updated by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    xarResponseRedirect(xarModURL('dynamicdata','admin','manageproplist'));
    return true;
}

?>