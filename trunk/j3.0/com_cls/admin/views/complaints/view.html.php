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

class ClsViewcomplaints extends JViewLegacy {

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
            $app->redirect('index.php?option=com_cls&view=reports');
            return;
        }

        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');
        $this->state        = $this->get('State');

        $category_options   = $this->get('category_options');
        $contracts_options  = $this->get('Contracts_options');

        //area_id filter
        $options        = array();
        foreach($category_options AS $category) {
            $options[]      = JHtml::_('select.option', $category->id, $category->area);
        }
        JHtmlSidebar::addFilter(
                JText::_('- Select Category -'),
                'filter_area_id',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.area_id'))
        );

        //contract_id filter
        $options        = array();
        foreach($contracts_options AS $contracts) {
            $options[]      = JHtml::_('select.option', $contracts->id, $contracts->name);
        }
        JHtmlSidebar::addFilter(
                JText::_('- Select Contract -'),
                'filter_contract_id',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.contract_id'))
        );

        //source filter
        $source_array = array(array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Email', 'value' => 'Email'), array('key' => 'Website', 'value' => 'Website'), array('key' => 'Telephone Call', 'value' => 'Telephone Call'), array('key' => 'Personal Visit', 'value' => 'Personal Visit'), array('key' => 'Field Visit by Project Staff', 'value' => 'Field Visit by Project Staff'), array('key' => 'Other', 'value' => 'Other'));
        $options        = array();
        foreach($source_array AS $source_item) {
            $options[]      = JHtml::_('select.option', $source_item['key'], $source_item['value']);
        }
        JHtmlSidebar::addFilter(
                JText::_('- Select Source -'),
                'filter_source',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.source'))
        );

        //priority filter
        $priority_array = array(array('key' => 'Low', 'value' => 'Low'), array('key' => 'Medium', 'value' => 'Medium'), array('key' => 'High', 'value' => 'High'));
        $options        = array();
        foreach($priority_array AS $priority_item) {
            $options[]      = JHtml::_('select.option', $priority_item['key'], $priority_item['value']);
        }
        JHtmlSidebar::addFilter(
                JText::_('- Select Priority -'),
                'filter_priority',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.priority'))
        );

        //status filter
        $status_array = array(array('key' => 'N', 'value' => 'Open'), array('key' => 'Y', 'value' => 'Resolved'));
        $options        = array();
        foreach($status_array AS $status_item) {
            $options[]      = JHtml::_('select.option', $status_item['key'], $status_item['value']);
        }

        JHtmlSidebar::addFilter(
                JText::_('- Select Status -'),
                'filter_status',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.status'))
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

        if($mainframe->isAdmin()) {
            if($user_type == 'System Administrator' or $user_type == 'Level 1')
                JToolBarHelper::addNew('complaint.add');
            JToolBarHelper::editList('complaint.edit');
            if($user_type == 'System Administrator')
                JToolBarHelper::deleteList('','complaint.remove');
        }

        if($user_type == 'System Administrator' and $mainframe->isAdmin())
            JToolBarHelper::preferences('com_cls', '550', '570', 'Settings');
        JToolBarHelper::help('screen.cls', true);
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
            'm.message_id' => JText::_('Message ID'),
            'm.message_source' => JText::_('Source'),
            'sender' => JText::_('Sender'),
            'm.date_received' => JText::_('Received'),
            'g.area' => JText::_('Category'),
            'm.message_priority' => JText::_('Priority'),
            'm.date_processed' => JText::_('Processed'),
            'e.name' => JText::_('Processed by'),
            'm.date_resolved' => JText::_('Resolved'),
            'u.name' => JText::_('Resolved by'),
            'm.id' => JText::_('id')
        );
    }
}