<?php
/**
 * Update the configuration parameters
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Mail module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * Update the configuration parameters of the module based on data from the modification form
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   no parameters
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminMail',0)) return xarResponseForbidden();
    // Generate a one-time authorisation code for this operation
    $data['authid'] = xarSecGenAuthKey();
    // Quick Check for E_ALL
    $searchstrings = xarModGetVar('mail', 'searchstrings');
    $replacestrings = xarModGetVar('mail', 'replacestrings');
    if (!isset($searchstrings) || empty($searchstrings)){
        $searchstrings = serialize('%%Search%%');
        xarModSetVar('mail', 'searchstrings', $searchstrings);
    }
    if (!isset($replacestrings) || empty($replacestrings)){
        $replacestrings = serialize('Replace %%Search%% with this text');
        xarModSetVar('mail', 'replacestrings', $replacestrings);
    }

    $data['createlabel'] = xarML('Submit');
    $data['searchstrings'] = unserialize(xarModGetVar('mail', 'searchstrings'));
    $data['replacestrings'] = unserialize(xarModGetVar('mail', 'replacestrings'));

    // Get encoding
    $data['encoding'] = xarModGetVar('mail', 'encoding');
    $data['encodingoptions'] = array('7bit'=>'7bit','8bit'=>'8bit','binary'=>'binary','quoted-printable'=>'quoted-printable','base64'=>'base64');

    //redirect address - ensure it's set
    $redirectaddress = trim(xarModGetVar('mail', 'redirectaddress'));
    if (isset($redirectaddress) && !empty($redirectaddress)){
        $data['redirectaddress']=xarVarPrepForDisplay($redirectaddress);
    }else{
        $data['redirectaddress']='';
    }

    $scheduleravailable = xarMod::isAvailable('scheduler') && xarMod::isHooked('mail','scheduler');
    if ($scheduleravailable) {
        $data['intervals'] = xarMod::apiFunc('scheduler','user','intervals');
        // see if we have a scheduler job running to send queued mail
        $job = xarMod::apiFunc('scheduler','user','get',
                             array('module' => 'mail',
                                   'type' => 'scheduler',
                                   'func' => 'sendmail'));
        if (empty($job) || empty($job['interval']) || ($job['interval'] == '0t')) {
            $data['interval'] = '';
        } else {
            $data['interval'] = $job['interval'];
        }
    }
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('mail','admin','getmenulinks');
    $data['scheduleravailable'] = $scheduleravailable;

    //set vars for templates
    $data['adminname'] = xarModGetVar('mail', 'adminname', 1);
    $data['adminmail'] = xarModGetVar('mail', 'adminmail', 1);
    $data['showtemplates'] = xarModGetVar('mail', 'ShowTemplates');
    $data['replyto'] = xarModGetVar('mail', 'replyto');
    $data['replytoname'] =  xarModGetVar('mail', 'replytoname', 1);
    $data['replytoemail'] = xarModGetVar('mail', 'replytoemail', 1);
    $data['usehtml'] = xarModGetVar('mail', 'html');
    $data['htmluseheadfoot'] = xarModGetVar('mail', 'htmluseheadfoot');
    $data['htmlfooter'] = xarModGetVar('mail', 'htmlfooter', 0);
    $data['htmlheader'] = xarModGetVar('mail', 'htmlheader', 0);
    $data['textuseheadfoot'] =     xarModGetVar('mail', 'textuseheadfoot');
    $data['textheader'] =  xarModGetVar('mail', 'textheader', 1);
    $data['textfooter'] =  xarModGetVar('mail', 'textfooter', 1);
    $data['wordwrap'] =  xarModGetVar('mail', 'wordwrap', 1);
    $data['priority'] =  xarModGetVar('mail', 'priority');
    $data['priorityoptions'] = array('1'=>xarML('High'),'3'=>xarML('Normal'),'5'=>xarML('Low'));
    $data['loopmail'] = xarModGetvar('mail','loopmail'); //loop and send email rather than use dedicated cc/bc list
    $data['onbehalf'] = xarModGetvar('mail','onbehalf'); // when email sender is not from the same domain as sense use 'On behalf of  ...' and webmaster email address
    $data['server'] =  xarModGetVar('mail', 'server');
    $data['serveroptions'] = array('smtp'=>'SMTP','sendmail'=>'SendMail','mail'=>'Mail','qmail'=>'QMail');
    $data['sendmailpath'] =  xarModGetVar('mail', 'sendmailpath', 1);
    $data['smtpHost'] =  xarModGetVar('mail', 'smtpHost', 1);
    $data['smtpPort'] =  xarModGetVar('mail', 'smtpPort');
    $data['smtpAuth'] =  xarModGetVar('mail', 'smtpAuth');
    $data['smtpUserName'] = xarModGetVar('mail', 'smtpUserName', 1);
    $data['redirectsending'] = xarModGetVar('mail', 'redirectsending');
    $data['suppresssending'] = xarModGetVar('mail', 'suppresssending');
   // get time spans for throttling
    $data['timespans'] = xarMod::apiFunc('mail', 'admin', 'gettimespans');
   // everything else happens in Template for now
    return $data;
}
?>
