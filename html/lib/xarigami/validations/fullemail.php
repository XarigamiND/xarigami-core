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
//sys::import('xarigami.xarValidations');
/**
 * Full Email Check -- Checks first thru the regexp and then by mx records
 * @return bool true if fullemail, false if not
 */
class FullEmailValidation extends EmailValidation
{
    function validate(&$subject, Array $parameters)
    {
        if (parent::validate($subject,array()) && xarVarValidate ('mxcheck', $subject)) {
            return true;
        }
        return false;
    }
}

?>