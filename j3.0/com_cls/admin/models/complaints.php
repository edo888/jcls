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
                    'current_status',
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
        $db = $this->getDbo();
        $sql = "select * from #__complaint_areas";
        $db->setQuery($sql);
        return $opts = $db->loadObjectList();
    }

    /**
     * Method to get support groups
     *
     */
    public function getSupportGroup_options() {
        $db = $this->getDbo();
        $sql = "select * from #__complaint_support_groups";
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

        $support_group_id = $this->getUserStateFromRequest($this->context.'.filter.support_group_id', 'filter_support_group_id');
        $this->setState('filter.support_group_id', $support_group_id);

        $contract_id = $this->getUserStateFromRequest($this->context.'.filter.contract_id', 'filter_contract_id');
        $this->setState('filter.contract_id', $contract_id);

        $source = $this->getUserStateFromRequest($this->context.'.filter.source', 'filter_source');
        $this->setState('filter.source', $source);

        $priority = $this->getUserStateFromRequest($this->context.'.filter.priority', 'filter_priority');
        $this->setState('filter.priority', $priority);

        $status = $this->getUserStateFromRequest($this->context.'.filter.status', 'filter_status');
        $this->setState('filter.status', $status);

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
        $id .= ':'.$this->getState('filter.area_id');
        $id .= ':'.$this->getState('filter.support_group_id');
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
        $user_type = $user->getParam('role', 'Guest');

        // Select the required fields from the table.
        $query->select(
                $this->getState(
                        'list.select',
                        'm.*, concat(m.name, " ", m.email, " ", m.phone, " ", m.ip_address) as sender, IF(m.date_processed is null, "Open", IF(m.confirmed_closed = "Y", "Closed", IF(m.confirmed_closed = "N" and m.date_resolved is null and m.date_processed is not null, "Processed", IF(confirmed_closed = "N" and date_resolved is not null and date_processed is not null, "Resolved", "Unknown")))) as current_status'
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
        } elseif (is_array($area_id)) {
            JArrayHelper::toInteger($area_id);
            $area_id = implode(',', $area_id);
            $query->where('m.complaint_area_id IN ('.$area_id.')');
        }

        // Filter
        $support_group_id = $this->getState('filter.support_group_id');
        if (is_numeric($support_group_id)) {
            $query->where('m.support_group_id = '.(int) $support_group_id);
        } elseif (is_array($support_group_id)) {
            JArrayHelper::toInteger($support_group_id);
            $area_id = implode(',', $support_group_id);
            $query->where('m.support_group_id IN ('.$support_group_id.')');
        }

        // Filter
        $contract_id = $this->getState('filter.contract_id');
        if (is_numeric($contract_id)) {
            $query->where('m.contract_id = '.(int) $contract_id);
        } elseif (is_array($contract_id)) {
            JArrayHelper::toInteger($contract_id);
            $contract_id = implode(',', $contract_id);
            $query->where('m.contract_id IN ('.$contract_id.')');
        }

        // Filter
        $source = $this->getState('filter.source');
        if($source != '')
            $query->where('m.message_source = "' . $source . '"');

        // Filter
        $priority = $this->getState('filter.priority');
        if($priority != '')
            $query->where('m.message_priority = "' . $priority . '"');

        // Filter
        $status = $this->getState('filter.status');
        if($status != '') {
            switch($status) {
                case 'Open': $query->where('date_processed is null'); break;
                case 'Closed': $query->where('confirmed_closed = "Y"'); break;
                case 'Processed': $query->where('confirmed_closed = "N" and date_resolved is null and date_processed is not null'); break;
                case 'Resolved': $query->where('confirmed_closed = "N" and date_resolved is not null and date_processed is not null'); break;
                default: break;
            }
        }

        // for level 2 users show only complaints assigned to them
        if($user_type == 'Level 2') {
            $db->setQuery('select group_id from #__complaint_support_groups_users_map where user_id = ' . $user->get('id'));
            $support_groups = $db->loadObjectList();
            $support_group_ids = array();
            foreach($support_groups as $support_group)
                $support_group_ids[] = $support_group->group_id;
            $support_group_ids = implode(',', $support_group_ids);

            if($support_group_ids != '')
                $query->where('date_processed is not null and m.support_group_id in (' . $support_group_ids . ')');
            else
                $query->where('false');
        }

        // Filter by search in name.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = str_replace("'", "", $search);
            $search = $db->Quote('%'.$db->escape($search, true).'%');
            $query->where('(replace(m.name, "\'", "") LIKE '.$search.' OR m.email LIKE '.$search.' OR m.address LIKE '.$search.' OR m.phone LIKE '.$search.' OR message_id LIKE '.$search.' OR raw_message LIKE '.$search.' OR processed_message LIKE '.$search.' OR resolution LIKE '.$search.')');
        }

        // Add the list ordering clause.
        $orderCol   = $this->state->get('list.ordering', 'm.message_id');
        $orderDirn  = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol.' '.$orderDirn));

        return $query;
    }

}
