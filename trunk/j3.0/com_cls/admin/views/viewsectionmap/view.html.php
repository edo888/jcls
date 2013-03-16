<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

// Import Joomla! libraries
jimport('joomla.application.component.view');

class ClsViewViewSectionMap extends JViewLegacy {

    protected $form;
    protected $item;
    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        // Initialiase variables.
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
         if($user_type == 'Guest' or $user_type == 'Supervisor' or $user_type == 'Level 2') {
            $app = JFactory::getApplication();
            $app->redirect('index.php?option=com_cls&view=reports');
            return;
        }

        parent::display($tpl);
    }
}
