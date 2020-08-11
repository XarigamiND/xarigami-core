<?php
/**
 * Theme variable handling
 *
 * @package core
 * @subpackage variables
 * @copyright (C) 2007-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

/**
 * Interface declaration for theme vars
 *
 */
sys::import('xarigami.xarVar');

interface IxarThemeVars
{
    static function get       ($scope, $name, $transform = NULL, $throw = FALSE);
    static function set       ($scope, $name, $prime = 0, $value, $description = '');
    static function delete    ($scope, $name);
    static function delete_all($scope);
    static function signature ($scope);
}

interface IxarSkinVars
{
    static function get       ($scope, $name, $transform = NULL, $target = NULL);
    static function set       ($scope, $name, $value, $target = NULL);
    static function delete    ($scope, $name, $target = NULL);
    static function delete_all($scope);
    static function signature ($scope, $target = NULL);
}

class xarThemeVars extends xarVars implements IxarThemeVars
{
    private static $_preloaded = array(); // Keep track of what theme vars (per theme) we already had

    private static $__key = 'Theme.Variables';

    private static $__initDone = FALSE;

    private static $__dbConn = NULL;
    private static $__themevarsTable = NULL;
   // private static $__useDbCache = FALSE;


    public static function init($args = NULL, $whattoload = NULL)
    {
        // @TODO: handle things with multiple connections passed in args
        // We would need to also dissociate the caching to not mix several configs. We can then use eventually the scope for the connection index.
        if (!self::$__initDone || $args !== NULL) {
            // Configuration init needs to be done first
            if (!class_exists('xarDB')) sys::import('xarigami.xarDB');
            $tables = &xarDB::$tables;
            self::$__themevarsTable = $tables['theme_vars'];
            self::$__dbConn = xarDB::$dbconn;
            self::$__initDone = TRUE;
            //self::$__useDbCache = xarSystemVars::get(null,'DB.UseADODBCache');
        }
    }

    /**
     * get a theme variable
     *
     * @param  string $scope The name of the theme
     * @param  string $name  The name of the variable
     * @param  bool $prep  return prepped by config for output
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     * @todo the silent spec of itemid is a bit hacky
     */
    public static function get($scope, $name, $transform = NULL, $throw = FALSE)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');
        if (empty($name)) throw new EmptyParameterException('name');

        if ($transform == TRUE) {
            $args = array('themename'=> $scope, 'varname'=>$name, 'transform'=>1, 'throw'=>$throw);
            return xarThemeGetConfig($args);
        }

        // if prep is not set, lets first check to see if any of our type vars are already set in the cache.
        $key = self::$__key . '.' . $scope;

        if (isset(parent::$_iscached[$key][$name])) return parent::$_cache[$key][$name];

        // Preload per module, once
        if (!isset(self::$_preloaded[$scope])) {
            self::preload($scope);
            if (isset(parent::$_iscached[$key][$name])) return parent::$_cache[$key][$name];
        }

        if (!isset(parent::$_iscached[$key])) {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }
        $cache = &parent::$_cache[$key];
        $iscached = &parent::$_iscached[$key];

        // Still no luck, let's do the hard work then
        $modBaseInfo = xarMod::getBaseInfo($scope, $type='theme');

        if (!self::$__initDone) self::init();

        $query = 'SELECT xar_name, xar_value FROM '.self::$__themevarsTable.
                ' WHERE xar_themeName = ? AND xar_name = ?';

        $bindvars = array($scope, $name);

       // if (self::$__useDbCache){
       //     $result = self::$__dbConn->CacheExecute(3600*24*7,$query,$bindvars);
       // } else {
            $result = self::$__dbConn->Execute($query,$bindvars);
       // }

        if (!$result) return NULL;
        while (!$result->EOF) {
            list($name, $value) = $result->fields;
            $cache[$name] = $value;
            $iscached[$name] = TRUE;
            $result->MoveNext();
        }
        if (!isset($value)) return;
        $result->close();
        return $value;
    }

        /**
     * getall the theme variables for a given scope
     *
     * @param  string $scope The name of the theme
     * @param  bool $transform  return prepped by config for output
     * @param  bool throw
     * @return mixed The value of the variable or void if variable doesn't exist
     * @throws EmptyParameterException
     * @todo the silent spec of itemid is a bit hacky
     */
    public static function getall($scope, $transform = NULL, $throw = FALSE)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');

        if ($transform == TRUE) {
            $args = array('themename'=> $scope, 'transform'=>1, 'throw'=>$throw);
            return xarThemeGetConfig( $args);
        }
        // Preload per module, once
        if (!isset(self::$_preloaded[$scope])) self::preload($scope);

        // if prep is not set, lets first check to see if any of our type vars are already set in the cache.
        $key = self::$__key . '.' . $scope;

        if (!isset(parent::$_iscached[$key])) {
            parent::$_cache[$key] = array();
            parent::$_iscached[$key] = array();
        }
        return parent::$_cache[$key];
    }

    /**
     * PreLoad all theme variables for a particular module
     *
     * @author Michel Dalle
     *
     * @param  string $scope Module name
     * @return boolean true on success
     * @throws EmptyParameterException
     * @todo  This has some duplication with xarVar.php
     */
    public static function preload($scope)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');

        $modBaseInfo = xarMod::getBaseInfo($scope, $type='theme');
        if (!isset($modBaseInfo)) return FALSE;

        if (!self::$__initDone) self::init();
        $query = 'SELECT xar_name, xar_value FROM '.self::$__themevarsTable.' WHERE xar_themename = ?';


        $bindvars = array($scope);
        $result = self::$__dbConn->Execute($query,$bindvars);

        if (!$result) return FALSE;
        $key = self::$__key . '.' . $scope;

        while (!$result->EOF) {
            list($name, $value) = $result->fields;
            parent::$_cache[$key][$name] = $value;
            parent::$_iscached[$key][$name] = TRUE;
            $result->MoveNext();
        }
        $result->close();

        self::$_preloaded[$scope] = TRUE;
        return TRUE;
    }

    /**
     * set a theme variable
     *
     * Note that this method is incompatible with 1.x even if wrapped.
     * the prime/description parameters were dropped from the signature.
     *
     *
     * @param themeName The name of the theme
     * @param name The name of the variable
     * @param value The value of the variable
     * @return boolean true on success
     * @throws EmptyParameterException
     *
     */
    public static function set($scope, $name, $prime = 0, $value, $description = '')
    {
        if (empty($scope)) throw new EmptyParameterException('modName');
        if (empty($name)) throw new EmptyParameterException('name');
        if ($value === NULL) throw new EmptyParameterException('value');

        $modBaseInfo = xarMod::getBaseInfo($scope, $type='theme');

        xarThemeVars::delete($scope, $name);

        if (!self::$__initDone) self::init();
        $seqId = self::$__dbConn->GenId(self::$__themevarsTable);
        $query = 'INSERT INTO ' . self::$__themevarsTable .
                       ' (xar_id, xar_themeName,
                          xar_name, xar_prime,
                          xar_value, xar_description)
                          VALUES (?,?,?,?,?,?)';
        $bindvars = array($seqId, $scope, $name, $prime, (string)$value, $description);

       // if (xarSystemVars::get(null,'DB.UseADODBCache')){
       //     $result = self::$__dbConn->CacheFlush();
       // }

        $result = self::$__dbConn->Execute($query,$bindvars);
        if (!$result) return FALSE;

        // Update cache for the variable
        $key = self::$__key . '.' . $scope;
        parent::$_cache[$key][$name] = $value;
        parent::$_iscached[$key][$name] = TRUE;
        return TRUE;
    }

    /**
     * delete a theme variable
     *
     *
     * @param themeName The name of the theme
     * @param name The name of the variable
     * @return boolean true on success
     * @throws EmptyParameterException
     */
    public static function delete($scope, $name)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');
        if (empty($name)) throw new EmptyParameterException('name');

        $modBaseInfo = xarMod::getBaseInfo($scope, $type='theme');
        if (!isset($modBaseInfo)) return FALSE; // throw back

        if (!self::$__initDone) self::init();
        $query = 'DELETE FROM ' . self::$__themevarsTable . ' WHERE xar_themeName = ?  AND xar_name = ?';
        $bindvars = array($scope, $name);
        $result = self::$__dbConn->Execute($query, $bindvars);
        if (!$result) return FALSE;

        // Removed it from the cache
        $key = self::$__key . '.' . $scope;
        if (isset(parent::$_iscached[$key][$name])) {
            unset(parent::$_cache[$key][$name], parent::$_iscached[$key][$name]);
        }
        return TRUE;
    }
    /**
     * Delete all module theme
     *
     * @param  string $scope The name of the module
     * @return boolean true on success
     * @throws EmptyParameterException, SQLException
     * @todo Add caching for item variables?
     */
    public static function delete_all($scope)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');

        $modBaseInfo = xarMod::getBaseInfo($scope, $type='theme');
        if (!isset($modBaseInfo)) return FALSE; // throw back

        if (!self::$__initDone) self::init();
        // We delete the theme vars

        // Now delete the module vars
        $query = 'DELETE FROM ' . self::$__themevarsTable . ' WHERE xar_themeName = ? ';
        $result = self::$__dbConn->Execute($query, array($scope));
        if (!$result) return FALSE;

        $key = self::$__key . '.' . $scope;
        if (isset(parent::$_iscached[$key])) {
            unset(parent::$_cache[$key], parent::$_iscached[$key]);
        }
        return TRUE;
    }

    /**
     * Get a signature of the current themevars scope
     */
    public static function signature($scope)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');

        $key = self::$__key . '.' . $scope;

        if (!isset(self::$_iscached[$key][$scope])) self::preload($scope);
        if (!isset(self::$_iscached[$key][$scope])) return NULL;

        return sha1(serialize(self::$_cache[$key][$scope].$scope));
    }
}

class xarSkinVars extends xarVars implements IxarSkinVars
{
    protected static $_keyGbl = 'Skin.Variables.Global';
    protected static $_keyLcl = 'Skin.Variables.Local';

    /**
     * get a theme skin variable
     *
     * @param  string $scope The name of the theme
     * @param  string $name  The name of the variable
     * @param  bool $transform  return prepped by config for output
     * @return mixed The value of the variable or void if variable doesn't exist
     */
    public static function get($scope, $name, $transform = NULL, $target = NULL)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');
        if (empty($name)) throw new EmptyParameterException('name');

        if ($target === NULL) {
            $key = self::$_keyGbl . '.' . $scope;
            if (!isset(self::$_iscached[$key][$name])) return NULL;
            return self::$_cache[$key][$name];
        } else {
            $key = self::$_keyLcl . '.' . $scope;
            if (!isset(self::$_iscached[$key][$target][$name])) return NULL;
            return self::$_cache[$key][$target][$name];
        }
    }

    /**
     * set a theme skin variable
     *
     * @param themeName The name of the theme
     * @param name The name of the variable
     * @param value The value of the variable
     * @return boolean true on success
     * @throws EmptyParameterException
     *
     */
    public static function set($scope, $name, $value, $target = NULL)
    {
        if (empty($scope)) throw new EmptyParameterException('modName');
        if (empty($name)) throw new EmptyParameterException('name');
        if ($value === NULL) return FALSE;

        if ($target === NULL) {
            $key = self::$_keyGbl . '.' . $scope;
            parent::$_cache[$key][$name] = $value;
            parent::$_iscached[$key][$name] = TRUE;
        } else {
            $key = self::$_keyLcl . '.' . $scope;
            parent::$_cache[$key][$target][$name] = $value;
            parent::$_iscached[$key][$target][$name] = TRUE;
        }
        return TRUE;
    }

    /**
     * set theme skin variables from an associative array
     *
     *
     * @param themeName The name of the theme
     * @param arr an associative array
     * @return boolean true on success
     * @throws EmptyParameterException
     *
     */
    public static function setarray($scope, $arr, $target = NULL)
    {
        if (empty($scope)) throw new EmptyParameterException('theme name');
        if (empty($arr)) throw new EmptyParameterException('array');

        if ($target === NULL) {
            $key = self::$_keyGbl . '.' . $scope;
            foreach ($arr as $name => $value) {
                parent::$_cache[$key][$name] = $value;
                parent::$_iscached[$key][$name] = TRUE;
            }
        } else {
            $key = self::$_keyLcl . '.' . $scope;
            foreach ($arr as $name => $value) {
                parent::$_cache[$key][$target][$name] = $value;
                parent::$_iscached[$key][$target][$name] = TRUE;
            }
        }
        return TRUE;
    }


    /**
     * delete a theme variable
     *
     *
     * @param themeName The name of the theme
     * @param name The name of the variable
     * @return boolean true on success
     * @throws EmptyParameterException
     */
    public static function delete($scope, $name, $target = NULL)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');
        if (empty($name)) throw new EmptyParameterException('name');

        if ($target === NULL) {
            $key = self::$_keyGbl . '.' . $scope;
            if (isset(parent::$_iscached[$key][$name])) {
                unset(parent::$_cache[$key][$name], parent::$_iscached[$key][$name]);
            }
        } else {
            $key = self::$_keyLcl . '.' . $scope;
            if (isset(parent::$_iscached[$key][$target][$name])) {
                unset(parent::$_cache[$key][$target][$name], parent::$_iscached[$key][$target][$name]);
            }
        }
        return TRUE;
    }

    /**
     * Delete all module theme
     *
     * @param  string $scope The name of the module
     * @return boolean true on success
     * @throws EmptyParameterException, SQLException
     * @todo Add caching for item variables?
     */
    static function delete_all($scope)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');
        $key = self::$_keyGbl . '.' . $scope;
        if (isset(parent::$_iscached[$key])) unset(parent::$_cache[$key], parent::$_iscached[$key]);

        $key = self::$_keyLcl . '.' . $scope;
        if (isset(parent::$_iscached[$key])) unset(parent::$_cache[$key], parent::$_iscached[$key]);

        return TRUE;
    }

    /**
     * Get a signature of the current themevars scope
     */
    public static function signature($scope, $target = NULL)
    {
        if (empty($scope)) throw new EmptyParameterException('themeName');

        $key = $target === NULL ? self::$_keyGbl . '.' . $scope : self::$_keyLcl . '.' . $scope;

        if (isset(parent::$_iscached[$key])) {
            if ($target === NULL) {
                $arr = &parent::$_cache[$key];
            } else {
                if ($target !== '*' && isset(parent::$_iscached[$key][$target])) {
                    $arr = &parent::$_cache[$key][$target];
                } else {
                    $arr = &parent::$_cache[$key];
                }
            }
        } else {
            $arr = array();
        }

        $ser = serialize($arr).$scope;
        if ($target !== NULL) $ser .= $target;
        return sha1($ser);
    }
}

/**
 * Base class for all skin var objects
 */
class xarSkinVar
{
    public $target = NULL;

    public function __construct($args=array())
    {
        if (isset($args['target'])) $this->target = $args['target'];
    }


    /**
     * Cloning classes with objects and arrays
     * @author: cheetah at tanabi dot org
     * @link http://www.php.net/manual/fr/language.oop5.cloning.php#87066
     * @todo <lakys> do we want this to be in Object base class?
     */
    public function __clone()
    {
        foreach ($this as $key => $val) {
            if (is_object($val) || (is_array($val))) {
                $this->{$key} = unserialize(serialize($val));
            }
        }
    }

    public function render()
    {
        return '';
    }
}

/**
 * Color processing for skin vars
 */
class xarSkinColor extends xarSkinVar
{
    public $r = 0.0;
    public $g = 0.0;
    public $b = 0.0;

    public $a = 1.0;

    public $h = 0.0;
    public $s = 0.0;
    public $l = 0.0;
    public $v = 0.0;

    public $mode = NULL;

    public $rgbafunc = TRUE;

    const MODE_RGB = 0;
    const MODE_RGBA = 1;
    const MODE_HSL = 2;
    const MODE_HSLA = 3;
    const MODE_HSV = 4;
    const MODE_HSVA= 5;
    const MODE_HSI = 6;
    const MODE_HSIA = 7;

    /**
     * @param args['type'] type of color (rgb, rgba, hsl, hsla, hsv, hsva)
     *
     */
    public function __construct($args=array())
    {
        parent::__construct($args);
        extract($args);
        if (isset($r) && is_numeric($r)) $this->r = $r;
        if (isset($g) && is_numeric($g)) $this ->g = $g;
        if (isset($b) && is_numeric($b)) $this->b = $b;
        if (isset($h)){
            if (is_numeric($h)) {
                $this->h = $h;
            } else {
                $this->h = NULL;
            }
        }
        if (isset($s) && is_numeric($s)) $this->s = $s;
        if (isset($l) && is_numeric($l)) $this->l;
        if (isset($a) && is_numeric($a)) $this->a = $a;
        if (isset($value)) $hex = $value;
        if (isset($hex)) {
            $hex = ltrim($hex, '#');
            switch (strlen($hex)) {
                case 6:
                    $this->r = hexdec(substr($hex, 0, 2));
                    $this->g = hexdec(substr($hex, 2, 2));
                    $this->b = hexdec(substr($hex, -2));
                    break;
                case 3:
                    $this->r = hexdec($hex{0}.$hex{0});
                    $this->g = hexdec($hex{1}.$hex{1});
                    $this->b = hexdec($hex{2}.$hex{2});
                    break;
            }
            $this->mode = isset($a) ? self::MODE_RGBA : self::MODE_RGB;
        }
        if (max($this->r, $this->g, $this->b) > 1) {
            $this->r /= 255; $this->g /= 255; $this->b /= 255;
        }

        if (isset($args['mode'])) {
            $this->setmode(array('newmode' => $args['mode']));
        } else {
            $this->mode = isset($a) ? self::MODE_RGBA : self::MODE_RGB;
            $newmode = isset($a) ? self::MODE_HSLA : self::MODE_HSL;
            $this->_convert($newmode);
            $this->mode = $newmode;
        }
    }

    public function setmode($args =array())
    {
        if (!isset($arg['newmode'])) $modename = $args['newmode'];
        switch (strtolower($modename)) {
            case 'rgb':
                $newmode = self::MODE_RGB;
                break;
            case 'rgba':
                $newmode = self::MODE_RGBA;
                break;
            case 'hsl':
                $newmode = self::MODE_HSL;
                break;
            case 'hsla':
                $newmode = self::MODE_HSLA;
                break;
            case 'hsv':
                $newmode = self::MODE_HSV;
                break;
            case 'hsva':
                $newmode = self::MODE_HSVA;
                break;
        }
        if ($this->mode !== NULL && $newmode !== $this->mode) {
            $this->_convert($newmode);
        }
        $this->mode = $newmode;
    }

    public function norgbafunc($args=array())
    {
        $this->rgbafunc = FALSE;
    }

    protected function _convert($newmode)
    {
        // http://en.wikipedia.org/wiki/HSL_and_HSV
        switch ($this->mode) {
            case self::MODE_RGB:
            case self::MODE_RGBA:
                if ($newmode === self::MODE_HSL || $newmode === self::MODE_HSLA) {
                    $M = max($this->r, $this->g, $this->b);
                    $m = min($this->r, $this->g, $this->b);
                    $C = $M - $m;
                    $this->l = 0.5 * ($M + $m); //This was true HSL
                    //$this->l = 0.21 * $this->r + 0.72 * $this->g + 0.07 * $this->b; // This is sRGB luma
                    if ($C == 0.0) {
                        $this->h = NULL;
                    }  elseif ($M == $this->b) {
                        $this->h = ($this->r - $this->g) / $C + 4;
                    }  elseif ($M == $this->r) {
                        $this->h = ($this->g - $this->b) / $C;
                        $this->h = $this->h > 6 ? ($this->h - 6) : $this->h;
                        $this->h = $this->h < 0 ? ($this->h + 6) : $this->h;

                    } elseif ($M == $this->g) {
                        $this->h = ($this->b - $this->r) / $C + 2;
                    }
                    if ($C == 0) {
                        $this->s = 0;
                    } else {
                        $this->s = $C / (1 - abs(2 * $this->l -1));
                    }
                    if ($this->h !== NULL)$this->h *= 60;
                }
                break;

            case self::MODE_HSL:
            case self::MODE_HSLA:
                if ($newmode === self::MODE_RGB || $newmode === self::MODE_RGBA) {
                    if ($this->h !== NULL) {
                        $H = $this->h / 60;
                    } else {
                        $H = NULL;
                    }

                    $C = (1-abs(2 * $this->l - 1)) * $this->s;
                    $r = 0; $g = 0; $b = 0;
                    if ($H === NULL) {
                        $r = 0; $g = 0; $b = 0;
                    } else if ($H < 1) {
                        $r = $C; $g = $C * $H; $b = 0;
                    } else if ($H < 2) {
                        $r = $C * (2 - $H); $g = $C; $b = 0;
                    } else if ($H < 3) {
                        $r = 0; $g = $C; $b = $C * ($H - 2);
                    } else if ($H < 4) {
                        $r = 0; $g = $C * (4 - $H); $b = $C;
                    } else if ($H < 5) {
                        $r = $C * ($H - 4); $g = 0; $b = $C;
                    } else {
                        $r = $C; $g = 0; $b = $C * (6 - $H);
                    }
                    // This HSL calcultation
                    $m = $this->l - 0.5 * $C;

                    // Let's use sRGB Luma instead as this is closer to the eye perceptual vision
                    //$m = $this->l - 0.21 * $this->r - 0.72 * $this->g - 0.07 * $this->b;
                    $this->r = $r + $m;
                    $this->g = $g + $m;
                    $this->b = $b + $m;
                }
                break;

            case self::MODE_HSV:
            case self::MODE_HSVA:
                // not implemented
                break;

        }
    }
    /**
     * Lighten a given color
     * @param $args['lighten']
     */
    public function lighten($args=array())
    {
        $actions = isset($args['lighten']) ? array('l' => array('add' => $args['lighten'])) : array('l' => array('add' => 0.05));
        $this->change($actions);
    }

    public function darken($args=array())
    {
        $actions = isset($args['darken']) ? array('l' => array('add' => -$args['darken'])) : array('l' => array('add' => -0.05));
        $this->change($actions);
    }

    public function lightenmore($args=array())
    {
        $actions = array('l' => array('add' => 0.25));
        $this->change($actions);
    }

    public function darkenmore($args=array())
    {
        $actions = array('l' => array('add' => -0.25));
        $this->change($actions);
    }

    public function luma($args=array())
    {
        if (!isset($args['luma'])) return;
        $luma = $args['luma'];
        if ($luma == 0) {
            $this->l = 0; $this->s = 0; $this->h = NULL;
            $this->r = 0; $this->g = 0; $this->b = 0;
            return;
        }
        $oldmode = $this->mode;
        $this->_convert(self::MODE_RGB);
        $this->mode = self::MODE_RGB;

        // @link http://www.fourcc.org/fccyvrgb.php
        $Ey = 0.299 * $this->r + 0.587 * $this->g + 0.114 * $this->b;
        $Ecr = 0.713 * ($this->r - $Ey);
        $Ecb = 0.565 * ($this->b - $Ey);

        $this->r = $luma + 1.402 * $Ecr;
        $this->g = $luma - 0.34414 * $Ecb - 0.71414 * $Ecr;
        $this->b = $luma + 1.772 * $Ecb;

        if ($this->r > 1) $this->r = 1.0;
        if ($this->r < 0) $this->r = 0.0;
        if ($this->g > 1) $this->g = 1.0;
        if ($this->g < 0) $this->g = 0.0;
        if ($this->b > 1) $this->b = 1.0;
        if ($this->b < 0) $this->b = 0.0;
        $this->_convert($oldmode);
        $this->mode = $oldmode;
        $this->_convert(self::MODE_HSL);
    }


    /**
     * Shift hue
     * @param arg['shift'] the offset in degree for the hue
     */
    public function shift($args=array())
    {
        $actions = isset($args['shift']) ? array('h' => array('add' => $args['shift'])) : array('h' => array('add' => 20));
        $this->change($actions);
    }

    public function shiftleft($args=array())
    {
        $actions = array('h' => array('add' => -20));
        $this->change($actions);
    }

    public function shiftright($args=array())
    {
        $actions = array('h' => array('add' => 20));
        $this->change($actions);
    }

    public function shiftleftmore($args=array())
    {
        $actions = array('h' => array('add' => -60));
        $this->change($actions);
    }

    public function shiftrightmore($args=array())
    {
        $actions = array('h' => array('add' => 60));
        $this->change($actions);
    }

    /**
     * Saturate a color
     */
    public function saturate($args=array())
    {
        $actions = array('s' => array('set' => 1.0));
        $this->change($actions);
    }

    public function neutral($args=array())
    {
        if (isset($args['neutral'])) {
            $actions = array('s' => array('multiply' => $args['neutral']));
            $this->change($actions);
        }
    }

    public function sat($args=array())
    {
        $actions = array('s' => array('add' => 0.2));
        $this->change($actions);
    }

    public function satmore($args=array())
    {
        $actions = array('s' => array('add' => 0.6));
        $this->change($actions);
    }

    public function desat($args=array())
    {
        $actions = array('s' => array('add' => -0.2));
        $this->change($actions);
    }

    public function desatmore($args=array())
    {
        $actions = array('s' => array('add' => -0.6));
        $this->change($actions);
    }

    /**
     * Make the brightest saturated color
     */
    public function saturatebright($args=array())
    {
        $actions = array('s' => array('set' => 1.0), 'l' => 0.5);
        $this->change($actions);
    }

    public function burn($args=array())
    {
        $actions = isset($args['burn']) && !empty($args['burn']) ?
                    array('s' => array('multiply' => 1.0+$args['burn'], 'l' => array('multiply' => 1.0/(1.0+$args['burn'])))) :
                    array('s' => array('multiply' => 1.25), 'l' => array('multiply' => 0.8));
        $this->change($actions);
    }

    public function dodge($args=array())
    {
        $actions = isset($args['dodge']) && !empty($args['dodge']) ?
                    array('s' => array('multiply' => 1.0/(1.0+$args['dodge'])), 'l' => array('multiply' => 1.0+$args['dodge'])) :
                    array('s' => array('multiply' => 0.8), 'l' => array('multiply' => 1.25));
        $this->change($actions);
    }

    public function burnmore($args=array())
    {
        $actions = array('s' => array('multiply' => 2.0), 'l' => array('multiply' => 0.5));
        $this->change($actions);
    }

    public function dodgemore($args=array())
    {
        $actions = array('s' => array('multiply' => 2.0), 'l' => array('multiply' => 2.0));
        $this->change($actions);
    }


    protected static $_allowedProperties = array('r','g','b','h','s','l', 'a');
    protected static $_rgbProperties = array('r', 'g', 'b');
    protected static $_allowedActions = array('add', 'multiply', 'set', 'zero');

    public function change($args = array())
    {
        $properties = array();
        $rgb = FALSE;
        $alpha = FALSE;
        foreach($args as $key => $value) {
            if (in_array($key, self::$_allowedProperties)) {
                $properties[$key] = $value;
                if (in_array($key, self::$_rgbProperties)) $rgb = TRUE;
                if ($key === 'a') $alpha = TRUE;
            }
        }

        if ($alpha) {
            if ($this->mode === self::MODE_RGB) $this->mode = self::MODE_RGBA;
            if ($this->mode === self::MODE_HSL) $this->mode = self::MODE_HSLA;
            if ($this->a === NULL) $this->a = 1.0;
        }
        $oldmode = $this->mode;
        $needtochange = !$rgb && ($this->mode === self::MODE_RGB || $this->mode === self::MODE_RGBA) || $rgb && ($this->mode === self::MODE_HSL || $this->mode === self::MODE_HSLA);

        // Prepare to convert
        if ($needtochange) {
            $newmode =$rgb ? self::MODE_RGBA : self::MODE_HSLA;
            $this->_convert($newmode);
            $this->mode = $newmode;
        }

        // Let's start apply the operations
        foreach($properties as $varname => $actions) {
            if (!isset($this->$varname)) continue;
            if (is_array($actions)) {
                foreach($actions as $type => $value) {
                    if (!is_numeric($value)) continue;
                    if ($type !== 'set' && $this->$varname === NULL) continue; // We don't change a NULL value with anything but set. This is important to prevent a null hue to be changed.
                    switch ($type) {
                        case 'add':
                            $this->$varname +=$value;
                            break;
                        case 'multiply':
                            $this->$varname *= $value;
                            break;
                        case 'set':
                            $this->$varname = $value;
                            break;
                    }
                }
            } else if (is_numeric($actions)) {
                $this->$varname = $actions;
            }
            // Normalisation
            switch ($varname) {
                case 'r': case'g': case 'b':
                case 'a': case 's': case 'l':
                    // Normalisation between 0 and 1
                    if ($this->$varname > 1) {
                        $this->$varname = 1.0;
                    } elseif ($this->$varname <0) {
                        $this->$varname = 0.0;
                    }
                    break;
                case 'h':
                    // Quick normalisation betwen 0 and 360
                    // Would be better to use a modulo function here, but would not be as fast
                    if ($this->h === NULL) break;
                    if ($this->h > 360) $this->h -= 360;
                    if ($this->h < 0) $this->h += 360;
                    break;
                default:
                    break;
            }
        }
        // Convert back
        if ($needtochange) {
            $this->_convert($oldmode);
            $this->mode = $oldmode;
        }
    }

    public function render()
    {
        switch ($this->mode) {
            case self::MODE_RGB:
            case self::MODE_RGBA:
                break;

            case self::MODE_HSL:
            case self::MODE_HSV:
                $this->_convert(self::MODE_RGB);
                break;

            case self::MODE_HSLA:
            case self::MODE_HSVA:
                $this->_convert(self::MODE_RGBA);
                break;
        }

        if ($this->mode === self::MODE_RGBA || $this->mode === self::MODE_HSLA || $this->mode === self::MODE_HSVA) {
            if ($this->rgbafunc) {
                return sprintf('rgba(%d, %d, %d, %.2f)', $this->r*255, $this->g*255, $this->b*255, $this->a);
            } else {
                return '#'.sprintf('%02X%02X%02X%02X', $this->a*255, $this->r*255, $this->g*255, $this->b*255);
            }
        }

        return '#'.sprintf('%02X%02X%02X', $this->r*255, $this->g*255, $this->b*255);
    }
}

/**
 * Css size processing
 */
class xarSkinSize extends xarSkinVar
{
    public $unit = 'px';
    public $value = 0;
    public $mode = NULL;

    public function __construct($args=array())
    {
        parent::__construct($args);
        extract($args);
        if (isset($unit) && is_string($unit)) $this->unit = $unit;
        if (isset($value) && is_numeric($value)) $this ->value = $value;
    }

    public function setmode($args =array())
    {

    }

    protected function _convert($newmode)
    {

    }

    public function add($args)
    {
        if (!isset($args['add'])) throw new BadParameterException('add');
        $actions = array('value' => array('add' => $args['add']));
        $this->change($actions);
    }

    public function sub($args)
    {
        if (!isset($args['sub'])) throw new BadParameterException('sub');
        $actions = array('value' => array('sub' => $args['sub']));
        $this->change($actions);
    }

    public function mul($args)
    {
        if (!isset($args['mul']) ) throw new BadParameterException('mul');
        $actions = array('value' => array('mul' => $args['mul']));
        $this->change($actions);
    }

    protected static $_allowedActions = array('add', 'mul', 'set', 'zero');
    protected static $_allowedProperties = array('value');

    public function change($args = array())
    {
        $properties = array();
        $rgb = FALSE;
        foreach($args as $key => $value) {
            if (in_array($key, self::$_allowedProperties)) {
                $properties[$key] = $value;
            }
        }

        // Let's start apply the operations
        foreach($properties as $varname => $actions) {
            if (!isset($this->$varname)) continue;
            if (is_array($actions)) {
                foreach($actions as $type => $value) {
                    if (!is_numeric($value)) {
                        if (is_string($value)) {
                            // interact possibly with other skinvars
                            $name = $value;
                            $skinvar = xarSkinVars::get(xarTpl::getThemeName(), $name, NULL, $this->target);
                            if ($skinvar === NULL) continue;
                            if (is_object($skinvar) && $skinvar instanceof xarSkinSize) {
                                $value = $skinvar->value;
                            } else {
                                $value = $skinvar;
                            }
                        } else {
                            continue;
                        }
                    }
                    switch ($type) {
                        case 'add':
                            $this->$varname +=$value;
                            break;
                        case 'sub':
                            $this->$varname -=$value;
                            break;
                        case 'mul':
                            $this->$varname *= $value;
                            break;
                        case 'set':
                            $this->$varname = $value;
                            break;
                    }
                }
            } else if (is_numeric($actions)) {
                $this->$varname = $actions;
            }
        }
    }

    public function render()
    {
        return $this->value == 0 ? $this->value : $this->value . $this->unit;
    }
}

class xarSkinGenerator extends xarSkinVar
{
    protected $_content;
    // @TODO implement something better using xarProcessCss->generate())
    public function __construct($args=array())
    {
        /* we don't need to call parent constructor */
        if (!isset($args['target'])) $args['target'] = NULL;
        if (!isset($args['source'])) $args['source'] = NULL;
        $file ='';
        if (!isset($args['altfile'])) $args['altfile'] = '';

        if (isset($args['file']) && !empty($args['file'])) {
            if (!class_exists('xarProcessCss')) sys::import('modules.themes.xarclass.xarcss');
            $skinvars = $args;
            $this->target = $args['target'];
            unset($skinvars['file'],$skinvars['altfile'],$skinvars['name'],$skinvars['target'],$skinvars['source']);
            if (!empty($skinvars)) xarSkinVars::setarray(xarTpl::getThemeName(), $skinvars, $args['source']);
            $this->_content = xarProcessCss::getInstance()->generate($args['file'], $args['altfile'], $args['source']);
        } else {
            $this->_content = ''; // That's a null generator
        }
    }

    public function render()
    {
        return $this->_content;
    }
}

class xarSkinFactory extends xarObject
{
    public static function call($args = array())
    {
        $themename = xarTpl::getThemeName();
        if (empty($args)) throw new EmptyParameterException('no args');

        if (isset($args['handler_type'])) unset($args['handler_type']);

        // Methods to call
        if (isset($args['method'])) {
            $method = $args['method'];
            unset($args['method']);
            if (strpos($method, ',') !== FALSE) {
                $methods = explode(',', str_replace(' ', '', $method));
            } else {
                $methods = array(trim($method));
            }
        } else {
            $methods = array('none');
        }

        // Type is a part of the class name
        $class = "xarSkinVar";
        if ($methods[0] !== 'get'&& !isset($args['type'])) throw new EmptyParameterException('type');
        if (isset($args['type'])) {
            $type = $args['type'];
            unset($args['type']);
            $class = 'xarSkin'.ucfirst($type);
            if (!class_exists($class) || $class === 'xarSkinVars' || $class === 'xarSkinFactory') throw new BadParameterException('type');
        }

        // Names to use
        if (!isset($args['name'])) throw new EmptyParameterException('name');
        $name = $args['name'];

        unset($args['name']);
        if (strpos($name, ',') !== FALSE) {
            $name = explode(',', str_replace(' ', '', $name));
        } else {
            $name = array(trim($name));
        }
        reset($name);
        $args['name'] = current($name);

        // Test against a list of names with separators (possibly provided by a checkboxlist themevar)
        if (isset($args['condition'])) {
            $condition = $args['condition'];
            unset($args['condition']);

            // Condition can be sent as several form
            if (is_bool($condition)) {
                $altaction = $condition === FALSE;
            } else if (is_numeric($condition)) {
                $altaction = $condition == 0;
            } else if (is_string($condition)) {
                $haswidlcard = strpos($condition, '*');
                if (strpos($condition, ',') !== FALSE) {
                    $condition = explode(',', $condition);
                } else {
                    $condition = array($condition);
                }

                $altaction = TRUE;

                // a test using in_array was enough, but we detect now true/false value, plus wildcard
                // note that the first relevant value found is used. So it is order dependant.
                foreach ($condition as $c) {
                    $cond = trim($c);
                    // in case there is a true string
                    if ($cond == 'true') {
                        $altaction = FALSE;
                        break;
                    }
                    if ($cond == 'false') {
                        $altaction = TRUE;
                        break;
                    }
                    // is the name there?
                    if ($cond == $args['name']) {
                        $altaction = FALSE;
                        break;
                    } elseif ($haswidlcard) {
                        $regex = '%^'. str_replace('*', '.*', $cond) . '$%';
                        if (preg_match($regex, $args['name'])) {
                            $altaction = FALSE;
                            break;
                        }
                    }

                }
            }

            if ($altaction) {
                // do something to invalidate a generator
                switch ($type) {
                    case 'generator':
                        if (isset($args['altfile'])) {
                            // Use altfile if provided
                            $args['file'] = $args['altfile'];
                            unset($args['altfile']);
                        } else if (isset($args['file'])) {
                            unset($args['file']);
                        }
                        // This will call a null generator
                        break;
                    default:
                        break;
                }
            }
        }

        // Target
        if (!isset($args['target'])) $args['target'] = NULL;
        // Source
        if (!isset($args['source'])) {
            if ($type === 'generator') {
                $args['source'] = 'genvars'; // Special source namespace
            } else {
                $args['source'] = $args['target']; // Fall back to the target
            }
        }

        if ($methods[0] !== 'get') {
            $skinvar = new $class($args);
            if (!is_object($skinvar)) throw new BadParameterException(NULL, 'no instance');
            xarSkinVars::set($themename, $args['name'], $skinvar, $args['target']);
            $args['name'] = next($name) !== FALSE ? current($name) : end($name);
        }

        foreach($methods as $key => $method) {
            $method = trim($method);

            if ($method === 'get') {
                if ($args['source'] == 'themevars') {
                    $value = xarThemeVars::get($themename, $args['name'], NULL, FALSE);
                } else {
                    $value = xarSkinVars::get($themename, $args['name'], NULL, $args['source']);
                }
                if (is_object($value) && $value instanceof xarSkinVar) {
                    $skinvar = $value;
                    $class = get_class($skinvar);
                } else {
                    $args['value'] = $value;
                    $skinvar = new $class($args);
                    // The class is not affected to any skinvar! Must use set then
                    if (!isset($methods[$key+1])) throw new BadParameterException($args['name'], 'Is not an object and will not be saved - Please use set method after get with a new name', '');
                }
                $args['name'] = next($name) !== FALSE ? current($name) : end($name);

            } elseif ($method === 'set') {
                // Be careful that if the skin vars is already existing, it passes the same handlder pointing the same class instance.
                // Should be used only after a get from a skinvar providing a string value and not being an object
                $skinvar->target = $args['target'];
                xarSkinVars::set($themename, $args['name'], $skinvar, $args['target']);
                $args['name'] = next($name) !== FALSE ? current($name) : end($name);

            } elseif ($method === 'clone') {
                $newskinvar = clone $skinvar;
                $newskinvar->target = $args['target'];
                $skinvar = $newskinvar;
                xarSkinVars::set($themename, $args['name'], $skinvar, $args['target']);
                $args['name'] = next($name) !== FALSE ? current($name) : end($name);

            } elseif ($method === 'none') {
                // do nothing
            }
             else if (method_exists($skinvar, $method)) {
                $skinvar->$method($args);
            } else {
                throw new FunctionNotFoundException($class . ' ' . $method);
            }
        }
    }
}

?>
