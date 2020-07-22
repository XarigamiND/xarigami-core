<?php
/**
 * validate a parameter as a valid file name
 *
 * @package validation
 * @copyright (C) 2009-2011 2skies.com
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xarigami.com/project/xarigami_core
*/
/**
 * Validate a file name
 * Cannot contain \ / : * ? " < > |
 * @return bool true if email, false if not
 */
class FilenameValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
         if (preg_match('/[\\/\:\*\?"<>\|]/', $subject)) {

            $msg = xarML('Not a valid filename format');
            throw new VariableValidationException(null, $msg);
        }
        return true;
    }
}
?>