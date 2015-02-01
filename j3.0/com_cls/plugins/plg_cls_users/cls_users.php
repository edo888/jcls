<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2008 - 2015 Edvard Ananyan. All rights reserved.
* @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

/**
 * CLS users plugin
 *
 */
class  plgSystemCLS_Users extends JPlugin {

    function __construct( &$subject ) {
        parent::__construct( $subject );
    }
    
    /**
     * Lock the admin bar and redirect the user to the complaints component
     *
     * @access public
     */
    function onAfterDispatch() {
        $app = JFactory::getApplication();
        
        if(!$app->isAdmin())
            return;
            
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        
        if($user_type != 'System Administrator' and !$user->authorise('core.admin') and !$user->guest) {
            if(JRequest::getVar('option') != 'com_cls') {
                JRequest::setVar('option', 'com_cls');
                $app->redirect('index.php?option=com_cls');
            }

            JRequest::setVar('hidemainmenu', 1);
        }
    }
}