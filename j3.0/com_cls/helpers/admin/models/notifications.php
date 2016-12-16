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
jimport('joomla.application.component.modellist');

class ClsModelNotifications extends JModelList {

    /**
     * Constructor.
     *
     * @param   array   An optional associative array of configuration settings.
     * @see     JController
     * @since   1.6
     */
    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'u.name',
                'm.action',
                'm.date',
                'm.end_date',
                'm.id'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to get users
     *
     */
    public function getUsers_options() {
        $db     = $this->getDbo();
        $query = 'select distinct n.user_id, u.name from #__complaint_notifications as n left join #__users as u on (n.user_id = u.id)';
        $db->setQuery($query);
        return $users = $db->loadObjectList();
    }

    /**
     * Method to get actions
     *
     */
    public function getActions_options() {
        $db     = $this->getDbo();
        $query = 'select distinct action from #__complaint_notifications';
        $db->setQuery($query);
        return $actions = $db->loadObjectList();
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return  void
     * @since   1.6
     */
    protected function populateState($ordering = null, $direction = null) {
        // Initialise variables.
        $app = JFactory::getApplication();

        // Adjust the context to support modal layouts.
        if ($layout = JRequest::getVar('layout')) {
            $this->context .= '.'.$layout;
        }

        $search = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $user_id = $this->getUserStateFromRequest($this->context.'.filter.user_id', 'filter_user_id');
        $this->setState('filter.user_id', $user_id);

        $action = $this->getUserStateFromRequest($this->context.'.filter.action', 'filter_action');
        $this->setState('filter.action', $action);

        // List state information.
        parent::populateState('m.id', 'desc');
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string      $id A prefix for the store id.
     *
     * @return  string      A store id.
     * @since   1.6
     */
    protected function getStoreId($id = '') {
        // Compile the store id.
        $id .= ':'.$this->getState('filter.search');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  JDatabaseQuery
     * @since   1.6
     */
    protected function getListQuery() {
        // Create a new query object.
        $db     = $this->getDbo();
        $query  = $db->getQuery(true);
        $user   = JFactory::getUser();

        // Select the required fields from the table.
        $query->select(
                $this->getState(
                        'list.select',
                        'm.*'
                )
        );

        $query->from('#__complaint_notifications AS m');

        // Join
        $query->select('u.name as user');
        $query->join('LEFT', '#__users AS u ON m.user_id = u.id');

        // Filter
        $user_id = $this->getState('filter.user_id');
        if ($user_id != '') {
            $query->where('m.user_id  = '.(string) $user_id);
        }

        // Filter
        $action = $this->getState('filter.action');
        if ($action != '') {
            $query->where('m.action  = \''.(string) $action.'\'');
        }

        // Filter by search in name.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->Quote('%'.$db->escape($search, true).'%');
            $query->where('(m.description LIKE '.$search.')');
        }

        // Add the list ordering clause.
        $orderCol   = $this->state->get('list.ordering', 'm.id');
        $orderDirn  = $this->state->get('list.direction', 'asc');
        /*
            if ($orderCol == 'a.ordering' || $orderCol == 'category_title') {
        $orderCol = 'c.title '.$orderDirn.', a.ordering';
        }
        */
        $query->order($db->escape($orderCol.' '.$orderDirn));
        //$query->group('sa.id');

        return $query;
    }

}
