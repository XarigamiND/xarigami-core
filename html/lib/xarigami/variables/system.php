<?php
/**
 * Class to handle system variables
 *
 * @package core
 * @subpackage variables
  * @copyright (C) 2007-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author Marcel van der Boom <mrb@hsdev.com>
 */
sys::import('xarigami.xarVar');

final class xarSystemVars extends xarVars implements IxarVars
{
    private static $__key = 'System.Variables';

    /**
     * A safe way to preload config vars from the core and to raise an error
     */
    public static function init($scope = NULL, $whattoload = NULL)
    {
        if ($scope === NULL) $scope = sys::CONFIG;
        try {
            self::preload($scope);
        } catch (Exception $e) {
            sys::failsafe('<p>Your configuration file '.$scope.' appears to be missing. <br />
It may mean that your site has not been installed correctly.
<br/>Try to <a href="install.php">run install now</a>. <br />Alternatively refer to the
<a href="http://xarigami.org/resources/installing_xarigami">Xarigami installation</a>
documentation or <a href="http://xarigami.org/forums">Xarigami forums</a> for assistance.</p>');
        }
    }

    /**
     * Gets a core system variable
     *
     * @param  string $scope base filename which holds the system variables
     * @param  string $name name of core system variable to get
     * @throws Exception
     */
    public static function get($scope, $name, $except = FALSE)
    {
        if(!isset($scope)) $scope = sys::CONFIG;
        $key = self::$__key . '.' . $scope;

        if (!isset(parent::$_iscached[$key])) {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }
        $cache = &parent::$_cache[$key];
        $iscached = &parent::$_iscached[$key];
        if (empty($iscached)) self::preload($scope);

        if (!isset($iscached[$name])) {
            if ($except) {
                return NULL;
            } else {
                if (function_exists('xarML')) {
                    $msg = xarML("Unknown System Configuration variable '#(1)'. Please check to ensure it is correctly defined in #(2)", $name, $scope);
                } else {
                    $msg = "Unknown System Configuration variable:'". $name."'. Please check to ensure it is correctly defined in your ".$scope." file";
                }
                throw new Exception($msg);
            }
        }
        return $cache[$name];
    }

    /**
     * xarSystemVars::set()
     * Set a core system variable
     *
     * @param string $scope the config filename used
     * @param string $name the variable value
     * @param mixed $value the value
     * @throws FileNotFoundException
     */
    public static function set($scope, $name, $value)
    {
        if(!isset($scope)) $scope = sys::CONFIG;
        $key = self::$__key . '.' . $scope;

        if (self::get($scope, $name) == $value) return FALSE;

        $configfile = sys::varpath() . '/'.$scope;
        try {
            $config_php = join('', file($configfile));

            $config_php = preg_replace('/\[\''.$name.'\'\]\s*=\s*(\'|\")(.*)\\1;/',  "['".$name."'] = '$value';", $config_php);
            $fp = fopen($configfile, 'wb');
            fwrite($fp, $config_php);
            fclose ($fp);
            // Store it in the array
            parent::$_cache[$key][$name] = $value;
            parent::$_iscached[$key][$name] = TRUE;
            return TRUE;
        } catch (Exception $e) {
            throw new FileNotFoundException($configfile);
        }
    }

    public static function delete($scope, $name)
    {
        // Not supported ?
        return false;
    }

    private static function preload($scope)
    {
        $arr = array(sys::CONFIG => 'systemConfiguration', sys::CONFIG_EXT => 'extConfiguration');
        if (!array_key_exists($scope, $arr)) throw new Exception('Unknown configuration file');

        $sbFile = new xarPhpSandbox(sys::varpath().'/'.$scope, $arr[$scope]);
        if (!$sbFile->isValid() && $scope === sys::CONFIG) {
            if (function_exists('xarML')) {
                $msg = xarML('System configuration file could not be found at : #(1)', $scope);
            }  else {
                $msg = 'System configuration file could not be found a: ' . $scope;
            }
            throw new Exception($msg);
        }

        $key = self::$__key . '.' . $scope;
        if ($sbFile->isValid()) {
            $ret = $sbFile->import();
            parent::$_cache[$key] = reset($ret);
            foreach (array_keys(parent::$_cache[$key]) as $name) {
                parent::$_iscached[$key][$name] = TRUE;
            }
        } else {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }
    }
}
?>