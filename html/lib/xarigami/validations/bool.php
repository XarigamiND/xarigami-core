<?php
/**
 * Validate subject as a bool value
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Boolean Validation Function
 * @throws VariableValidationException
**/

class BoolValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if ($subject === true || $subject === 'true' || $subject === 'TRUE') {
             $subject = TRUE;
        } elseif ($subject === false || $subject === 'false' || $subject === 'FALSE') {
             $subject = FALSE;
        } else {
            $msg = xarML('Not a boolean');
            throw new VariableValidationException(null, $msg);
        }

        return true;
    }
}

?>