<?php
/**
 * Main user menu
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 * @author Xarigami Team
 */
/**
 * Show the user menu
 * @author Xaraya Core Development Group
 */
function roles_user_usermenu($args)
{
    // Security check
    //we should not be able to access this except when logged in and through account function
    //anyone logged in should be able to see their profile - they may not have view level privs
    //  if (!xarSecurityCheck('ViewRoles')) return;
    if (!xarUserIsLoggedIn()) return;

    //the edit account tab is viewable when roles is hooked to roles
    //Do not allow editing unless this is the case
    //this is handled by the usereditaccount modvar
    $usereditaccount = xarModGetvar('roles','usereditaccount');
    //we are logged in, so return to display if no edit is available
    if (!$usereditaccount) {
        xarResponseRedirect(xarModURL('roles','user','account'));
    }

    extract($args);
    if(!xarVarFetch('phase','notempty', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));
    $data = array(); $hooks = array();

    switch(strtolower($phase)) {

        case 'menu':
            $dummyimage    = xarTplGetImage('blank.gif', 'base');
            $iconbasic  = 'sprite xs-go-home';
            $iconenhanced = 'sprite xs-go-home';
            $current      = xarModURL('roles', 'user', 'account', array('moduleload' => 'roles'));
            $data         = xarTplModule('roles','user', 'user_menu_icon',
                                      array('iconbasic'    => $iconbasic,
                                            'iconenhanced' => $iconenhanced,
                                            'current'      => $current));

            break;
        case 'form':
        case 'formbasic':
         if(!xarVarFetch('invalid',   'array', $invalid,      array(), XARVAR_NOT_REQUIRED)) {return;}
            $properties = null;
            $withupload = (int) FALSE;

            $propertyvalues= array();
            //check if we've been here before and returning with values to recheck
            $values = xarSessionGetVar('roles.usermenu');
            $values = unserialize($values);

            $uid     = xarUserGetVar('uid');
            $values['uid'] = $uid;
            if (xarMod::isHooked('dynamicdata','roles',0)) {
                // get the Dynamic Object defined for this module (and itemtype, if relevant)
                $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                        array('modid' => xarMod::getId('roles'),
                                              'itemtype'=> 0
                                             )
                                        );

                if (isset($object) && !empty($object->objectid)) {
                //always take the new posted vars, not the old ones
                //here we are getting all properties even hidden or inactive or display only
                    $object->getItem(array('itemid'=>$uid));
                    $properties =& $object->getProperties();
                    foreach ($properties as $name=>$property) {
                       // $invalid[$name] =  $property->invalid; //jojo - we dont' need invalid here - it is passed in from the submit
                        $propertyvalues[$name] =   $property->value;
                    }
                }

                if (is_array($properties)) {
                    foreach ($properties as $key => $prop) {
                        if (isset($prop->upload) && $prop->upload == TRUE) {
                            $withupload = (int) TRUE;
                        }
                    }
                }
            }

            $values['invalid']= $invalid;
            $values['propertyvalues']=   $propertyvalues;

            unset($properties);
            $values['withupload'] = $withupload;
            $values['uname']    = !isset($values['uname']) ? xarUserGetVar('uname') :$values['uname'];
            $values['dname']    = !isset($values['dname']) ? xarUserGetVar('name') : $values['dname'];

            $values['email']    = isset($values['email']) ? $values['email'] : '';
            $values['emailaddress'] =  xarUserGetVar('email');
            $role       = xarUFindRole($values['uname']);
            $values['home']      = !isset($values['home']) ? xarModUserVars::get('roles','userhome') : $values['home'];// now user mod var not 'duv'. $role->getHome();
            $values['usersendemails'] = xarModGetVar('roles','usersendemails');
            $values['allowemail']= xarModUserVars::get('roles','allowemail',$uid); //allow someone to send an email to the user via a form

            if (xarModGetVar('roles','setuserlastlogin')) {
                //only display it for current user or admin
                if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) { //they should be but ..
                    $userlastlogin=xarSessionGetVar('roles_thislastlogin');
                    $usercurrentlogin=xarModUserVars::get('roles','userlastlogin',$uid);
                }elseif (xarSecurityCheck('AdminRole',0,'Roles',$uid) && xarModUserVars::get('roles','userlastlogin',$uid)){
                    $usercurrentlogin='';
                    $userlastlogin= xarModUserVars::get('roles','userlastlogin',$uid);
                }else{
                    $userlastlogin='';
                    $usercurrentlogin='';
                }
            }else{
                $userlastlogin='';
                $usercurrentlogin='';
            }
            $values['setusertimezone'] = xarModGetVar('roles','setusertimezone');
            $values['userlastlogin']    = $userlastlogin;
            $values['usercurrentlogin'] = $usercurrentlogin;
            $values['authid']           = xarSecGenAuthKey('roles');
            $values['submitlabel']      = xarML('Submit');
            $values['upasswordupdate']  = xarModUserVars::get('roles','passwordupdate',$uid);
            //usertimezone - might be empty
            //default timezone
            $defaulttimezonedata = @unserialize(xarModGetVar('roles','usertimezone'));
            $defaulttimezone = $defaulttimezonedata['timezone'];
            $usertimezonedata = @unserialize(xarModUserVars::get('roles','usertimezone',$uid));
            $values['usertimezone']  = isset($usertimezonedata['timezone']) && !empty($usertimezonedata['timezone']) ? $usertimezonedata['timezone']:  $defaulttimezone;
             //call modify hooks in case we want to use them in the template
            $item['module']   = 'roles';
            $values['hooks']   = xarMod::callHooks('item','modify',$values['uid'],$item);

            $data['values'] = $values;

            $values['readytoreset'] = isset($readytoreset) ? $readytoreset : 0; //this is advance password reset

            return xarTplModule('roles','user', 'user_menu_form', $values);

            break;

        case 'formenhanced':
            $name = xarUserGetVar('name');
            $uid = xarUserGetVar('uid');
            $authid = xarSecGenAuthKey('roles');
            $item['module'] = 'roles';
            $hooks = xarMod::callHooks('item','modify',$uid,$item);

            $data = xarTplModule('roles','user', 'user_menu_formenhanced', array('authid' => $authid,
                                                                                 'name'   => $name,
                                                                                 'uid'    => $uid,
                                                                                 'hooks'  => $hooks));

            break;
        case 'updatebasic':
            if (!xarVarFetch('uid',        'isset',    $uid,        NULL,  XARVAR_DONT_SET)) return;
            if (!xarVarFetch('dname',      'isset',    $dname,      NULL,  XARVAR_DONT_SET)) return;
            if (!xarVarFetch('email',      'isset',    $email,      NULL,  XARVAR_DONT_SET)) return;
            if (!xarVarFetch('home',       'isset',    $home,       NULL,  XARVAR_DONT_SET)) return;
            if (!xarVarFetch('pass1',      'isset',    $pass1,      NULL,  XARVAR_DONT_SET)) return;
            if (!xarVarFetch('pass2',      'isset',    $pass2,      NULL,  XARVAR_DONT_SET)) return;
            if (!xarVarFetch('allowemail', 'checkbox', $allowemail, false, XARVAR_DONT_SET)) return;
            if (!xarVarFetch('utimezone',  'str:1:',   $utimezone,  NULL,  XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('readytoreset', 'int',   $readytoreset,  0,  XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('template','str:1:100',$template,'',XARVAR_NOT_REQUIRED)) return;

            xarSession::delVar('roles.usermenu'); // get rid of any existing values
            $uname = xarUserGetVar('uname');

            //set emailing options for the user
            $uid = $uid ? $uid : xarUserGetVar('uid');
            xarModSetVar('roles','allowemail',false);// let's make sure global is always false by default
            xarModSetUserVar('roles', 'allowemail',$allowemail, $uid);

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;
            $dopasswordupdate=false; //switch

            //adjust the timezone value for saving
            if (xarModGetVar('roles','setusertimezone')) {
                if (isset($utimezone)) {
                    $timezoneinfo = new DateTimezone($utimezone);
                }else {
                    $timezoneinfo = new DateTimezone(xarConfigGetVar('Site.Core.TimeZone'));
                }
                $datetime = new DateTime();
                $offset = $timezoneinfo->getOffset($datetime);
                $timeinfoarray = array('timezone' => $utimezone, 'offset' => $offset/(60*60)); //need it in hours not sec
                $usertimezone  = serialize($timeinfoarray);

                xarModSetUserVar('roles', 'usertimezone', $usertimezone, $uid);
            } else {
                xarModSetUserVar('roles','usertimezone','', $uid);
                $usertimezone = '';
            }

             /* Check if external urls are allowed in home page */
            $allowexternalurl=xarModGetVar('roles','allowexternalurl');
            $url_parts = parse_url($home);
            if (!$allowexternalurl) {
                if ((preg_match("%^http://%", $home, $matches)) &&
                    ($url_parts['host'] != $_SERVER["SERVER_NAME"]) &&
                    ($url_parts['host'] != $_SERVER["HTTP_HOST"])) {

                    $msg = xarML('External URLs such as #(1) are not permitted in your User Account.', $home);
                     throw new BadParameterException(null,$msg);
                    $home=''; //reset and return with error
                    return;
                }
            }

            //initialize variable to hold errors in submitted data
            $invalid=NULL;

            // check display name if required
            $requiredisplayname = xarModGetVar('roles','requiredisplayname');
            if ($requiredisplayname == TRUE) {
                $invalid['dname'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'displayname', 'var'=>$dname,'uid'=>$uid));
            } elseif (empty($dname)) {
                $dname= xarUserGetVar('uname',$uid);
            }
            //check the passwords
            $pass = '';
            if (($readytoreset ==1) && (trim($pass1)=='') && (trim($pass2) =='')) {
                $invalid['pass1'] = xarML('<br />You must enter a valid password to be able to log in again');
            } else {
                $invalid['pass1'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass1', 'var'=>$pass1,'uid'=>$uid ));
                if (empty($invalid['ppass1'])) {
                    $invalid['pass2'] = xarMod::apiFunc('roles','user','validatevar', array('type'=>'pass2', 'var'=>array($pass1,$pass2),'uid'=>$uid ));
                }
                if (empty($invalid['pass1']) && empty($invalid['pass2']))   {
                    $pass = $pass1;
                    if (xarModGetVar('roles','setpasswordupdate') && !empty($pass)){
                            $dopasswordupdate=true;
                   }
                }
            }

            // save the exising email for use
            $oldemail = xarUserGetVar('email');
            //Are we changing the email address?
            // Step 1) Validate the new email address for errors,
            if (!empty($email)){
                //step 1 (see below)
                $invalid['email'] = xarMod::apiFunc('roles','user','validatevar',
                                      array('var'  => $email,
                                            'type' => 'email',
                                            'uid'=>$uid));
            }

            if (!isset($invalid['email']) || empty($invalid['email'])) {
                //the email address is valid - is is different from the old one?
                if (trim($oldemail) == trim($email)) {
                    $email = ''; //no change
                }
            }
            //grab any dd properties - show or hide in the template accordingly
            //initialize our vars
            $properties = null; //array of properties
            $isvalid = true;  //variable signifying valid data

            $propertyvalues = array();
            //handle the errors ourselves here for DD as we don't want to call update hook yet
            //don't go here if this is just password reset so we don't spoil the reset process
            if (xarMod::isHooked('dynamicdata','roles',0) && $readytoreset !=1) {
                // get the Dynamic Object defined for this module (and itemtype, if relevant)
                 $object = xarMod::apiFunc('dynamicdata','user','getobject',
                                        array('modid' => xarMod::getId('roles'),
                                              'itemtype'=> 0
                                             )
                                        );

                if (isset($object) && !empty($object->objectid)) {
                    // check the values submitted for the DD object  properties
                    $object->getItem(array('itemid'=>$uid));
                    $isvalid = $object->checkInput();
                    // get the Dynamic Properties of this object
                    $properties =& $object->getProperties();
                    foreach ($properties as $name=>$property) {
                        //see if we have any invalid values and need to return
                        $invalid[$name]=$property->invalid;
                        $propertyvalues[$name] = $property->value;
                     }

                }
            } else {
               $properties = array();
            }

            $a = array_count_values($invalid); // $a[''] will be the count of null values
            if (!isset($a[''])) $a['']='';
            $countInvalid = count($invalid) - $a[''];
            $values['message'] = '';
            if ($uid == XARUSER_LAST_RESORT) {
                    $values['message'] = xarML('You are logged in as the last resort administrator.');
                } else  {
                    $values['current'] = xarModURL('roles', 'user', 'display', array('uid' => xarUserGetVar('uid')));
                    $output = array();
                     $output = xarMod::callHooks('item', 'usermenu', '', array('module' => 'roles'));
                      $value['output'] = $output;
            }

            //if we have invalid data - we need to return and show the form again
            //jojo - we can have problems here due to the way this userhook works esp with short urls
            //we need to do it with sessions vars for reliability
            $values = array('name'         => $dname,
                            'uname'         => $uname,
                            'email'         => $email,
                            'emailaddres'   => $oldemail,
                            'invalid'       => $invalid,
                            'home'          => $home,
                            'allowemail'    => $allowemail,
                            'utimezone'     => $utimezone,
                            'uid'           => $uid,
                            'moduleload'    => 'roles',
                            'phase'         => 'formbasic',
                            'module'        => 'roles',
                            'propertyvalues' => $propertyvalues,
                            'readytoreset'  => $readytoreset,
                            'authmodule'=> 'authsystem',
                            'logoutmodule' => 'authsystem',
                            'loginmodule' => 'authsystem',
                            'setpasswordupdate' =>  xarModGetVar('roles','setpasswordupdate'),
                            'usersendemails' => xarModUserVars::get('roles', 'usersendemails'),
                            'avatar_type' => xarUserGetVar('avatar_type',$uid)
                             //don't add plain text passwords
                            );

            if ($countInvalid > 0 || !$isvalid) {
                // if so, return to the previous template
                $dopasswordupdate = false;
                 xarTplSetMessage(xarML('Your profile has not been saved. Please check for errors in your submitted form.'),'error');
                 return xarTplModule('roles','user','account',$values,$template);
                //xarResponseRedirect(xarModURL('roles','user','account',array('moduleload'=>'roles','readytoreset'=>$readytoreset,)));
            }

            //we have successfully got through validations, reset var
            $readytoreset = 0;
            /* updated steps for changing email address

               2) Check if validation is required and if so create confirmation code
               3) Change user status to 2 (if validation is set as option)
               4) If validation is required for a change, send the user an email about validation
               5) if user is logged in (ie existing user), log user out
               6) Display appropriate message
            */
            if (!empty($email)) { //let's continue wtih changing email address steps
                // Step 2 Check for validation required or not
                $requireValidation = xarModGetVar('roles', 'requirevalidation');
                if ((!xarModGetVar('roles', 'requirevalidation')) || (xarUserGetVar('uname') == 'admin')){
                    // The API function is called.
                    if(!xarMod::apiFunc('roles',  'admin', 'update',
                                       array('uid'     => $uid,
                                             'uname'   => $uname,
                                             'name'    => $dname,
                                             'home'    => $home,
                                             'email'   => $email,
                                             'usertimezone' => $usertimezone,
                                             'state'   => ROLES_STATE_ACTIVE,
                                             'pass'  => $pass,
                                             'usertimezone' => $usertimezone,
                                             'dopasswordupdate' => $dopasswordupdate))) return;
                } else { // if we need validation
                    // Step 2
                    // Create confirmation code and time registered
                    $confcode = xarMod::apiFunc('roles','user','makepass');

                    // Step 3
                    // Set the user to not validated
                    // The API function is called.
                    if(!xarMod::apiFunc('roles', 'admin', 'update',
                                       array('uid'      => $uid,
                                             'uname'    => $uname,
                                             'name'     => $dname,
                                             'home'     => $home,
                                             'email'    => $email,
                                             'usertimezone' => $usertimezone,
                                             'valcode'  => $confcode,
                                             'state'    => ROLES_STATE_NOTVALIDATED,
                                             'pass'  => $pass,
                                             'usertimezone' => $usertimezone,
                                             'dopasswordupdate' => $dopasswordupdate
                                             ))) return;
                    // Step 4
                    //Send validation email
                    if (!xarMod::apiFunc( 'roles',  'admin', 'senduseremail',
                                  array('uid' => array($uid => '1'), 'mailtype' => 'validation'))) {

                        $msg = xarML('Problem sending confirmation email');
                        throw new DataNotFoundException(null,$msg);
                    }
                    // Step 5
                    // Log the user out. This needs to happen last
                    xarUserLogOut();

                    //Step 6
                    //Show a nice message for the person about email validation
                    $data = xarTplModule('roles', 'user', 'waitingconfirm');
                    return $data;
                }
            } else {
                $oldemail = xarUserGetVar('email');

                // The API function is called.
                if(!xarMod::apiFunc('roles', 'admin', 'update',
                                   array('uid'     => $uid,
                                         'uname'   => $uname,
                                         'name'    => $dname,
                                         'home'    => $home,
                                         'email'   => $oldemail,
                                         'pass'     => $pass,
                                         'usertimezone'=> $usertimezone,
                                         'dopasswordupdate' => $dopasswordupdate,
                                         'state'   => ROLES_STATE_ACTIVE))) return;
            }

            $msg = xarML('Your account settings have been successfully updated.');
            xarTplSetMessage($msg,'status');
            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account', array('moduleload'=>'roles')));
            return true;
        case 'updateenhanced':
            // Redirect
            $msg = xarML('Your account settings have been successfully updated.');
            xarTplSetMessage($msg,'status');
            xarResponseRedirect(xarModURL('roles', 'user', 'account', array('moduleload'=>'roles','template')));
            return true;
    }

    return $data;
}
?>