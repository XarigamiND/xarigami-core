<?php
/**
 * View the current mail queue
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
 * View the current mail queue (if any)
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   $sentstatus int 0 - fail, 1 - sent, 2- pending
 * @return  true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_viewq($args)
{
    extract($args);
    if (!xarVarFetch('action','str', $action, '')) return;
    if (!xarVarFetch('startnum', 'int:1:', $startnum, 1, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('sentstatus','int:0:2', $sentstatus, 0, XARVAR_GET_OR_POST)) return;
    if (!xarVarFetch('checklist',  'array',  $checklist, NULL,XARVAR_NOT_REQUIRED)) {return;}    
    if (!xarVarFetch('bulk',    'int',  $bulk,  null,XARVAR_NOT_REQUIRED)) {return;};      
      
    if (!xarSecurityCheck('AdminMail',0)) return xarResponseForbidden();

    $data = array();
    if (!empty($action)) {

        switch ($action)
        {
            case 'process':

                // Confirm authorisation code
                if (!xarSecConfirmAuthKey()) return;

                // trigger the sendmail function as if the Scheduler module did it
                $log = xarMod::apiFunc('mail','scheduler','sendmail');
                if (!isset($log)) return;

                // redirect so we avoid reload issues
                xarSession::setVar('statusmsg', $log);
                xarResponseRedirect(xarModURL('mail', 'admin', 'viewq',array('sentstatus'=>$sentstatus)));

                break;

            case 'view':
                if (!xarVarFetch('mid','str', $mid, '')) return;
                if (!empty($mid)) {
                    // retrieve the message
                    $data['mail'] = xarMod::apiFunc('mail', 'admin', 'get', array('mid' => $mid));
                }
                break;

            case 'delete':

                // Confirm authorisation code
                if (!xarSecConfirmAuthKey()) return;
                if (!xarVarFetch('mid','id', $mid, '')) return;

                if (!empty($mid)) {
                    // delete the message
                    if (!xarMod::apiFunc('mail', 'admin', 'delete', array('mid' => $mid))) {
                        $msg = xarML('Failed deleting message from queue!');
                        throw new BadParameterException(null,$msg);
                    }
                    // set status and return
                    xarSession::setVar('statusmsg', xarML('Message deleted.'));
                    xarResponseRedirect(xarModURL('mail', 'admin', 'viewq',array('sentstatus'=>$sentstatus)));
                    return true;
                }
                break;

            default:
                break;
        }
    }
     //let's get to it if there is a bulk action
    if (is_array($checklist) && !empty($checklist) && isset($bulk)) {

        $checklist = array_keys($checklist);

        if ($bulk = -1) { //we're  deleting 

            foreach($checklist as $id) {
                $deleted = xarMod::apiFunc('mail','admin','delete',
                          array('mid' => $id));
             }
        }
    }
    $numitems = xarModGetVar('mail','itemsperpage')? xarModGetVar('mail','itemsperpage'):30;
 
    $authid = xarSecGenAuthKey();
    $data['scheduleravailable'] = xarMod::isAvailable('scheduler');
    // get items and check for exceptions
    
    $items = xarMod::apiFunc('mail', 'admin', 'getall', array(
        'startnum' => $startnum, 'numitems' => $numitems, 'sentstatus'=> $sentstatus ));

    $job = array();
    if ( $data['scheduleravailable'] ) { 
        $job = xarMod::apiFunc('scheduler','user','get',
                array('module' => 'mail', 'type' => 'scheduler', 'func' => 'sendmail')
            );
        
        $jobint = empty($job['interval']) || ($job['interval'] == '0t') ?'':$job['interval'] ;   
        
        if ((empty($job) || empty($jobint)) && count($items)>0) {
            $queuenote = xarML('Scheduler is currently set to OFF and any unsent items on the queue will not be sent.');
        }
    }

    $now = time();
    $data['queuenote'] = isset($queuenote)?$queuenote:'';
    
    // add links as necessary
    foreach ($items as $i => $item) {

        $items[$i]['viewurl'] = xarModURL('mail', 'admin', 'viewq', array('mid' => $item['mid'], 'action' => 'view', 'sentstatus'=> $sentstatus));
        $items[$i]['deleteurl'] = xarModURL('mail', 'admin', 'viewq', array('mid' => $item['mid'], 'action' => 'delete', 'sentstatus'=> $sentstatus, 'authid' => $authid));

        // set status
        if ($item['sent'] > 0) {
            $items[$i]['status'] = xarML('Already sent');
        } elseif ($now >= $item['when'] && $item['sent'] == 0) {
            $items[$i]['status'] = xarML('To be sent on next trigger');
        } elseif ($item['sent'] == -1) {
            $items[$i]['status'] = xarML('Send failure');
        } else {
            $datev = xarLocaleGetFormattedDate('medium', $item['when']);
            $timev =xarLocaleGetFormattedTime('medium', $item['when']);
            $items[$i]['status'] = xarML('Scheduled for #(1) #(2)',$datev,$timev);
        }

    }
    $data['items'] = &$items;
    
    // get pager
    $countargs = array('sentstatus'=>$sentstatus);
    $data['pager'] = xarTplGetPager(
        $startnum,
        xarMod::apiFunc('mail', 'admin', 'countitems', $countargs),
        xarModURL('mail', 'admin', 'viewq', array('startnum' => '%%','sentstatus'=>$sentstatus)),
        $numitems
    );
  //setup bulk action choices
    $data['bulk'] = array(
          
            '-1'=> xarML('Delete')
            );   
    $data['sentstatus'] = $sentstatus;
    $data['authid'] = $authid;
    //common admin menu
    $data['menulinks'] = xarMod::apiFunc('mail','admin','getmenulinks');   
    $data['mailunsent'] = xarModURL('mail','admin','viewq',array('sentstatus'=>2));
    $data['mailsent'] = xarModURL('mail','admin','viewq',array('sentstatus'=>1));    
    return $data;

}

?>