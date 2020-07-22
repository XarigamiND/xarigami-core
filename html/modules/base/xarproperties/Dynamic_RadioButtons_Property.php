<?php
/**
 * Radio Buttons property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */
/**
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import ('modules.base.xarproperties.Dynamic_Select_Property');

/**
 * handle radio buttons property
 */
class Dynamic_RadioButtons_Property extends Dynamic_Select_Property
{
    public $id         = 34;
    public $name       = 'radio';
    public $desc       = 'Radio Buttons';

    function __construct($args)
    {
      parent::__construct($args);
      $this->tplmodule = 'base';
      $this->template  = 'radio';
    }

    //<jojo> - we need a special case here
    // if the neither radio button is checked, we should treat it as 'empty' in this special case
    //while not strictly true from a usability pov it is what would be expected for 'allow empty'
     public function checkInput($name = '', $value = null)
    {
        //store the fieldname in case it is required by other methods/functions (e.g. file uploads)
        $name = empty($name) ? 'dd_'.$this->id : $name;
          $this->fieldname = $name;
        $namelabel = isset($this->label)?$this->label:$this->name;
        $this->invalid = '';
         if (!isset($value)) {
             if (isset($this->label) && !empty($this->label)) {
                $namelabel = $this->label;
            } else {
                $namelabel = $this->name;
            }
            list($found,$value) = $this->fetchValue($name);
            if (!$found && $this->xv_allowempty !=1) {
                $this->invalid = xarML("You must select an option for '#(1)'", $namelabel);
                $this->objectref->missingfields[] = $namelabel;//$this->name;
                return null;
            }
        }
        return $this->validateValue($value);
    }
    function showInput(Array $data = array())
    {
        extract($data);
        $data['checked'] = isset($checked)?$checked: 0;
        if (!empty($checked)) $data['value'] = $checked;

        $data['template'] = empty($template) ? $this->template : $template;

         return parent::showInput($data);
    }

    /**
     * Get the base information for this property.
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $validation = parent::getBaseValidationInfo();
         $baseInfo = array(
                              'id'         => 34,
                              'name'       => 'radio',
                              'label'      => 'Radio buttons',
                              'format'     => '34',
                              'validation' => serialize($validation),
                              'source'     => '',
                              'dependancies' => '',
                              'filepath'    => 'modules/base/xarproperties',
                              'requiresmodule' => 'base',
                              'aliases'        => '',
                              'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>
