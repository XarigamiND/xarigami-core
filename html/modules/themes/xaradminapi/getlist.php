<?php
/**
 * Gets a list of themes that matches required criteria.
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * Gets a list of themes that matches required criteria.
 *
 * Supported criteria are UserCapable, AdminCapable, Class, Category, State.
 *
 * Permitted values for UserCapable are 0 or 1 or unset. If you specify the 1
 * value the result will contain all the installed themes that support the
 * user GUI.
 * Obviously you get the opposite result if you specify a 0 value for
 * UserCapable in filter.
 * If you don't care of UserCapable property, simply don't specify a value for
 * it.
 * The same thing is applied to the AdminCapable property.
 * Permitted values for Class and Category are the ones defined in the proper
 * RFC.
 * Class 0:- System theme, 1: utility theme, 2: user theme
 * Permitted values for State are XARTHEME_STATE_ANY, XARTHEME_STATE_UNINITIALISED,
 * XARTHEME_STATE_INACTIVE, XARTHEME_STATE_ACTIVE, XARTHEME_STATE_MISSING,
 * XARTHEME_STATE_UPGRADED.
 * The XARTHEME_STATE_ANY means that any state is valid.
 * The default value of State is XARTHEME_STATE_ACTIVE.
 * For other criteria there's no default value.
 * The orderBy parameter specifies the order by which is sorted the result
 * array, can be one of name, regid, class, category or a combination of them,
 * the default is name.
 * You can combine those fields to obtain a good ordered list simply by
 * separating them with the '/' character, i.e. if you want to order the list
 * first by class, then by category and lastly by name you pass
 * 'class/category/name' as orderBy parameter
 *
 * @author Marco Canini <marco.canini@postnuke.com>
 * @param filter array of criteria used to filter the entire list of installed
 *        themes.
 * @param startNum the start offset in the list
 * @param numItems the length of the list
 * @param orderBy the order type of the list
 * @return array of theme information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function themes_adminapi_getlist($filter = array(), $startNum = NULL, $numItems = NULL, $orderBy = 'name')
{
    static $validOrderFields = array('name' => 'themes', 'regid' => 'themes',
                                     'class' => 'infos');
    if (!is_array($filter)) {
        throw new BadParameterException('filter','Parameter filter must be an array.');
    }
    // Optional arguments.
    if (!isset($startNum)) {
        $startNum = 1;
    }
    if (!isset($numItems)) {
        $numItems = -1;
    }

    $orderFields = explode('/', $orderBy);
    $orderByClauses = array(); $extraSelectClause = '';
    foreach ($orderFields as $orderField) {
        if (!isset($validOrderFields[$orderField])) {
            throw new BadParameterException('orderBy','Parameter orderBy can contain only \'name\' or \'regid\' or \'class\' as items.');
        }
        // Here $validOrderFields[$orderField] is the table alias
        $orderByClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'infos') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.xar_' . $orderField;
        }
    }
    $orderByClause = join(', ', $orderByClauses);

    // Determine the right tables to use
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    $prefix = xarDB::$prefix;
    $themestable = $prefix .'_themes';
    // Construct an array with where conditions and their bind variables
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
            $whereClauses[] = 'xar_state = ?';
            $bindvars[] = $filter['State'];
        }
    } else {
        $whereClauses[] = 'xar_state = ?';
        $bindvars[] = XARTHEME_STATE_ACTIVE;
    }

    $mode = XARTHEME_MODE_SHARED;
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
              FROM $tables[themes] AS themes";
    array_unshift($whereClauses, 'themes.xar_mode = ?');
    array_unshift($bindvars,$mode);

    $whereClause = join(' AND ', $whereClauses);
    $query .= " WHERE $whereClause ORDER BY $orderByClause";
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

            $themeInfo['mode'] = (int) $mode;
            // Shortcut for os prepared directory
            $themeInfo['osdirectory'] = xarVarPrepForOS($themeInfo['directory']);
            $themeInfo['state'] = (int) $themeState;
            //get all the file theme info first, and once
            if (function_exists('xarTheme_getFileInfo')) {
                $themeFileInfo = xarTheme_getFileInfo($themeInfo['osdirectory']);
                xarCoreCache::setCached('Theme.BaseInfos', $themeInfo['name'], $themeInfo);
            }
            if (!isset($themeFileInfo)) {
                // There was an entry in the database which was not in the file system,
                // remove the entry from the database
                xarModAPIFunc('themes','admin','remove',array('regid' => $themeInfo['regid']));
            } else {
                $themeInfo = array_merge($themeInfo, $themeFileInfo);
                xarCoreCache::setCached('Theme.Infos', $themeInfo['regid'], $themeInfo);

                switch ($themeInfo['state']) {
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
                        $themeInfo['state'] = XARTHEMESTATE_UPGRADED;
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
