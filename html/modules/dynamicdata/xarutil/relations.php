<?php
/**
 * Return relationship information
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Return relationship information (test only)
 */
function dynamicdata_util_relations($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();

    if(!xarVarFetch('module',    'isset', $module,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',     'isset', $modid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',  'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('objectid',  'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',     'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('field',     'isset', $field,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('value',     'isset', $value,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('relation',  'isset', $relation,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withobjectid', 'isset', $withobjectid, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withtable', 'isset', $withtable, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withfield', 'isset', $withfield, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('withvalue', 'isset', $withvalue, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('confirm',   'isset', $confirm,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('delete',    'isset', $delete,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('what',      'isset', $what,      NULL, XARVAR_DONT_SET)) {return;}

    $data = array('modid' => $modid,
                  'itemtype' => $itemtype,
                  'objectid' => $objectid,
                  'table' => $table,
                  'field' => $field,
                  'value' => $value,
                  'relation' => $relation,
                  'withobjectid' => $withobjectid,
                  'withtable' => $withtable,
                  'withfield' => $withfield,
                  'withvalue' => $withvalue);

    $data['prop'] = xarMod::apiFunc('dynamicdata','user','getproperty',array('type' => 'fieldtype', 'name' => 'dummy'));

    $dbconn = xarDB::$dbconn;
    $data['tables'] = $dbconn->MetaTables();
    $data['objects'] = xarMod::apiFunc('dynamicdata','user','getobjects');

    if (!empty($table) || !empty($objectid)) {
        $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                array('objectid' => $objectid,
                                      'table' => $table));
        $data['fields'] = $object->properties;
if (empty($table)) {
$table = $objectid;
}
        $relations = xarModGetVar('dynamicdata','relations');
        if (!empty($relations)) {
            $data['relations'] = unserialize($relations);
        } else {
            $data['relations'] = array();
        }
//echo '<pre>',var_dump($data['relations']),'</pre>';
        if (!empty($withtable) || !empty($withobjectid)) {
            $withobject = xarMod::apiFunc('dynamicdata','user','getobject',
                                        array('objectid' => $withobjectid,
                                              'table' => $withtable));
            $data['withfields'] = $withobject->properties;
if (empty($withtable)) {
$withtable = $withobjectid;
}
        }
        if (!empty($confirm)) {
            if (!xarSecConfirmAuthKey()) return;
            if (!empty($value)) {
                $field = $value;
            }
            if (!empty($withvalue)) {
                $withfield = $withvalue;
            }
            $data['relations'][$table][$withtable][] = array('from' => $field, 'to' => $withfield, 'type' => $relation);
            switch ($relation) {
                case 'parent':
                    $data['relations'][$withtable][$table][] = array('from' => $withfield, 'to' => $field, 'type' => 'child');
                    break;
                case 'child':
                    $data['relations'][$withtable][$table][] = array('from' => $withfield, 'to' => $field, 'type' => 'parent');
                    break;
                case 'direct':
                    $data['relations'][$withtable][$table][] = array('from' => $withfield, 'to' => $field, 'type' => 'directfrom');
                    break;
                case 'directfrom':
                    $data['relations'][$withtable][$table][] = array('from' => $withfield, 'to' => $field, 'type' => 'direct');
                    break;
                case 'recursive':
                    // nothing more to add
                    break;
                case 'extension':
                    $data['relations'][$withtable][$table][] = array('from' => $withfield, 'to' => $field, 'type' => 'extended');
                    break;
                case 'extended':
                    $data['relations'][$withtable][$table][] = array('from' => $withfield, 'to' => $field, 'type' => 'extension');
                    break;
            }
            $relations = serialize($data['relations']);
            xarModSetVar('dynamicdata','relations',$relations);
            xarResponseRedirect(xarModURL('dynamicdata', 'util', 'relations',
                                          array('table' => $table)));
            return true;
        } elseif (!empty($delete)) {
            if (!xarSecConfirmAuthKey()) return;
            foreach ($data['relations'][$table] as $where => $links) {
                foreach ($links as $idx => $link) {
                    if (!empty($what[$where][$idx])) {
                        unset($data['relations'][$table][$where][$idx]);
                    }
                }
            }
            $relations = serialize($data['relations']);
            xarModSetVar('dynamicdata','relations',$relations);
            xarResponseRedirect(xarModURL('dynamicdata', 'util', 'relations',
                                          array('table' => $table)));
            return true;
        }
    } elseif (!empty($modid)) {
        // (try to) get the relationships between this module and others
        $data['relations'] = xarMod::apiFunc('dynamicdata','util','getrelations',
                                           array('modid' => $modid,
                                                 'itemtype' => $itemtype));
    }
    if (!isset($data['relations']) || $data['relations'] == false) {
        $data['relations'] = array();
    }

    xarTpl::setAdminTheme('dynamicdata');

    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    return $data;
}

?>