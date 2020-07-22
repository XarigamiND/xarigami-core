<?php
/**
 * Test a user or group's privileges against a mask
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * testprivileges - test a user or group's privileges against a mask
 *
 * Performs a test of all the privileges of a user or group against a security mask.
 * A security mask defines the hurdle a group/user needs to overcome
 * to gain entrance to a given module component.
 *
 * @access public
 * @param none $
 * @return none
 * @throws none
 */
function roles_admin_testprivileges()
{
    // Get Parameters
    if (!xarVarFetch('uid',     'int:1:',    $uid)) return;
    if (!xarVarFetch('pmodule', 'str:1:',    $module,  '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('name',    'str:1',     $name,    '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('test',    'str:1:35:', $test,    '', XARVAR_NOT_REQUIRED,XARVAR_PREP_FOR_DISPLAY)) return;

    // Security Check - don' tthrow an exception, handle the response
    if (!xarSecurityCheck('EditRole',0,'Roles',$uid)) return xarResponseForbidden();

    // Call the Roles class and get the role
    $roles = new xarRoles();
    $role  = $roles->getRole($uid);

    // get the array of parents of this role
    // need to display this in the template
    $parents = array();
    foreach ($role->getParents() as $parent) {
        $parents[] = array('parentid'   => $parent->getID(),
                           'parentname' => $parent->getName());
    }
    $data['parents'] = $parents;

    // Call the Privileges class and
    // get a list of all modules for dropdown display
    $privileges = new xarPrivileges();
    $allmodules = $privileges->getmodules();
    // Call the Masks class
    $masks = new xarMasks();
    // we want to do test
    if (!empty($test)) {
        // get the mask to test against
        $mask = $masks->getMask($name);
        $component = $mask->getComponent();
        // test the mask against the role
        $testresult = $masks->xarSecurityCheck($name, 0, $component, 'All', $mask->getModule(), $role->getName());
        // test failed
        if (!$testresult) {
            $resultdisplay = xarML('Privilege: none found');
        }
        // test returned an object
        else {
            $resultdisplay = "";
            $data['rname']      = $testresult->getName();
            $data['rrealm']     = $testresult->getRealm();
            $data['rmodule']    = $testresult->getModule();
            $data['rcomponent'] = $testresult->getComponent();
            $data['rinstance']  = $testresult->getInstance();
            $data['rlevel']     = $masks->levels[$testresult->getLevel()];
        }
        // rest of the data for template display
        $data['testresult'] = $testresult;
        $data['resultdisplay'] = $resultdisplay;
        $testmasks = array($mask);
        $testmaskarray = array();
        foreach ($testmasks as $testmask) {

            $thismask = array('sname'      => $testmask->getName(),
                              'srealm'     => $testmask->getRealm(),
                              'smodule'    => $testmask->getModule(),
                              'scomponent' => $testmask->getComponent(),
                              'sinstance'  => $testmask->getInstance(),
                              'slevel'     => $masks->levels[$testmask->getLevel()]
                              );
            $testmaskarray[] = $thismask;
        }
        $data['testmasks'] = $testmaskarray;
        $module = $mask->getModule();
    }
    // no test yet
    // Load Template
    $data['test']       = $test;
    $data['pname']      = $role->getName();
    $data['ptype']      = $role->getType();
    $data['pmodule']    = $module;
    $data['uid']        = $uid;
    $data['allmodules'] = $allmodules;
    $data['testlabel']  = xarML('Test');

    if (empty($module)) $data['masks'] = array();
    else $data['masks'] = $masks->getmasks(strtolower($module));
    $data['authid'] = xarSecGenAuthKey('roles');
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('roles','admin','getmenulinks');       
    return $data;
    // redirect to the next page
    xarResponseRedirect(xarModURL('roles', 'admin', 'newrole'));
}

?>