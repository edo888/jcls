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
jimport( 'joomla.application.component.view');


class ClsViewAreas extends JViewLegacy {

    protected $items;
    protected $pagination;
    protected $state;

    /**
     * Display the view
     *
     * @return  void
     */
    public function display($tpl = null) {

        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $app = JFactory::getApplication();
            $app->redirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission to view this page"));
            return;
        }

        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');
        $this->state        = $this->get('State');

        $this->addToolbar();
        $this->sidebar = JHtmlSidebar::render();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @since   1.6
     */
    protected function addToolbar() {
        $mainframe = JFactory::getApplication();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        if($user_type == 'System Administrator' or $user_type == 'Level 1')
            JToolBarHelper::addNew('area.add');
        JToolBarHelper::editList('area.edit');
        if($user_type == 'System Administrator')
            JToolBarHelper::deleteList('', 'area.remove');

        if($user_type == 'System Administrator' and $mainframe->isAdmin())
            JToolBarHelper::preferences('com_cls', '550', '570', 'JOptions');

        JToolBarHelper::help('screen.cls.areas', true);
        JToolBarHelper::divider();
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     *
     * @since   3.0
     */
    protected function getSortFields() {
        return array(
                'm.area' => JText::_('Category Name'),
                'm.description' => JText::_('Description'),
                'm.id' => JText::_('id')
        );
    }

}
