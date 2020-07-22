<?php
/**
 * Dynamic URL Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * Include the base class
 */
sys::import('modules.base.xarproperties.Dynamic_TextBox_Property');
/**
 * handle the URL property
 *
 * @package dynamicdata
 *
 */
class Dynamic_URL_Property extends Dynamic_TextBox_Property
{
    public $id         = 11;
    public $name       = 'url';
    public $desc       = 'URL';
    public $xv_host    = '';
    public $checkvalue = '';

    function __construct($args)
    {
        parent::__construct($args);
        $this->template = 'url';
    }
    /**
     * Validate a value as an URL
     * @param value
     * @return bool true
     */
    function validateValue($value = null)
    {

        // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
        $value = trim($value);
        if (!empty($value) && $value != 'http://')  {
           //let's process futher then
           //check it is not invalid eg html tag
           $lablename = isset($this->label) && !empty($this->label)?$this->label:$this->name;
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('URL: #(1)', $labelname);
               // $this->value = '';
                return false;
            } else {
              // If we have a scheme but nothing following it,
                // then consider the link empty :-)
                if (preg_match('/^[a-z]+\:\/\/$/', $value)) {
                    $this->value = '';
                }elseif (preg_match('/xarModURL\(.*/i', $value)) {
                    //we have a xarModURL link
                      $this->value = $value;
                } else {
                    // Do some URL validation below. Separate for better understanding
                    // Still not perfect. Add as seen fit.
                    $uri = @parse_url($value);

                    if (empty($uri['scheme']) && empty($uri['host']) && empty($uri['path'])) {

                        $this->invalid = xarML('URL');
                        //$this->value = '';
                        return false;
                    } elseif (empty($uri['scheme']) && empty($uri['host']) && !empty($uri['path']) && !empty($value)) {
                        //it could be a local url or perhaps a specific scheme and host might be provided
                        if (isset($this->xv_host))
                        {
                            //strip any leading slashes from value
                            $value = preg_replace('/^\/+/','',$value);
                            $value = $this->xv_host.'/'.$value;
                            $this->value = $value;
                            $this->checkvalue = $value;
                        }
                        //otherwise assume it is local and leave it without scheme and host
                    } elseif (empty($uri['scheme'])) {
                        // No scheme, so add one.
                        //strip any leading slashes from value
                        $value = preg_replace('/^\/+/','',$value);
                        $this->value = 'http://' . $value;
                        $this->checkvalue = $value;
                    } else {
                        //$hasdot = strpos($uri['host'],'.');
                         // it has at least a scheme (http/ftp/etc) and a host (domain.tld)
                         //does it have a valid scheme??
                         $validschemes = array('http','ftp','https','file','sftp');
                          $this->value = $value;
                         $this->checkvalue = $value;
                         if (!in_array($uri['scheme'],$validschemes)) {
                            $this->invalid = xarML('URL has invalid scheme "#(1)". Depending on type of URI type it should be something like http, ftp, https, or file.',$uri['scheme']);
                            return FALSE;
                        }
                        $this->value = $value;
                        $this->checkvalue = $value;

                    }
                }

            } //end checks for other schemes
        } else {
            // Set the empty value of the property.
            $this->value = '';
            $this->checkvalue = '';


        }
        $parenttest = $this->value;
        if (!parent::validateValue($parenttest)) return false;

        return true;
    }

    function showInput(Array $data = array())
    {
        extract($data);
        $value = isset($value)? $value: $this->value;
        if (empty($template)) $template = $this->template;
        $data['template'] = $template;
        if (preg_match('/xarModURL\(.*/i', $value)) {
            eval('$checkvalue =' . $value .';');
        } else {
            $checkvalue = $value;
        }
        $data['checkvalue'] =$checkvalue;
        return parent::showInput($data);
    }

    function showOutput(Array $data = array())
    {
        extract($data);
        $value = isset($value)? $value: $this->value;
        if (empty($template)) {
            $template = $this->template;
        }
        $data['template'] = $template;
        if (preg_match('/xarModURL\(.*/i', $value)) {
            eval('$checkvalue =' . $value .';');
        } else {
            $checkvalue = $value;
        }

        $data['checkvalue'] =$checkvalue;
        $data['target'] = isset($target) && !empty($target)? $target:'';
        $data['class'] = isset($class) && !empty($class)? $class:'';

        return parent::showOutput($data);
    }


 /* This function returns a serialized array of validation options specific for this property
     * The validation options will be combined with global validation options so only specific should be defined here
     * These validation options can be inherited  if necesary
     */
    function getBaseValidationInfo()
    {
        static $validationarray = array();
        if (empty($validationarray)) {

            $parentvalidations = parent::getBaseValidationInfo();
            $validations = array('xv_host' =>  array(  'label'=>xarML('scheme and host'),
                                                                'description'=>xarML('Scheme and host'),
                                                                'propertyname'=>'textbox',
                                                                'propargs' => array(),
                                                                'ignore_empty'  =>1,
                                                                'ctype'=>'definition',
                                                                'configinfo'=>xarML('Optional. Baseurl when different from specified or site baseurl.')
                                                          ),
                                    );
            $validationarray = array_merge($parentvalidations,$validations);
        }
        return $validationarray;
    }
    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
        $validations = parent::getBaseValidationInfo();
         $baseInfo = array(
            'id'         => 11,
            'name'       => 'url',
            'label'      => 'URL',
            'format'     => '11',
            'validation' => serialize($validations),
            'source'     => '',
            'dependancies' => '',
            'filepath'    => 'modules/base/xarproperties',
            'requiresmodule' => 'base',
            'aliases' => '',
            'args'         => '',
            // ...
        );
        return $baseInfo;
     }
}
?>
