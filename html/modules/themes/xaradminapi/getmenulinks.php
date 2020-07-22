<?php
/**
 * Pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Themes
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/projects/xarigami_core
 * @author Xarigami Team
 */
/**
* utility function pass individual menu items to the main menu
*
* @author Marty Vance
* @return array containing the menulinks for the main menu items.
*/
function themes_adminapi_getmenulinks()
{
    $menulinks = array();
    // Security Check
    if (xarSecurityCheck('AddTheme',0)) {

    $menulinks[] = array('url'   => xarModURL('themes', 'admin', 'list'),
                        'title' => xarML('Managed installed themes on the system'),
                        'label' => xarML('Manage Themes'),
                        'active' => array('list','themesinfo'),
                        'activelabels' => array('',xarML('Theme information'),xarML('Configure theme')
                                            )
                        );
    }
    if (xarSecurityCheck('EditTheme',0)) {
        $menulinks[] = array('url'   => xarModURL('themes', 'admin', 'themewizard'),
                        'title' => xarML('Configure themes'),
                        'label' => xarML('Configure themes'),
                        'active' => array('themewizard','config','del','exportvars'),
                         'activelabels' => array('',xarML('Configure variable'),xarML('Delete variable'),xarML('Export variables'))
                        );
    }
    if (xarSecurityCheck('AdminTheme',0)) {
        $menulinks[] = array('url'   => xarModURL('themes', 'admin', 'listtpltags'),
                             'title' => xarML('View the registered template tags.'),
                             'label' => xarML('List Template Tags'),
                            'active' => array('listtpltags','modifytpltag','removetpltag'),
                            'activelabels' => array('',xarML('Modify template tag'),xarML('Remove template tag')
                                                )
                             );

        // css configurations, viewer and editor (AndyV - corecss scenario)
        // lets make these links only available when css class lib is loaded
        //if(class_exists("xarCss")){
        //    $menulinks[] = array(   'url'   => xarModURL('themes', 'admin', 'cssconfig'),
        //   'title' => xarML('View and configure Xarigami Cascading Style Sheets'),
        //    'label' => xarML('Manage CSS'));
        //}

        $menulinks[] = array('url'   => xarModURL('themes', 'admin', 'modifyconfig'),
                             'title' => xarML('Modify the configuration of the themes module'),
                             'label' => xarML('Modify Config'),
                             'active' => array('modifyconfig','clearuservars'),
                             'activelabels' => array('',xarML('Clear user vars'))
                             );
    }

    return $menulinks;
}

?>