<?php
/**
 * Check a roles DUV
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * 
 * @subpackage Xarigami Roles
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team 
 */
/**
 * checkduv - perform an active check on a DUV
 * @author - Marc Lutolf
 * @param $args['name'] name of the duv
 * @param $args['check'] type of check to be performed
 * @return boolean
 * @TODO Check the usefulness of this function. It seems redundant now that state is held in a separate modvar
 */
function roles_adminapi_checkduv($args)
{
    extract($args);
    if (!isset($name)) return false;
    $state = isset($state) ? $state : 0;

    switch ($state) {
        case 0 :
            $result = false;
            $duvs = xarModGetVar('roles',$name);
            if ($duvs) {
                $result = true;
            }
            break;
        case 1 :
        default:
            // TODO: investigate how this case would differ now or
            //   how the State has been handled since conversion to moduservars
            $result = false;
            $duvs = xarModGetVar('roles',$name);
            if ($duvs) {
                $result = true;
            }
    }
    return $result;
}

?>