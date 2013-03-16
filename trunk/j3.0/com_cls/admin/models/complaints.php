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

class ClsModelComplaints extends JModelList {

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
                    'm.id',
                    'm.message_id',
                    'm.message_source',
                    'm.date_received',
                    'g.area',
                    'm.message_priority',
                    'm.date_processed',
                    'm.date_resolved',
                    'sender',
                    'e.name',
                    'u.name'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to get category
     *
     */
    public function getCategory_options() {
        $db     = $this->getDbo();
        $sql = "select * from #__complaint_areas";
        $db->setQuery($sql);
        return $opts = $db->loadObjectList();
    }

    /**
     * Method to contract_id
     *
     */
    public function getContracts_options() {
        $db     = $this->getDbo();
        $sql = "select * from #__complaint_contracts";
        $db->setQuery($sql);
        return $opts = $db->loadObjectList();
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

        $area_id = $this->getUserStateFromRequest($this->context.'.filter.area_id', 'filter_area_id');
        $this->setState('filter.area_id', $area_id);

        $contract_id = $this->getUserStateFromRequest($this->context.'.filter.contract_id', 'filter_contract_id');
        $this->setState('filter.contract_id', $contract_id);

        $source = $this->getUserStateFromRequest($this->context.'.filter.source', 'filter_source');
        $this->setState('filter.source', $source);

        $priority = $this->getUserStateFromRequest($this->context.'.filter.priority', 'filter_priority');
        $this->setState('filter.priority', $priority);

        $status = $this->getUserStateFromRequest($this->context.'.filter.status', 'filter_status');
        $this->setState('filter.status', $status);

        // List state information.
        parent::populateState('m.message_id', 'asc');
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
        $id .= ':'.$this->getState('filter.area_id');
        $id .= ':'.$this->getState('filter.contract_id');
        $id .= ':'.$this->getState('filter.source');
        $id .= ':'.$this->getState('filter.priority');
        $id .= ':'.$this->getState('filter.status');

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
                        'm.*, concat(m.name, " ", m.email, " ", m.phone, " ", m.ip_address) as sender'
                )
        );

        $query->from('#__complaints AS m');

        // Join
        $query->select('g.area as area');
        $query->join('LEFT', '#__complaint_areas AS g ON m.complaint_area_id = g.id');

        // Join
        $query->select('u.name as resolver');
        $query->join('LEFT', '#__users AS u ON m.resolver_id = u.id');

        // Join
        $query->select('e.name as editor');
        $query->join('LEFT', '#__users AS e ON m.editor_id = e.id');


        // Filter
        $area_id = $this->getState('filter.area_id');
        if (is_numeric($area_id)) {
            $query->where('m.complaint_area_id = '.(int) $area_id);
        }
        elseif (is_array($area_id)) {
            JArrayHelper::toInteger($area_id);
            $area_id = implode(',', $area_id);
            $query->where('m.complaint_area_id IN ('.$area_id.')');
        }
        // Filter
        $contract_id = $this->getState('filter.contract_id');
        if (is_numeric($contract_id)) {
            $query->where('m.contract_id = '.(int) $contract_id);
        }
        elseif (is_array($contract_id)) {
            JArrayHelper::toInteger($contract_id);
            $contract_id = implode(',', $contract_id);
            $query->where('m.contract_id IN ('.$contract_id.')');
        }
        // Filter
        $source = $this->getState('filter.source');
        if($source != '')
            $query->where('m.message_source = '. $source);
        // Filter
        $status = $this->getState('filter.status');
        if($status != '')
            $query->where('confirmed_closed = '. $status);

        // Filter by search in name.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->Quote('%'.$db->escape($search, true).'%');
            $query->where('(message_id LIKE '.$search.' OR raw_message LIKE '.$search.' OR processed_message LIKE '.$search.' OR resolution LIKE '.$search.')');
        }

        // Add the list ordering clause.
        $orderCol   = $this->state->get('list.ordering', 'm.message_id');
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
