<?php
/**
 * Base User Version management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Validate the format of a version number against some rule.
 *
 * @author Jason Judge
 * @param $args['ver'] string version number to validate
 * @param $args['rule'] string rule name to validate against
 * @return boolean indicating whether the rule was passed (NULL for parameter error)
            result of validation: true or false
 */
function base_versionsapi_validate($args)
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
    // Rules could include:
    // - numeric only
    // - strict number of levels
    // - implied '0' on empty levels allowed
    $rule = !isset($rule)?'module':$rule;
    if (!isset($ver) || !isset($rule)) {
        return;
    }
    // Set of rules. These can be extended as needed.
    $regex = array();

    // [n].n[.n ...]
    $regex['application'] = '/^\d*\.\d+(\.\d+)*$/';
    // n[.n ...]
    $regex['module'] = '/^\d+(\.\d+)*$/';
    // [n].n[.n-[a|b|rc]n]
    $regex['xarigami'] = '/^([1-9]\d*|0)\.([1-9]\d*|0)\.([1-9]\d*|0)(-(a|b|rc)([1-9]\d*))?$/';

    if (isset($regex[$rule])) {
        if (preg_match($regex[$rule], $ver,$matches)) {
            return true;
        } else {

          return false;
        }
    }

    return;
}

?>