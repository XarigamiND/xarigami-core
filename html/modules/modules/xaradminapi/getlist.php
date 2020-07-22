<?php
/**
 * Get a list of modules that matches required criteria.
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Modules module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Get a list of modules that matches required criteria.
 *
 * Supported criteria are UserCapable, AdminCapable, Class, Category,
 * State, modulelist (comma separated list of regid)
 * Permitted values for UserCapable are 0 or 1 or unset. If you specify the 1
 * value the result will contain all the installed modules that support the
 * user GUI.
 * Obviously you get the opposite result if you specify a 0 value for
 * UserCapable in filter.
 * If you don't care of UserCapable property, simply don't specify a value for
 * it.
 * The same thing is applied to the AdminCapable property.
 * Permitted values for Class and Category are the ones defined in RFC 13.
 *
 * Permitted values for State are XARMOD_STATE_ANY, XARMOD_STATE_UNINITIALISED,
 * XARMOD_STATE_INACTIVE, XARMOD_STATE_ACTIVE, XARMOD_STATE_MISSING,
 * XARMOD_STATE_UPGRADED, XARMOD_STATE_INSTALLED
 * The XARMOD_STATE_ANY means that any state is valid.
 * The default value of State is XARMOD_STATE_ACTIVE.
 * For other criteria there's no default value.
 * The orderby parameter specifies the order by which is sorted the result
 * array, can be one of name, regid, class, category or a combination of them,
 * the default is name.
 * You can combine those fields to obtain a good ordered list simply by
 * separating them with the '/' character, i.e. if you want to order the list
 * first by class, then by category and lastly by name you pass
 * 'class/category/name' as orderby parameter
 *
 * @author Marco Canini <marco@xaraya.com>
 * @param filter array of criteria used to filter the entire list of installed
 *        modules.
 * @param startnum integer the start offset in the list
 * @param numitems integer the length of the list
 * @param orderby string the order type of the list
 * @return array array of module information arrays
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function modules_adminapi_getlist($args)
{
    extract($args);
    static $validOrderFields = array('name' => 'mods', 'regid' => 'mods', 'status'=>'states','state'=>'states',
                                     'class' => 'mods', 'category' => 'mods');

    if (!isset($filter)) $filter = array();

    if (!is_array($filter)) {
        $msg = xarML('Parameter filter must be an array.');
        throw new BadParameterException(null,$msg);
    }
    // Optional arguments.
    $startnum = isset($filter['startnum'])? $filter['startnum'] : 1;
    $numitems = isset($filter['numitems'])? (int)$filter['numitems'] : -1;

    $count = isset($filter['count'])? $filter['count'] : 0;
    $orderby = isset($filter['orderby']) && !empty($filter['orderby'])? $filter['orderby'] : 'name';
    $sort = isset($filter['sort'])? $filter['sort'] : 'ASC';

    // Determine the tables we need to consider
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;
    $modulestable = $tables['modules'];

    // Construct the order by clause and join it up into one string
    $orderFields = explode('/', $orderby);
    $orderbyClauses = array(); $extraSelectClause = '';

    foreach ($orderFields as $orderField) {

        if (!isset($validOrderFields[$orderField])) {
            throw new BadParameterException('orderby');
        }
        if ($orderField == 'status') $orderField = 'state';

        // Here $validOrderFields[$orderField] is the table alias
        $orderbyClauses[] = $validOrderFields[$orderField] . '.xar_' . $orderField;
        if ($validOrderFields[$orderField] == 'mods') {
            $extraSelectClause .= ', ' . $validOrderFields[$orderField] . '.xar_' . $orderField;
        }
    }
    $orderbyClause = join(', ', $orderbyClauses) . ' ' .$sort;

    // Keep a record of the different conditions and their bindvars
    $whereClauses = array(); $bindvars = array();
    if (isset($filter['Mode'])) {
        $whereClauses[] = 'mods.xar_mode = ?';
        $bindvars[] = $filter['Mode'];
    }
    if (isset($filter['UserCapable'])) {
        $whereClauses[] = 'mods.xar_user_capable = ?';
        $bindvars[] = $filter['UserCapable'];
    }
    if (isset($filter['AdminCapable'])) {
        $whereClauses[] = 'mods.xar_admin_capable = ?';
        $bindvars[] = $filter['AdminCapable'];
    }
    if (isset($filter['Class'])) {
        $whereClauses[] = 'mods.xar_class = ?';
        $bindvars[] = $filter['Class'];
    }
    if (isset($class)) {
        $whereClauses[] = 'mods.xar_class = ?';
        $bindvars[] = $class;
    }
    if (isset($filter['Category'])) {
        $whereClauses[] = 'mods.xar_category = ?';
        $bindvars[] = $filter['Category'];
    }

    if (isset($filter['modulelist'])) {
        $whereClauses[] = "mods.xar_regid IN ({$filter['modulelist']})";
        //$bindvars[] = $filter['modulelist'];
    }
    if (isset($filter['State'])) {
        if ($filter['State'] != XARMOD_STATE_ANY) {
            if ($filter['State'] != XARMOD_STATE_INSTALLED) {
                $whereClauses[] = 'mods.xar_state = ?';
                $bindvars[] = $filter['State'];
            } else {
                $whereClauses[] = 'mods.xar_state != ? AND mods.xar_state < ? AND mods.xar_state != ?';
                $bindvars[] = XARMOD_STATE_UNINITIALISED;
                $bindvars[] = XARMOD_STATE_MISSING_FROM_INACTIVE;
                $bindvars[] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;
            }
        }
    } else {
        $whereClauses[] = 'mods.xar_state = ?';
        $bindvars[] = XARMOD_STATE_ACTIVE;
    }

    $modList = array();
    //$mode = XARMOD_MODE_SHARED;

    $query = "SELECT mods.xar_regid, mods.xar_name, mods.xar_directory,
                     mods.xar_version, mods.xar_id, mods.xar_state
              FROM $modulestable mods ";

    if (!empty($whereClauses)) {
        $whereClause = join(' AND ', $whereClauses);
        $query .= " WHERE $whereClause ";
    }
    $query .= " ORDER BY $orderbyClause";

    if (isset($numitems) && is_numeric($numitems)) {
        $result = $dbconn->SelectLimit($query, $numitems, $startnum-1,$bindvars);
    } else {
        $result = $dbconn->Execute($query,$bindvars);
    }

    if (!$result) return;
    while(!$result->EOF) {
        list($modInfo['regid'],
             $modInfo['name'],
             $modInfo['directory'],
             $modInfo['version'],
             $modInfo['systemid'],
             $modState) = $result->fields;

        if (xarCoreCache::isCached('Mod.Infos', $modInfo['regid'])) {
            // Get infos from cache
            $modList[] = xarCoreCache::getCached('Mod.Infos', $modInfo['regid']);
        } else {
            $modInfo['regid'] = (int)$modInfo['regid'];
           // $modInfo['displayname'] = xarMod::getDisplayableName($modInfo['name']);
           // $modInfo['displaydescription'] = xarMod::GetDisplayableDescription($modInfo['name']);
            // Shortcut for os prepared directory
            $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);
            $modInfo['state'] = (int) $modState;

            xarCoreCache::setCached('Mod.BaseInfos', $modInfo['name'], $modInfo);

            // jojo - get the file info we need once
            $modfileinfo = xarMod::getFileInfo($modInfo['osdirectory']);
           // get the display info from modfileinfo and save more function calls
            $modInfo['displayname'] = $modfileinfo['displayname'];
            $modInfo['displaydescription'] = $modfileinfo['displaydescription'];
            if (isset($modfileinfo)) {
                $modInfo = array_merge($modfileinfo, $modInfo);
                xarCoreCache::setCached('Mod.Infos', $modInfo['regid'], $modInfo);
                switch ($modInfo['state']) {
                    case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
                        $modInfo['state'] = XARMOD_STATE_UNINITIALISED;
                        break;
                    case XARMOD_STATE_MISSING_FROM_INACTIVE:
                        $modInfo['state'] = XARMOD_STATE_INACTIVE;
                        break;
                    case XARMOD_STATE_MISSING_FROM_ACTIVE:
                        $modInfo['state'] = XARMOD_STATE_ACTIVE;
                        break;
                    case XARMOD_STATE_MISSING_FROM_UPGRADED:
                        $modInfo['state'] = XARMOD_STATE_UPGRADED;
                        break;
                }
            }

            $modList[] = $modInfo;
        }
        $modInfo = array();
        $result->MoveNext();
    }

    $result->Close();

    return $modList;
}
?>
