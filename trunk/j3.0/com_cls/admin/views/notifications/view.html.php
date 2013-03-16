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

class ClsViewNotifications extends JViewLegacy {

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
            $app->redirect('index.php?option=com_cls&view=reports');
            return;
        }

        $this->items        = $this->get('Items');
        $this->pagination   = $this->get('Pagination');
        $this->state        = $this->get('State');

        $users_options  = $this->get('users_options');
        $options        = array();
        foreach($users_options AS $user) {
            $user->name = $user->user_id == 0 ? 'System' : $user->name;
            $options[]      = JHtml::_('select.option', $user->user_id, $user->name);
        }

        JHtmlSidebar::addFilter(
                JText::_('- Select User -'),
                'filter_user_id',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.user_id'))
        );

        $actions_options    = $this->get('actions_options');
        $options        = array();
        foreach($actions_options AS $action) {
            $options[]      = JHtml::_('select.option', $action->action, $action->action);
        }
        JHtmlSidebar::addFilter(
                JText::_('- Select Action -'),
                'filter_action',
                JHtml::_('select.options', $options, 'value', 'text', $this->state->get('filter.action'))
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
        JToolBarHelper::preferences('com_cls', '550', '570', 'Settings');
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
            'u.name' => JText::_('User'),
            'm.action' => JText::_('Action'),
            'm.date' => JText::_('Date'),
            'm.end_date' => JText::_('End Date'),
            'm.id' => JText::_('id')
        );
    }

}
