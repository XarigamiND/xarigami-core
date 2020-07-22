<?php
/**
 * Test the email settings
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007,2008,2009 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team 
 */

/**
 * Test the email settings
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_compose()
{
    // Security Check
    if (!xarSecurityCheck('AdminMail',0)) return xarResponseForbidden();
    // Generate a one-time authorisation code for this operation
    $authid        = xarSecGenAuthKey();

    //common admin menu
    $menulinks= xarMod::apiFunc('mail','admin','getmenulinks');   
    
    // Get the admin email address
    $email  = xarModGetVar('mail', 'adminmail');
    
    if (strpos($email,'invalid.tld')) $email='';
    
    $name  = xarModGetVar('mail', 'adminname');
    $data =  array('invalid'=>'',
                    'message'=>'',
                    'email' =>$email,
                    'recipients'=>'',
                    'subject'=>'',
                    'name'=>$name,
                    'emailcc'=>'',
                    'namecc'=>'',
                    'emailbcc'=>'',
                    'namebcc'=>'',
                    'ccrecipients'=>'',
                    'bccrecipients'=>'',
                    'authid'=> xarSecGenAuthKey() ,
                    'menulinks'=>$menulinks       
                    );
    // everything else happens in Template for now
    return $data;
}
?>