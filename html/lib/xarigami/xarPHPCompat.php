<?php
/**
 * PHP Version Compatibility Loader
 *
 * @package core
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage PHP Version Compatibility Library
 * @author Paul Crovella
 */
/**
 * Helper class for PHP compatibilities
 *
 * @package core
 */
class xarPHPCompat
{
    /**
     * Loads given constants and functions not in the current PHP version
     *
     * @access public
     * @param  string $path Path to the phpcompat libraries
     */
    static function loadAll ($path)
    {
        xarPHPCompat::loopDir($path . '/stubs/', 'loadFunction');
        xarPHPCompat::loopDir($path . '/constants/', 'loadConstant');
    }

    /**
     * Loops over a directory applying a loader to each php file
     *
     * @access private
     * @param  string $directory The directory to loop over
     * @param  string $manipulator The xarPHPCompat loader to apply to each .php file
     */
    static function loopDir ($directory, $manipulator)
    {
        $dir = dir($directory);
        while ($file = $dir->read()) {
            if ($pos = strpos($file, '.php')) {
                $loadee = substr($file, 0, $pos);
                xarPHPCompat::$manipulator($directory, $loadee);
            }
        }
        $dir->close();
    }

    /**
     * Loads a workalike function
     *
     * @access private
     * @param  string $path Path to function
     * @param  string $function Name of function to load
     */
    static function loadFunction ($path, $function)
    {
        if (!function_exists($function)) {
            include $path . $function . '.php';
        }
    }

    /**
     * Loads a workalike constant
     *
     * @access private
     * @param  string $path Path to constant
     * @param  string $constant Constant to load
     */
    static function loadConstant ($path, $constant)
    {
        if (!defined($constant)) {
            include $path . $constant . '.php';
        }
    }
}
?>
