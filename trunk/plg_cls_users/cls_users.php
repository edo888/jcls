<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2008 - 2010 Edvard Ananyan. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * CLS users plugin
 *
 */
class  plgSystemCLS_Users extends JPlugin {
    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @access protected
     * @param  object $subject The object to observe
     * @param  array  $config  An array that holds the plugin configuration
     * @since  1.0
     */
    function plgSystemCLS_Users(& $subject, $config) {
        // check to see if we are on backend to execute plugin
        global $mainframe;
        if(!$mainframe->isAdmin())
            return;

        // check to see if the user is admin
        $user = JFactory::getUser();
        if(!$user->authorize('com_banners', 'manage'))
            return;

        parent::__construct($subject, $config);
    }

    /**
     * Lock the admin bar and redirect the user to the complaints component
     *
     * @access public
     */
    function onAfterDispatch() {
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        if($user_type != 'System Administrator') {
            if(JRequest::getVar('option') != 'com_cls') {
                JRequest::setVar('option', 'com_cls');

                global $mainframe;
                $mainframe->redirect('index.php?option=com_cls');
            }

            JRequest::setVar('hidemainmenu', 1);
        }
    }
}