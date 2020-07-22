<?php
/**
 * Gets a list of themes
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Gets a list of themes that matches required criteria.
 * Supported criteria are UserCapable, AdminCapable, Class, Category, State.
 * Class 0:- System theme, 1: utility theme, 2: user theme, int or array
 * @param filter array of criteria used to filter the entire list of installed themes.
 * @param startNum the start offset in the list
 * @param numItems the length of the list
 * @param orderBy the order type of the list
 * @return array array of theme information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function themes_adminapi_getthemelist($args)
{
    extract($args);

    static $validOrderFields = array('name' => 'themes', 'regid' => 'themes', 'class' => 'infos');

    if (!isset($filter)) $filter = array();
    if (!is_array($filter)) {
        throw new BadParameterException('filter','The parameter #(1) must be an array.');
    }

    // Optional arguments.
    if (!isset($startNum)) $startNum = 1;
    if (!isset($numItems)) $numItems = -1;
    if (!isset($orderBy)) $orderBy = 'name';

    // Determine the tables we are going to use
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    $themestable = $tables['themes'];

    // Construct order by clause
    $orderFields = explode('/', $orderBy);
    $orderByClauses = array(); $extraSelectClause = '';
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            throw new BadParameterException('orderBy','The parameter #(1) can contain only \'name\' or \'regid\' or \'class\' as items.');
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'infos') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.xar_' . $orderField;
        }
    }
    $orderByClause = join(', ', $orderByClauses);


    // Construct arrays for the where conditions and their bind variables
    $whereClauses = array(); $bindvars = array();
    if (isset($filter['Mode'])) {
        $whereClauses[] = 'xar_mode = ?';
        $bindvars[] = $filter['Mode'];
    }

    if (isset($filter['Class'])) {
        if (is_array($filter['Class'])) {
            $filter['Class'] = implode(',',$filter['Class']);
            $whereClauses[] = 'xar_class IN('.$filter['Class'].')';
        } else {
            $whereClauses[] = 'xar_class = ?';
             $bindvars[] = $filter['Class'];
        }
    }

    if (isset($filter['State'])) {
        if ($filter['State'] != XARTHEME_STATE_ANY) {
            if ($filter['State'] != XARTHEME_STATE_INSTALLED) {
                $whereClauses[] = 'xar_state = ?';
                $bindvars[] = $filter['State'];
            } else {
                $whereClauses[] = 'xar_state != ? AND xar_state < ? AND xar_state != ?';
                $bindvars[] = XARTHEME_STATE_UNINITIALISED;
                $bindvars[] = XARTHEME_STATE_MISSING_FROM_INACTIVE;
                $bindvars[] = XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
            }
        }
    } else {
        $whereClauses[] = 'xar_state = ?';
        $bindvars[] = XARTHEME_STATE_ACTIVE;
    }


    $themeList = array();


    $query = "SELECT xar_regid,
                     xar_name,
                     xar_directory,
                     xar_mode,
                     xar_author,
                     xar_homepage,
                     xar_email,
                     xar_description,
                     xar_contactinfo,
                     xar_publishdate,
                     xar_license,
                     xar_version,
                     xar_xaraya_version,
                     xar_bl_version,
                     xar_class,
                     xar_state
              FROM $themestable AS themes";

    $whereClause = join(' AND ', $whereClauses);
    if($whereClause != ''){
         $query .= " WHERE $whereClause";
    }
    $query .= " ORDER BY $orderByClause";
    $result = $dbconn->SelectLimit($query, $numItems, $startNum - 1,$bindvars);
    if (!$result) return;

    while(!$result->EOF) {
        list ($themeInfo['regid'],
             $themeInfo['name'],
             $themeInfo['directory'],
             $themeInfo['mode'],
             $themeInfo['author'],
             $themeInfo['homepage'],
             $themeInfo['email'],
             $themeInfo['description'],
             $themeInfo['contactinfo'],
             $themeInfo['publishdate'],
             $themeInfo['license'],
             $themeInfo['version'],
             $themeInfo['xaraya_version'],
             $themeInfo['bl_version'],
             $themeInfo['class'],
             $themeState) = $result->fields;

        if (xarCoreCache::isCached('Theme.Infos', $themeInfo['regid'])) {
            // Get infos from cache
            $themeList[] = xarCoreCache::getCached('Theme.Infos', $themeInfo['regid']);
        } else {
           // $themeInfo['mode'] = (int) $mode;
            $themeInfo['displayname'] = xarThemeGetDisplayableName($themeInfo['name']);
            // Shortcut for os prepared directory
            $themeInfo['osdirectory'] = xarVarPrepForOS($themeInfo['directory']);

            $themeInfo['state'] = (int) $themeState;

            xarCoreCache::setCached('Theme.BaseInfos', $themeInfo['name'], $themeInfo);

            $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
            if (isset($themeFileInfo)) {
                $themeInfo = array_merge($themeFileInfo, $themeInfo);
                xarCoreCache::setCached('Theme.Infos', $themeInfo['regid'], $themeInfo);
                switch ($themeInfo['state']) {
                /* jojo - take out the patch, add alternate for this
                    case XARTHEME_STATE_UNINITIALISED:
                        $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
                        break;
                    case XARTHEME_STATE_INACTIVE:
                        $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_INACTIVE;
                        break;
                    case XARTHEME_STATE_ACTIVE:
                        $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_ACTIVE;
                        break;
                    case XARTHEME_STATE_UPGRADED:
                        $themeInfo['state'] = XARTHEME_STATE_MISSING_FROM_UPGRADED;
                        break;
                */
                    case XARTHEME_STATE_MISSING_FROM_UNINITIALISED:
                        $themeInfo['state'] = XARTHEME_STATE_UNINITIALISED;
                        break;
                    case XARTHEME_STATE_MISSING_FROM_INACTIVE:
                        $themeInfo['state'] = XARTHEME_STATE_INACTIVE;
                        break;
                    case XARTHEME_STATE_MISSING_FROM_ACTIVE:
                        $themeInfo['state'] = XARTHEME_STATE_ACTIVE;
                        break;
                    case XARTHEME_STATE_MISSING_FROM_UPGRADED:
                        $themeInfo['state'] = XARTHEME_STATE_UPGRADED;
                        break;

                }
            }
            $themeList[] = $themeInfo;
        }
        $themeInfo = array();
        $result->MoveNext();
    }
    $result->Close();
    return $themeList;
}

?>