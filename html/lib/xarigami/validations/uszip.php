<?php
/**
 * validate a parameter as a United States ZIP code
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/
/**
 * Validate a United States ZIP code
 * @return bool true if email, false if not
 */
class USZipValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (!preg_match('/^\d{5}$|^\d{5}-\d{4}$/', $subject)) {
                $msg = xarML('Variable does not match ZIP code type: "#(1)"', $subject);
                throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}

?>
