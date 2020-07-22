<?php

/**
* xarTpl__XarVarNode: <xar:var> tag class
 *
 *
 * @package blocklayout
 */
class xarTpl__XarVarNode extends xarTpl__TplTagNode
{
    function render()
    {
        $scope = 'local';
        $prep = FALSE;
        $user = xarUserGetVar('uid');
        extract($this->attributes);

        if (!isset($name)) {
            $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'name\' attribute in <xar:var> tag.');
            return;
        }

        $prefix = ''; $postfix = '';
        if(strtolower($prep) == 'true') {
            $prep = TRUE;
            $prefix = "xarVarPrepForDisplay(";
            $postfix = ")";
        }

        // Allow specifying name="test" and name="$test" and deprecate the $ form over time
        if(substr($name,0,1) == XAR_TOKEN_VAR_START) $name = substr($name, 1);

        switch ($scope) {
            case 'config':
                $scope = isset($scope) ? $scope : NULL;
                $value = "xarConfigVars::get('".$scope."','".$name."')";
                break;
            case 'session':
                $value = "xarSession::getVar('".$name."')";
                break;
            case 'user':
                $user = xarTpl__ExpressionTransformer::transformPHPExpression($user);
                $value = "xarUserGetVar('".$name."',".$user.")";
                break;
            case 'module':
                if (!isset($module)) {
                    $this->raiseError(XAR_BL_MISSING_ATTRIBUTE,'Missing \'module\' attribute in <xar:var> tag.');
                    return;
                }
                $value = "xarModVars::get('".$module."', '".$name."')";
                break;
            case 'theme':
                if (!isset($themeName)) {
                    $themeName = xarTpl::getThemeName();
                }
                $transform = isset($transform) ? $transform : FALSE;
                $throw = isset($throw) ? $throw : FALSE;
                $value = "xarThemeVars::get('".$themeName."', '".$name."',  '".$transform."', '".$throw."')";
                break;
            case 'skin':
                if (!isset($themeName)) {
                    $themeName = xarTpl::getThemeName();
                }
                $target = isset($target) ? $target : NULL;
                $transform = isset($transform) ? $transform : FALSE;
                $value = "xarSkinVars::get('".$themeName."', '".$name."',  '".$target."', '".$transform."')";
                break;
            case 'request':
                $value = 'xarRequest::getVar("'.$name.'")';
                break;
            case 'local':
                // Resolve the name, note that this works for both name="test" and name="$test"
                $value = xarTpl__ExpressionTransformer::transformPHPExpression(XAR_TOKEN_VAR_START . $name);
                if (!isset($value)) return; // throw back
                    break;
            default:
                $this->raiseError(XAR_BL_INVALID_ATTRIBUTE,'Invalid value for \'scope\' attribute in <xar:var> tag.');
                return;
        }
        return $prefix . $value . $postfix;
    }
}
?>