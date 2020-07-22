<?php
/**
 * Call the waiting content hook
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Authsystem module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
 * call the waiting content hook
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  array with the output and a possible message
 * @throws  NO_PERMISSION exception
 * @todo    nothing
*/
function base_adminapi_waitingcontent()
{
    // Hooks (we specify that we want the ones for adminpanels here)
    $output = array();
    $output = xarMod::callHooks('item', 'waitingcontent', '', array('module' => 'base'));

    if (!isset($message)) $message = '';
    if (empty($output)) {
        $message = xarML('Waiting Content has not been configured');
    }

    return array('output'   => $output,
                 'message'  => $message);
}

?>