<?php
/**
 * Handle E-mail property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Roles
 * @copyright (C) 2009-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_core
 */
/*
 * Handle E-mail property
 * @package modules
 * @subpackage Roles module
 * @author mikespub <mikespub@xaraya.com>
*/

/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');

class Dynamic_Email_Property extends Dynamic_TextBox_Property
{
    public $id         = 26;
    public $name       = 'email';
    public $desc       = 'E-Mail';
    public $reqmodules = 'roles';
    public $xv_useimage   = false;
    public $xv_linktext   = '';
    public $xv_obfuscate  = false;
    public $xv_obmethod  = 0;
    public $xv_confirm    = false;
    public $xv_link        = '';

    /*
    * Options available for email property
    * obfuscate: 0 or none - no encoding, 1 or encoded  - obfuscates the email
    * linktext : a text string used as the text link in an email link,
    *       defaults to the email address if none given, and is slightly obfuscated if the email address is used
    * useimage: boolean, if true an image icon is used in the link along with the text
    */

  function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'roles';
        $this->template = 'email';
        $this->filepath   = 'modules/roles/xarproperties';
    }

   public function validateValue($value = null)
    {
        $isvalid = true;
        if (!isset($value)) {
            $value = $this->value;
        }
        if ($this->xv_confirm) {
            if (is_array($value) && $value[0] == $value[1]) {
                $value = $value[0];
            } else {
                if (!empty($this->xv_confirm_invalid)) {
                    $this->invalid = xarML($this->xv_confirm_invalid);
                } else {
                    $this->invalid = xarML('emails: did not match');
                }
                 return false;
            }
        }
        if (!parent::validateValue($value)) return false;

         if (!empty($value)) {
            $regexp = '/^(?:[^\s\000-\037\177\(\)<>@,;:\\"\[\]]\.?)+@(?:[^\s\000-\037\177\(\)<>@,;:\\\"\[\]]\.?)+\.[a-z]{2,6}$/Ui';
            if (!preg_match($regexp,$value)) {
                if (!empty($this->xv_email_invalid)) {
                    $this->invalid = xarML($this->xv_email_invalid);
                } else {
                    $this->invalid = xarML('email: format is incorrect');
                }
                $this->value = $value;
                $isvalid = false;
            }
        } else {
            $this->value = '';
             $isvalid = true;
        }
        return $isvalid;
    }

    public function showInput(Array $data = array())
    {
        extract($data);
        $data['confirm'] = !isset($confirm) ? $this->xv_confirm : $confirm;
        if (empty($template)) $template = 'email';
        if (isset($value) && is_array($value) ) $data['value'] = $value[0];
        $data['template'] = $template;
        return parent::showInput($data);

    }

    public function showOutput(Array $data = array())
    {
        extract($data);
        if (!isset($value)) {
            $value =$this->value;
        }

        $value= (html_entity_decode($value)); //incase we have entities for @

        if (!isset($obfuscate)) {
            $obfuscate =$this->xv_obfuscate;
        }

        if (!isset($useimage)) {
            $useimage =$this->xv_useimage;
        }
        if (!isset($linktext)|| empty($linktext)) {
            $linktext =$this->xv_linktext;
        }
         if (!isset($obmethod)) {
            $obmethod =$this->xv_obmethod;
        }
        //$imagevalue = isset($image)?$image:'';

        if (isset($obfuscate) && (($obfuscate =='encoded') || $obfuscate == '1')) {

            $encoded = xarMod::apiFunc('mail','user','obfuemail',
                array('email'=>$value,'text'=>$linktext,'image'=>$useimage,'obmethod'=>$obmethod));

            $value = isset($encoded['encoded'])?$encoded['encoded']:'';
            $text = isset($encoded['text'])?$encoded['text']: '';
            $link = $encoded['link'];
        } else {
            $link = '';
            $text = $value;
            $format = '';
        }

        $data['value']= $value;
        $data['obfuscate']= isset($obfuscate)?$obfuscate:$this->xv_obfuscate;
        $data['link']   = isset($link)?$link:$this->xv_link;
        $data['linktext'] = $text;
        $data['useimage']= $useimage;
        $data['value'] = $value;
        $template = !isset($template) || empty($template)? 'email':$template;
        $data['template'] = $template;

        return parent::showOutput($data);
    }

    /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    public function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

            $parentvalidation = parent::getBaseValidationInfo();
            $validations = array('xv_linktext'    =>  array('label'=>xarML('Email link text'),
                                                          'description'=>xarML('Text displayed in email link'),
                                                          'propertyname'=>'textbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'display'

                                                          ),
                                    'xv_useimage'    =>  array('label'=>xarML('Use mail icon?'),
                                                          'description'=>xarML('Use mail icon instead of text link?'),
                                                          'propertyname'=>'checkbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'display'
                                                           ),
                                    'xv_obfuscate'    =>  array('label'=>xarML('Obfuscate email?'),
                                                          'description'=>xarML('Obfuscate the email in source code'),
                                                          'propertyname'=>'checkbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
                                    'xv_obmethod'    =>  array('label'=>xarML('Use Rot13 and JS?'),
                                                          'description'=>xarML('Use Rot13 and JS method instead of default obfuscation'),
                                                          'propertyname'=>'checkbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'definition'
                                                           ),
                                   'xv_confirm'    =>  array('label'=>xarML('Confirm email?'),
                                                          'description'=>xarML('Display a confirmation email input field'),
                                                          'propertyname'=>'checkbox',
                                                          'ignore_empty'  =>1,
                                                          'ctype'=>'validation'
                                                           ),
                                         );

            $validationarray = array_merge($validations,$parentvalidation);
        }
        return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     public function getBasePropertyInfo()
     {
        $args = array();

        $validation = $this->getBaseValidationInfo();

         $baseInfo = array(
                              'id'         => 26,
                              'name'       => 'email',
                              'label'      => 'E-Mail',
                              'format'     => '26',
                              'validation' => serialize($validation),
                              'source'         => '',
                              'dependancies'   => '',
                               'filepath'    => 'modules/roles/xarproperties',
                              'requiresmodule' => 'roles',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
}

?>
