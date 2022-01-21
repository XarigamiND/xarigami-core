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
 * HTML Validation Class
 * @return bool true if html, false if not
 */
class HtmlValidation extends ValueValidations
{
    function validate(&$subject, Array $parameters)
    {
        assert(($parameters[0] == "restricted" ||
                 $parameters[0] == "basic" ||
                 $parameters[0] == "enhanced" ||
                 $parameters[0] == "admin"));

        if ($parameters[0] == 'admin') {
            return true;
        }

        $allowedTags = array();
        foreach (xarConfigVars::get(null,'Site.Core.AllowableHTML') as $k=>$v) {
            if ($v) {
                $allowedTags[] = $k;
            }
        }
        preg_match_all("|</?(\w+)(\s+.*?)?/?>|", $subject, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $tag = strtolower($match[1]);
            if (!isset($allowedTags[$tag])) {
                $msg = xarML('Specified tag is not allowed');
                throw new VariableValidationException(null, $msg);
            } elseif (isset($match[2]) && $allowedTags[$tag] == XARVAR_ALLOW_NO_ATTRIBS && trim($match[2]) != '') {
                // We should check for on* attributes
                // Attributes should be restricted too, shouldnt they?
                $msg = xarML("Attributes are not allowed for tag '#(1)'",$tag);
                throw new VariableValidationException(null,$msg);
            }
        }
        return true;
    }
}

?>
