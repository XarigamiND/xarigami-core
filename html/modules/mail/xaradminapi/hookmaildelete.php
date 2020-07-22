<?php
/**
 * Hook called to send mail on deletion of an item
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */
/**
 * This is a hook function that is called to send mail on deletion of an item
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param  $ 'modid' is the module that is sending mail.
 * @param  $ 'objectid' is the item deleted.
 */
function mail_adminapi_hookmaildelete($args)
{
    extract($args);

    if (!isset($objectid) || !is_numeric($objectid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'object ID', 'admin', 'hookmaildelete', 'mail');
        throw new BadParameterException($msg);
    }
    if (!isset($extrainfo) || !is_array($extrainfo)) {
        $extrainfo = array();
    }

    // When called via hooks, modname wil be empty, but we get it from the
    // extrainfo or the current module
    if (empty($modname)) {
        if (!empty($extrainfo['module'])) {
            $modname = $extrainfo['module'];
        } else {
            $modname = xarMod::getName();
        }
    }

    $modid = xarMod::getId($modname);
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
            'module name', 'admin', 'hookmaildelete', 'mail');
        throw new BadParameterException($msg);
    }

    if (!isset($itemtype) || !is_numeric($itemtype)) {
         if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
             $itemtype = $extrainfo['itemtype'];
         } else {
             $itemtype = 0;
         }
    }

    // Security Check
    //TODO: if we add to the hook to allow sending of mail to OTHER recipients than the admin
    // we will have to include the following security check and make sure the appropriate privileges are assigned
//    if (!xarSecurityCheck('DeleteMail', 0, 'All', "$modname::$objectid", 'mail')) return;

    // Set up variables
    $wordwrap = xarModGetVar('mail', 'wordwrap');
    $priority = xarModGetVar('mail', 'priority');
    $encoding = xarModGetVar('mail', 'encoding');
    if (empty($encoding)) {
        $encoding = '8bit';
        xarModSetVar('mail', 'encoding', $encoding);
    }
    $from = xarModGetVar('mail', 'adminmail');
    $fromname = xarModGetVar('mail', 'adminname');

// Get the templates for this message
    $strings = xarMod::apiFunc('mail','admin','getmessagestrings',
                             array('module' => 'mail',
                                   'template' => 'deletehook'));

    $subject = $strings['subject'];
    $message = $strings['message'];

// Get the template that defines the substitution vars
    $vars  = xarMod::apiFunc('mail','admin','getmessageincludestring',
                           array('module' => 'mail',
                                 'template' => 'message-vars'));

// Substitute the static vars in the template
    $subject  = xarTplCompileString($vars . $subject);
    $message  = xarTplCompileString($vars . $message);

// Substitute the dynamic vars in the template
    $data = $extrainfo;
    $data['modulename'] = $modname;
    $data['objectid'] = $objectid;
    $subject = xarTplString($subject, $data);
    $message = xarTplString($message, $data);

    // TODO How to do this with BL? Create yet another template? Don't think so.
// Send a formatted html message to the mail module for use if the admin has the html turned on.
    $htmlmessage = $message;

// Set mail args array
    $mailargs = array('info' => $from, // set info to $from
                      'subject' => $subject,
                      'message' => $message,
                      'htmlmessage' => $htmlmessage,
                      'name' => $fromname, // set name to $fromname
                      'priority' => $priority,
                      'encoding' => $encoding,
                      'wordwrap' => $wordwrap,
                      'from' => $from,
                      'fromname' => $fromname);
// Check if HTML mail has been configured by the admin
    if (xarModGetVar('mail', 'html')) {
        xarMod::apiFunc('mail', 'admin', 'sendhtmlmail', $mailargs);
    } else {
        xarMod::apiFunc('mail', 'admin', 'sendmail', $mailargs);
    }
// life goes on, and so do hook calls :)
    return $extrainfo;
}

?>
