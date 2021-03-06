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

class ClsViewContracts extends JViewLegacy {

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
         if($user_type == 'Guest' or $user_type == 'Level 2') {
            $app = JFactory::getApplication();
            $app->redirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission to view this page"));
            return;
        }

        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');
        $this->state        = $this->get('State');

        $section_options    = $this->get('Section_options');
        $options        = array();
        foreach($section_options AS $section) {
            $options[]      = JHtml::_('select.option', $section->id, $section->name);
        }
        JHtmlSidebar::addFilter(
                JText::_('- Select Location -'),
                'filter_section_id',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.section_id'))
        );

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
            JToolBarHelper::addNew('contract.add');
        JToolBarHelper::editList('contract.edit');
        if($user_type == 'System Administrator')
            JToolBarHelper::deleteList('', 'contract.remove');

        if($user_type == 'System Administrator' and $mainframe->isAdmin())
            JToolBarHelper::preferences('com_cls', '550', '570', 'JOptions');

        JToolBarHelper::help('screen.cls.contracts', true);
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
                'm.name' => JText::_('Contract Name'),
                'm.contract_id' => JText::_('Contract Id'),
                'm.start_date' => JText::_('Start Date'),
                'm.end_date' => JText::_('End Date'),
                's.name' => JText::_('Location'),
                'complaints_count' => JText::_('Complaints Count'),
                'm.id' => JText::_('id')
        );
    }

}
