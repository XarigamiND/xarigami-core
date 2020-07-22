<?php
/**
 * validate a parameter as a north american phone number
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/
/**
 * Validate a north american phone number
 * @return bool true if valid, false if not
 * @see http://en.wikipedia.org/wiki/North_American_Numbering_Plan
 */

class NanPhoneValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
         if (!preg_match('/^((\([2-9][0-8]\d\) ?)|([2-9][0-8]\d-))?[2-9]\d{2}-\d{4}$/i', $subject)) {
                $msg = xarML('Variable does not match the required phone number format: "#(1)"', $subject);
                throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}

?>
