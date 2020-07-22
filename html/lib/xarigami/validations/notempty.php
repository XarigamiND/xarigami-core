<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/

/**
 * notempty Validation Function
 * @param subject
 * @param parameters
 * @return bool true if not empty, false if empty
 */
class NotEmptyValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        if (empty($subject)) {
            $msg = xarML('Variable is empty');
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}

?>