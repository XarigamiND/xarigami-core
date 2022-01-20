<?php
/**
 * Modify site configuration
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Modify site configuration
 * @return array of template values
 */
function authsystem_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminAuthsystem',0)) return xarResponseForbidden();
    if (!xarVarFetch('phase',        'str:1:100', $phase,           'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

    switch (strtolower($phase)) {
          case 'update':
            if (!xarVarFetch('shorturls',    'checkbox',  $shorturls,       FALSE,    XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('uselockout',   'checkbox',  $uselockout,      FALSE,    XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('lockouttime',  'int:1:',    $lockouttime,     15,       XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('lockouttries', 'int:1:',    $lockouttries,    3,        XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
            if (!xarVarFetch('aliasname',    'str:1:',    $aliasname,       '',       XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('modulealias',  'checkbox',  $modulealias,     FALSE,    XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('useauthcheck', 'checkbox',  $useauthcheck,    FALSE,    XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('hidemoduleurl','checkbox',  $hidemoduleurl,   FALSE,    XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            xarModSetVar('authsystem', 'SupportShortURLs', $shorturls);
            xarModSetVar('authsystem', 'uselockout', $uselockout);
            xarModSetVar('authsystem', 'useauthcheck', $useauthcheck);
            xarModSetVar('authsystem', 'lockouttime', $lockouttime);
            xarModSetVar('authsystem', 'lockouttries', $lockouttries);

            if (isset($aliasname) && trim($aliasname)<>'') {
                    xarModSetVar('authsystem', 'useModuleAlias', $modulealias);
                } else{
                     xarModSetVar('authsystem', 'useModuleAlias', 0);
                }
                $currentalias = xarModGetVar('authsystem','aliasname');
                $newalias = trim($aliasname);
                /* Get rid of the spaces if any, it's easier here and use that as the alias*/
                if ( strpos($newalias,'_') === FALSE )
                {
                    $newalias = str_replace(' ','_',$newalias);
                }
                $hasalias= xarModGetAlias($currentalias);
                $useAliasName= xarModGetVar('authsystem','useModuleAlias');
                // if a new one is set or if there is an old one there and we don't want to use alias anymore
                if ($useAliasName && !empty($newalias)) {
                     if ($aliasname != $currentalias)
                     /* First check for old alias and delete it */
                        if (isset($hasalias) && ($hasalias =='authsystem')){
                            xarModDelAlias($currentalias,'authsystem');
                        }
                        /* now set the new alias if it's a new one */
                        $newalias = xarModSetAlias($newalias,'authsystem');
                        if (!$newalias) { //name already taken so unset
                             xarModSetVar('authsystem', 'aliasname', '');
                             xarModSetVar('authsystem', 'useModuleAlias', FALSE);
                              xarModSetVar('authsystem', 'hidemoduleurl', FALSE);
                        } else { //it's ok to set the new alias name
                            xarModSetVar('authsystem', 'aliasname', $aliasname);
                            xarModSetVar('authsystem', 'useModuleAlias', $modulealias);
                            xarModSetVar('authsystem', 'hidemoduleurl', $hidemoduleurl);
                        }
                } else {
                   //remove any existing alias and set the vars to none and false
                        if (isset($hasalias) && ($hasalias =='authsystem')){
                            xarModDelAlias($currentalias,'authsystem');
                        }
                        xarModSetVar('authsystem', 'aliasname', '');
                        xarModSetVar('authsystem', 'useModuleAlias', FALSE);
                         xarModSetVar('authsystem', 'hidemoduleurl', FALSE);
                }

            $msg = xarML('Authsystem configuration settings updated.');
            xarTplSetMessage($msg,'status');
            xarResponseRedirect(xarModURL('authsystem', 'admin', 'modifyconfig'));
            // Return
            return true;
            break;
        case 'modify':
        default:

            $data['authid']           = xarSecGenAuthKey();
           
            $data['shorturlschecked'] = xarModGetVar('authsystem',  'SupportShortURLs')? xarModGetVar('authsystem',  'SupportShortURLs'): FALSE;
            $data['usealiasname']     = xarModGetVar('authsystem',  'useModuleAlias') ? xarModGetVar('authsystem',  'useModuleAlias'): FALSE;
            $data['aliasname']        = xarModGetVar('authsystem',  'aliasname');
            $data['hidemoduleurl']    = xarModGetVar('authsystem',  'hidemoduleurl');
            $data['uselockout']       = xarModGetVar('authsystem', 'uselockout') ;
            $data['lockouttime']      = xarModGetVar('authsystem', 'lockouttime')? xarModGetVar('authsystem', 'lockouttime'): 15; //minutes
            $data['lockouttries']     = xarModGetVar('authsystem', 'lockouttries') ? xarModGetVar('authsystem', 'lockouttries'): 3;
            $data['useauthcheck']     = xarModGetVar('authsystem', 'useauthcheck');
     
            break;


    }
    //common admin menu
   $data['menulinks'] = xarMod::apiFunc('authsystem','admin','getmenulinks');
    return $data;
}
?>
