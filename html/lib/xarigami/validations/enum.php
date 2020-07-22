<?php
/**
 * Validate a file as enum
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
*/

/**
 * Enum Validation Function
 * @throws VariableValidationException
**/
class EnumValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        $found = false;
        foreach ($parameters as $param) {
            if ($subject == $param) {
                $found = true;
            }
        }

        if ($found) {
            return true;
        } else {
            $msg = xarML('Value is not in the list of valid options ');
            $first = true;
            foreach ($parameters as $param) {
                if ($first) {
                    $first = false;
                } else {
                    $msg .= ' or '; // TODO: evaluate MLS consequences later on
                }
                $msg .= $param;
            }
            throw new VariableValidationException(null, $msg);
        }
    }
}
?>
