<?php
/**
 * Handle AIM property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/*
 * Handle AIM property
 * @author mikespub <mikespub@xaraya.com>
*/
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.Dynamic_URLIcon_Property');

/**
 * Class to handle the AIM and other IM properties
 *
 * @package dynamicdata
 */
class Dynamic_AIM_Property extends Dynamic_URLIcon_Property
{
    public $id         = 29;
    public $name       = 'aim';
    public $desc       = 'Social - AIM Address ';
    public $xv_icon_url;
    public $imlink;
    public $cssclass = '';
    public $xv_titletext = 'IM Icon';

    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'im';
        $this->filepath   = 'modules/roles/xarproperties';
    }


    function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;
        if (!empty($value)) {
            if (is_string($value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('IM identifier: #(1)', $this->name);
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    function showInput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            $link = str_replace('%value%',$value,$this->imlink);
        } else {
            $link = '';
        }
        $data['link']     = xarVarPrepForDisplay($link);

        $data['template'] = isset($template)?$template:$this->template;
         return parent::showInput($data);

    }

    function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) {
            $value = isset($this->value)?$this->value:'';
        }

        if (!empty($this->icon)) {
            $cssclass=''; //we use the icon specified and not a css class
        } else {
            $icon = isset($icon_url)?$icon_url:$this->xv_icon_url;
            $cssclass = 'xar-noborder';
        }
        $value = xarVarPrepForDisplay($value);
        if (!empty($value)) {

            $link = str_replace('%value%',$value,$this->imlink);
        } else {
            $link = '';
        }
        $title = isset($title)?$title: $this->xv_titletext;

        if (!empty($title)) {
            $data['title'] = str_replace('%value%',$value,$title);
        } else {
           $data['title'] = '';
        }
        $data['link'] = xarVarPrepForDisplay($link);
        $cssclass = isset($cssclass)?$cssclass:$this->cssclass;

        if (!empty($this->xv_icon_url)) {
            $data['value']= $value;
            $data['icon'] = $icon;
            $data['cssclass'] = $cssclass;
            $data['image']= xarVarPrepForDisplay($icon);
        } else {
            $data['icon'] = '';
            $data['image'] = '';
            $data['cssclass'] = '';
        }
        $data['altinfo'] = $this->label;
        $data['template'] = isset($template)?$template:$this->template;

        return parent::showOutput($data);
    }

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
        $args = array();
        $validation = parent::getBaseValidationInfo();
        $validation = serialize($validation);
        // Linked In
        $args['imlink']='http://www.linkedin.com/profile/view?id=%value%';
        $args['xv_titletext'] = xarML('View LinkedIn profile');
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-linkedin.png','base');
        $aliases[]= array(
                              'id'         => 65,
                              'name'       => 'linkedin',
                              'label'      => 'Social - LinkedIn Profile',
                              'format'     => '65',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );
        // Facebook
        $args['imlink']='http://www.facebook.com/profile.php?id=%value%';
        $args['xv_titletext'] = xarML('View Facebook profile');
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-facebook.png','base');
        $aliases[]= array(
                              'id'         => 64,
                              'name'       => 'facebook',
                              'label'      => 'Social - Facebook Profile',
                              'format'     => '64',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );

        // Twitter
        $args['imlink']='http://www.twitter.com/%value%';
        $args['xv_titletext'] = xarML('Follow %value% on Twitter');
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-twitter.png','base');
        $aliases[]= array(
                              'id'         => 63,
                              'name'       => 'twitter',
                              'label'      => 'Social - Twitter profile',
                              'format'     => '63',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );


         //Jabber
        $args['imlink']='xmpp:%value%?message';
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-jabber.png','base');
        $aliases[]= array(
                              'id'         => 62,
                              'name'       => 'jabber',
                              'label'      => 'IM - Jabber username',
                              'format'     => '62',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );




         //Skype
        $args['imlink']='skype:%value%?chat';
         $args['xv_titletext'] = xarML('Skype %value% now');
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-skype.png','base');
        $aliases[]= array(
                              'id'         => 61,
                              'name'       => 'skype',
                              'label'      => 'IM - Skype username',
                              'format'     => '61',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );



         //Affero
        $args['imlink']='http://svcs.affero.net/user-history.php?ll=lq_members&u=%value%';
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-new.png','base');
        $aliases[]= array(
                              'id'         => 40,
                              'name'       => 'affero',
                              'label'      => 'IM - Affero username',
                              'format'     => '40',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );


         //Yahoo
        $args['imlink']='ymsgr:sendIM?%value%}';
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-yahoo.png','base');
        $aliases[]= array(
                              'id'         => 31,
                              'name'       => 'yahoo',
                              'label'      => 'IM - Yahoo messenger',
                              'format'     => '31',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );

         //MSN
        $args['imlink']='msnim:chat?contact=%value%';
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-msnm.png','base');
        $aliases[]= array(
                              'id'         => 30,
                              'name'       => 'msn',
                              'label'      => 'IM - MSN messenger',
                              'format'     => '30',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );

         //ICQ
        $args['imlink']='http://wwp.icq.com/scripts/search.dll?to=%value%';
        $args['xv_icon_url'] =  xarTplGetImage('icons/contact-icq.png','base');
        $aliases[]= array(
                              'id'         => 28,
                              'name'       => 'icq',
                              'label'      => 'IM - ICQ number',
                              'format'     => '28',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'args'           => serialize($args),
                           );
        //AIM
         $args['imlink']='aim:goim?screenname=%value%&message='.xarML('Hello+Are+you+there?');
         $args['xv_icon_url'] =  xarTplGetImage('icons/contact-aim.png','base');
         $baseInfo= array(
                              'id'         => 29,
                              'name'       => 'aim',
                              'label'      => 'IM - AIM address',
                              'format'     => '29',
                              'validation' => $validation,
                              'source'         => '',
                              'filepath'    => 'modules/roles/xarproperties',
                              'dependancies'   => '',
                              'requiresmodule' => 'roles',
                              'aliases'        => $aliases,
                              'args'           => serialize($args),
                           );


        return $baseInfo;
     }
}
?>
