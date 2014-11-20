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

class ClsViewReports extends JViewLegacy {

    protected $form;
    protected $item;
    protected $state;

    /**
     * Display the view
     */
    public function display($tpl = null) {
        // Initialiase variables.

        $this->addToolbar();
        $this->sidebar = JHtmlSidebar::render();
        parent::display($tpl);
    }

    protected function addToolbar() {
        JRequest::setVar('hidemainmenu', true);

        JToolBarHelper::cancel('reports.cancel','JTOOLBAR_CLOSE');
    }

}
