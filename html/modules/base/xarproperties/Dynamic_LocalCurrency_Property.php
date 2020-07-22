<?php
/**
 * Local Currency property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
*
 * @subpackage Xarigami Base module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 */

sys::import('modules.base.xarproperties.Dynamic_FloatBox_Property');

class Dynamic_LocalCurrency_Property extends Dynamic_FloatBox_Property
{
    public $id          = 48;
    public $name        = 'localcurrency';
    public $desc        = 'Local Currency';

    // TODO: indicate field alignment = 'right'
    // TODO: the grouping seperator, decimal separator and precision could be

    // The localeMonetary structure will expand to:
    //  currencySymbol
    //  internationalCurrencySymbol
    //  decimalSeparator
    //  isDecimalSeparatorAlwaysShown
    //  groupingSeparator
    //  groupingSize
    //  fractionDigits +
    //      maximum
    //      minimum
    //  integerDigits +
    //      maximum
    //      minimum

    public $localeMonetary = array();

    /**
     * Constructor.
     * Fetch currency informatino from the current locale.
     * FIXME: we should be able to fetch from the *default* locale,
     * and not be limited by the locale the user happens to have chosen,
     * perhaps to select their language.
     */
    function __construct($args)
    {
        parent::__construct($args);

        $localeData = xarMLSLoadLocaleData();
        $data = $this->_locale2array($localeData);

        $this->localeMonetary = $data['monetary'];

        if (!isset($this->xv_precision) && isset($this->localeMonetary['fractionDigits']['maximum']) && is_numeric($this->localeMonetary['fractionDigits']['maximum'])) {
            $this->xv_precision = (int)$this->localeMonetary['fractionDigits']['maximum'];
        }

        // Set some defaults from the locale.
        // These could be set (overridden) by a descendant class if required.
        if (!isset($this->grouping_sep)) {
            $this->grouping_sep = (isset($this->localeMonetary['groupingSeparator']) ? $this->localeMonetary['groupingSeparator'] : '');
        }

        if (!isset($this->decimal_sep)) {
            $this->decimal_sep = (isset($this->localeMonetary['decimalSeparator']) ? $this->localeMonetary['decimalSeparator'] : '.');
        }

        if (!isset($this->xv_number_prefix)) {
            $this->xv_number_prefix = (isset($this->localeMonetary['currencySymbol']) ? $this->localeMonetary['currencySymbol'] : '?');
        }

        if (!isset($this->always_show_decimal)) {
            $this->always_show_decimal = (!empty($this->localeMonetary['isDecimalSeparatorAlwaysShown']) ? true : false);
        }
    }
      /**
     * Show the input form
     */
    function showInput(Array $data = array())
    {
        $this->value = $this->_format_number($this->value);
        $data['template'] = isset($data['template'])?$data['template']:$this->template;
        return parent::showInput($data);
    }


    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
    function getBasePropertyInfo()
    {
        $args = array();
        $validations = parent::getBaseValidationInfo();
        $baseInfo = array(
            'id'            => 48,
            'name'          => 'localcurrency',
            'label'         => 'Local currency',
            'format'        => '48',
            'validation'    => serialize($validations),
            'source'        => '',
            'dependancies'  => '',
            'filepath'    => 'modules/base/xarproperties',
            'requiresmodule' => 'base',
            'aliases'       => '',
            'args'          => serialize($args),
        );

        return $baseInfo;
    }
}

?>