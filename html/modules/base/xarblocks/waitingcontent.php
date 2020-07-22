<?php
/**
 * Waiting content block management
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */

/**
 * initialise block
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
function base_waitingcontentblock_init()
{
    // Nothing to configure.
    return array('nocache'     => 0,
                 'pageshared'  => 1,
                 'usershared'  => 1,
                 'cacheexpire' => null);
}

/**
 * get information on block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function base_waitingcontentblock_info()
{
    return array(
        'text_type' => 'Waiting Content',
        'text_type_long' => 'Displays Waiting Content for All Modules',
        'module' => 'base',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display waitingcontent block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  data array on success or void on failure
 * @throws  no exceptions
*/
function base_waitingcontentblock_display($blockinfo)
{

    // Get publication types
    $data = xarMod::apiFunc('base', 'admin', 'waitingcontent');

    $blockinfo['content'] = array(
        'output'   => $data['output'],
        'message'  => $data['message']
    );

    return $blockinfo;
}

?>