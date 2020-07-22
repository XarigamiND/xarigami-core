<?php
/**
 * Import an object definition or an object item from XML
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Dynamic Data module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Import an object definition or an object item from XML
 */
function dynamicdata_util_import($args)
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData',0)) return xarResponseForbidden();
    $currentprefix = xarDB::$prefix;
    if(!xarVarFetch('basedir',    'isset', $basedir,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('import',     'isset', $import,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('xml',        'isset', $xml,         NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('refresh',    'isset', $refresh,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('keepitemid', 'isset', $keepitemid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('prefix',     'isset', $prefix,      $currentprefix, XARVAR_DONT_SET)) {return;}

    extract($args);

    $data = array();

    $data['warning'] = '';
    $data['options'] = array();
    $data['prefix'] = $prefix;
    if (empty($basedir)) {
        $basedir = 'modules/dynamicdata';
    }
    $data['basedir'] = $basedir;
    $data['authid'] = xarSecGenAuthKey();

    $filetype = 'xml';
    $files = xarMod::apiFunc('dynamicdata','admin','browse',
                           array('basedir' => $basedir,
                                 'filetype' => $filetype));
    if (!isset($files) || count($files) < 1) {
        $data['warning'] = xarML('There are currently no XML files available for import in "#(1)"',$basedir);
        return $data;
    }

    if (empty($refresh) && (!empty($import) || !empty($xml))) {
        if (!xarSecConfirmAuthKey()) return;

        if (empty($keepitemid)) {
            $keepitemid = 0;
        }
        if (!empty($import)) {
            $found = '';
            foreach ($files as $file) {
                if ($file == $import) {
                    $found = $file;
                    break;
                }
            }
            if (empty($found) || !file_exists($basedir . '/' . $file)) {
                $msg = xarML('File not found');
                throw new FileNotFoundException(null,$msg);
            }
            $objectid = xarMod::apiFunc('dynamicdata','util','import',
                                      array('file'       => $basedir . '/' . $file,
                                            'keepitemid' => $keepitemid,
                                            'prefix'     => $prefix));
        } else {
            $objectid = xarMod::apiFunc('dynamicdata','util','import',
                                      array('xml'        => $xml,
                                            'keepitemid' => $keepitemid,
                                            'prefix'     => $prefix));
            $import = 'xml';
        }
        if (empty($objectid)) return;

        $objectinfo = xarMod::apiFunc('dynamicdata','user','getobjectinfo',
                                    array('objectid' => $objectid));

        if (empty($objectinfo)) {
             $msg = xarML('There is a problem with object creation from your #(1)', $import);
            xarTplSetMessage($msg,'error');
            return;
        } else {
            $msg = xarML('Import and creation of a new object from "#(1)" was successfull.',$import);
            xarTplSetMessage($msg,'status');
        }

        xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                      array('itemid' => $objectid)));
        return true;
    }

    natsort($files);
    array_unshift($files,'');
    foreach ($files as $file) {
         $data['options'][] = array('id' => $file,
                                    'name' => $file);
    }

    xarTpl::setAdminTheme('dynamicdata');
    //common adminmenu
    $data['menulinks'] = xarMod::apiFunc('dynamicdata','admin','getmenulinks');
    return $data;
}

?>