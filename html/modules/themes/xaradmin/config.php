<?php
/**
 * Modify theme settings
 *
 * @package modules
 * @subpackage Xarigami Themes module
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * List theme vars and display form for input of new theme var
 *
 * @param id $ theme id
 * @return array An array of variables to pass to the template
 */
function themes_admin_config()
{
    if (!xarVarFetch('themeid', 'int:1:', $regid, NULL,  XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('invalid', 'array', $invalid, array(),   XARVAR_NOT_REQUIRED)) {return;}


    $invalid = xarSessionGetVar('themevars.invalid');

    //regenerate theme list to ensure latest file info in the db
    $changevars = array();
    $changevars = xarMod::apiFunc('themes','admin','regenerate');

    $proptypes = xarMod::apiFunc('dynamicdata','user','getproptypes');
    $sitethemedir = xarConfigGetVar('Site.BL.ThemesDirectory');
    $themeinfo = xarThemeGetInfo($regid);
    if (!isset($themeinfo)) return;

    $themename = $themeinfo['name'];
    // Security Check
    if (!xarSecurityCheck('AdminTheme', 0, 'All', '$themename::$regId')) return xarResponseForbidden();

    //get all the db theme vars - the configs should be set with prime or not prime
    $dbthemevars = xarThemeGetConfig(array('themename'=>$themeinfo['name']));
    //get the file theme vars
    $filethemevars = xarThemeGetConfig(array('themename'=>$themeinfo['name'],'configtype'=>'file'));

    $displayvars = array();
    $args['changed'] = '';

    //we want to know what is new, deleted, changed
    if (!isset($dbthemevars) || !is_array($dbthemevars)) $dbthemevars = array();


    foreach ($dbthemevars as $dbvar => $dbinfo) {
        $args = $dbinfo;
        $filedefault = isset($filethemevars[$dbvar]['value']) ?$filethemevars[$dbvar]['value']:'';
        $args['config']['default'] = isset($dbinfo['config']['default'])?$dbinfo['config']['default']: $filedefault;
        //config is made up of a number of options
        $args['propargs']  = isset($args['config']['propargs'])?$args['config']['propargs']:array(); //arguments to property
        $args['config']['type'] = isset($args['config']['type'])?$args['config']['type']:2;
         $args['config']['status'] = isset($args['config']['status'])?$args['config']['status']:0;
        $args['config']['propertyname'] = isset($args['config']['propertyname'])?$args['config']['propertyname']:'textbox';

        //update irregardless if set or not
        foreach($proptypes as $proptypeid=>$propinfo) {
            if ($propinfo['name'] == $args['config']['propertyname']) {
                $args['config']['type'] = $propinfo['format'];
            }
        }
        if (!isset ( $args['config']['type']) )  $args['config']['type']= 2; //prevent erroring
        //make sure we have all the required propargs
        $args['config']['label'] = isset($args['config']['label'])?$args['config']['label']:'';

        if (!empty($invalid[$dbvar])) {
            $args['value'] = $invalid[$dbvar]['value'];
        }
        $args['config']['validation'] =  $args['config']['propargs'];

        //just for property info
        $args['config']['name']      = $args['name'];
        $args['config']['value']      = $args['value'];

        $propinfo =  xarMod::apiFunc('dynamicdata','user','getproperty',$args['config']);

        $args['config']['validation'] = serialize($args['config']['validation']);
        $args['showinput']=$propinfo->showInput($args['config']);

        $args['formoutput'] = xarMod::apiFunc('dynamicdata','user','showoutput', $args['config']);
        //check for added or deleted or non-default
        if (isset($changevars) && is_array($changevars)) {
            foreach($changevars as $theme=>$changedvars) {
                if (is_array($changedvars)) {
                    foreach ($changedvars as $varname=>$changetype) {
                        if ($dbvar == $varname &&  $theme == $themename)  {
                            $args['changed'] = $changetype;
                        }
                    }
                }
            }
        }


        if ($args['prime'] == 1) {

            $args['restoreurl'] = xarModURL('themes','admin','configaction',
                                        array('themeid'=>(int)$regid,'varname'=>$args['name'],'action'=>'restore','authid'=> xarSecGenAuthKey())
                                    );
            $args['restoreimg'] = xarTplGetImage('icons/edit-undo.png');
        } else {
             $args['restoreurl'] ='';
             $args['restoreimg'] = '';
        }
        //check for action urls
        if (($args['prime'] == 1 && isset($args['changed']) && $args['changed'] == 2) || ($args['prime'] !=1) ) {
             $args['deleteurl'] = xarModURL('themes','admin','configaction',
                                        array('themeid'=>(int)$regid,'varname'=>$args['name'],'action'=>'del')
                                    );
            $args['deletestate'] = 'xar-icon';
            $args['deleteimg'] = 'esprite xs-delete';
        }else {
            $args['deleteurl']='';
            $args['deletestate'] = 'xar-icon-disabled';
            $args['deleteimg'] = 'esprite xs-delete';
        }
        $args['configurl'] =  xarModURL('themes','admin','configaction',
                                        array('themeid'=>(int)$regid,'varname'=>$args['name'],'action'=>'config')
                                    );
        $args['configimg'] = 'sprite xs-document-properties ';

        $displayvars[$dbvar] = $args;
        unset($args);

    }
    $newvar     = array( 'name'     => isset($invalid['newvarname']['name']) ? $invalid['newvarname']['name']:'',
                         'desc'     => isset($invalid['newvarname']['desc']) ? $invalid['newvarname']['desc']:'',
                         'value'    => isset($invalid['newvarname']['value']) ? $invalid['newvarname']['value']:'',
                         'proptype' => isset($invalid['newvarname']['proptype']) ? $invalid['newvarname']['proptype']:2

                         );
    $data['newvar'] = $newvar;
   //common admin menu
    $data['menulinks'] = xarMod::apiFunc('themes','admin','getmenulinks');
    $data['restoreallurl'] = xarModURL('themes','admin','configaction', array('themeid'=>(int)$regid,'action'=>'restoreall','authid'=> xarSecGenAuthKey()));
    $data['exporturl'] = xarModURL('themes','admin','exportvars',array('themeid'=>(int)$regid,'format'=>'php','vartype'=>'all'));
    $data['authid'] = xarSecGenAuthKey();
    $data['id'] = $regid;
    $data['themeid']= (int)$regid;
    $data['changevars'] = $changevars;
    $data['name'] = $themeinfo['name'];
    $data['themeinfo'] = $themeinfo;
    $data['filethemevars'] = $filethemevars;
    $data['displayvars'] = $displayvars;
    $data['savelabel'] = xarML('Update theme');
    $data['invalid'] = $invalid;
    xarSession::delVar('themevars.invalid');

    return $data;
}

?>