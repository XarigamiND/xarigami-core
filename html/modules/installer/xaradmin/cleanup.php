<?php
/**
 * Installer
 * @subpackage Xarigami Installer
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */

function installer_admin_cleanup()
{
 //jojo - replace this check with a better one once we finish new installer
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTplSetThemeName('installtheme');

    xarUserLogOut();
// log in admin user
    $uname = xarModGetVar('roles','lastuser');
    $pass = xarModGetVar('roles','adminpass');

    if (!xarUserLogIn($uname, $pass, 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        throw new BadParameterException(null,$msg);
    }

    $remove = xarModDelVar('roles','adminpass');
    $remove = xarModDelVar('installer','modules');

    // Load up database
    $dbconn = xarDB::$dbconn;
    $tables = &xarDB::$tables;

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = ?";

    // Check for db errors
    $result = $dbconn->Execute($query,array('right'));
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'right' not found.");
         throw new BadParameterException(null,$msg);
    }

    list ($rightBlockGroup) = $result->fields;

   //Get the info and add the Login block which is in authsystem module
    $loginBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                    array('module' => 'authsystem',
                                          'type'   => 'login'));

    if (empty($loginBlockType)) {
        throw new EmptyParameterException($loginBlockType,'Variable is empty "#(1)" ');
    }
    $loginBlockTypeId = $loginBlockType['tid'];

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'login'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Login',
                                 'name'     => 'login',
                                 'type'     => $loginBlockTypeId,
                                 'groups'    => array(array('gid'     => $rightBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = ?";

    // Check for db errors
    $result = $dbconn->Execute($query,array('header'));
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'header' not found.");
         throw new BadParameterException(null,$msg);
    }

    list ($headerBlockGroup) = $result->fields;

    $metaBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                   array('module' => 'themes',
                                         'type'   => 'meta'));
    if (empty($metaBlockType)) {
        throw new EmptyParameterException($metaBlockType,'Variable is empty "#(1)" ');
    }
    $metaBlockTypeId = $metaBlockType['tid'];

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'meta'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Meta',
                                 'name'     => 'meta',
                                 'type'     => $metaBlockTypeId,
                                 'groups'    => array(array('gid'      => $headerBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }

    //remove unceesary vars
    xarModDelVar('installer','modulelist');
    $data['language']    = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Step Six');
    $data['finalurl'] = xarModURL('installer', 'admin', 'finish');

    return $data;
}


?>