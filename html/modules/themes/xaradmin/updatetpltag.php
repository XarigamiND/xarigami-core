<?php
/**
 * Update/insert a template tag
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
  *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update/insert a template tag
 * @param tagname
 * @return bool true on success, error message on failure
 * @author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_admin_updatetpltag()
{
    // Get parameters
    if (!xarVarFetch('tag_name', 'str:1:', $tagname)) return;
    if (!xarVarFetch('tag_module', 'str:1:', $module)) return;
    if (!xarVarFetch('tag_handler', 'str:1:', $handler)) return;
    if (!xarVarFetch('tag_action', 'str:1:', $action)) return;

    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0)) return xarResponseForbidden();

    if (!xarSecConfirmAuthKey()) return;
    // find all attributes (if any)
    $aAttributes = array();
    /* This is not implemented and will error - comment until fully implemented
    for ($i=0; $i<10; $i++ ) {
        //xarVarFetch("tag_attrname[$i]", 'isset', $current_attrib);
        if (!xarVarFetch("tag_attrname[$i]", 'isset', $current_attrib,  NULL, XARVAR_DONT_SET)) {return;}

        if (trim($current_attrib) != '') {
            $aAttributes[] = trim($current_attrib);
        }
    }
   */
    // action update = delete and re-add
    // action insert = add
    if ($action == 'update') {
        if(!xarTplUnregisterTag($tagname)) {
            $msg = xarML('Could not unregister (#(1)).', $tagname);
            xarTplSetMessage($msg,'error');

            throw new BadParameterException(null,$msg);
            }
    }

    if(!xarTplRegisterTag($module, $tagname, $aAttributes, $handler)) {
        $msg = xarML('Could not register (#(1)).', $tagname);
        xarTplSetMessage($msg,'error');
       throw new BadParameterException(null,$msg);
    }
    $msg = xarML('Update of tpl tag #(1) was successful',$tagname);
    xarTplSetMessage($msg,'status');

    xarResponseRedirect(xarModUrl('themes', 'admin', 'listtpltags'));

    return true;
}

?>