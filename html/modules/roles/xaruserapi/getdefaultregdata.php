<?php
/**
 * Get the default registraton module and related data if it exists
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * getdefaultregdata  - get the default registration module data
 * @return array  defaultregmodname string, empty if no active registration module
 *                defaultregmodactive boolean, regmodule is active or not
 *
 */
function roles_userapi_getdefaultregdata()
{
    $defaultregdata      = array();
    $defaultregmodname   = '';
    $defaultregmodactive = false;
    //get the default reg module if it exits  - it either does or does not
    $defaultregmodid     =(int)xarModGetVar('roles','defaultregmodule');

    //if it is not set then use registration module
    if (!isset($defaultregmodid) || $defaultregmodid<=0) {
        //user Registration if it's there else display appropriate error
        if (xarMod::isAvailable('registration')) {
           $defaultregmodname   = 'registration';
           $defaultregid = xarMod::getId('registration');
        } else {
            //jojo - we can't throw an exception here as we do know know who is calling.
            //there may  be no need for the registration module - could just be a check
          // $msg = xarML('There is no active registration module installed');

            $defaultregdata=array('defaultregmodname'   => '',
                                    'defaultregmodactive' => FALSE);
            return $defaultregdata;
        }
    } elseif (isset($defaultregmodid)){
        $defaultregmodname = xarMod::getName($defaultregmodid);
        if (xarMod::isAvailable($defaultregmodname)) {
            $defaultregmodname   = $defaultregmodname;
            $defaultregid        = $defaultregmodid;
        } else {
            if (xarMod::isAvailable('registration')) {
                $defaultregmodname   = 'registration';
                $defaultregid = xarMod::getId('registration');
            } else {
                $msg = xarML('There is no active registration module installed');
                  throw new DirectoryNotFoundException(null,$msg);
            }
        }
    }
    xarModSetVar('roles','defaultregmodule', $defaultregid); //set this in case it hasn't been

    //we have reworked this function - leave the returned array for now and set the $defaultregmodactive
    //the function will return an error previously if not now.
    $defaultregmodactive = true;
    $defaultregdata=array('defaultregmodname'   => $defaultregmodname,
                          'defaultregmodactive' => $defaultregmodactive);

    return $defaultregdata;
}
?>