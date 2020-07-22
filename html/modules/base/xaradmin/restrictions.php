<?php
/**
 * Modify site restrictions
 *
 * @package modules
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Modify site configuration
 * @return array of template values
 */
function base_admin_restrictions()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase',0)) return xarResponseForbidden();

    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'restrict', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str:1:100', $data['returnurl'], Null, XARVAR_NOT_REQUIRED)) return;
    xarVarFetch('invalid', 'array',     $invalid, array(), XARVAR_NOT_REQUIRED);

    //Disallowed  - restrictions
    $data['emails'] = unserialize(xarModGetVar('roles', 'disallowedemails'));
    $data['disallowedips'] = unserialize(xarModGetVar('roles', 'disallowedips'));
    $data['disallowednames'] = unserialize(xarModGetVar('roles', 'disallowednames'));

    //Site lock
    // Get parameters from the db
    $lockvars = unserialize(xarModGetVar('roles','lockdata'));
    $toggle = $lockvars['locked'];
    $roles = $lockvars['roles'];
    if (!isset($lockvars['killactive'])) $lockvars['killactive'] = false;
    $killactive = $lockvars['killactive'];
    $lockedoutmsg = (!isset($lockvars['message']) || $lockvars['message'] == '') ? xarML('The site is currently locked. Thank you for your patience.') : $lockvars['message'];
    $notifymsg = $lockvars['notifymsg'];

    $data['roles']        = $roles;
    $data['lockedoutmsg'] = $lockedoutmsg;
     $data['serialroles']  = xarVarPrepForDisplay(serialize($roles));
    $data['notifymsg']    = $notifymsg;
    $data['toggle']       = $toggle;
    $data['killactive']   = $killactive;
    if ($toggle == 1) {
        $data['togglelabel']   = xarML('Unlock the Site');
        $data['statusmessage'] = xarML('The site is locked');
    } else {
        $data['togglelabel']   = xarML('Lock the Site');
        $data['statusmessage'] = xarML('The site is unlocked');
    }
    $data['addlabel']    = xarML('Add a role');
    $data['deletelabel'] = xarML('Remove');
    $data['savelabel']   = xarML('Save the configuration');

    $data['authid'] = xarSecGenAuthKey();

    $data['menulinks'] = xarMod::apiFunc('base','admin','getmenulinks');
    $data['invalid'] = $invalid;
    return $data;
}

?>
