<?php
/**
 * Base User Version management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Blocks module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * Asserts that the Xarigami application version has reached a certain level.
 *
 * @author Jason Judge
 * @param $args['ver'] string version number to compare
 * @return boolean result of test: true or false
           indicating whether the application is at least version $ver
 */
function base_versionsapi_assert_application($args)
{
    extract($args, EXTR_PREFIX_INVALID, 'p');

    if (!isset($ver)) {
        if (isset($p_0)) {
            $ver = $p_0;
        } else {
            // The given version number is missing
             $msg = xarML('The application version number was not provided in base_versionsapi_assert_application');
             throw new EmptyParameterException(null,$msg);
        }
    }

    $ok = xarMod::apiFunc('base','versions','validate',array('ver'=>$ver));

    if (!$ok) { return false; }

    $result = xarMod::apiFunc('base', 'versions', 'compare',
        array(
            'ver1' => $ver,
            'ver2' => xarConfigGetVar('System.Core.VersionNum'),
            'normalize' => 'numeric'
        )
    );

    if ($result < 0) {
        // The supplied version is greater than the system version.
        $msg = xarML('The application version is too low; version #(1) or later is required.', $ver);
        throw new BadParameterException(null,$msg);
    }
    return true;
}

?>
