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

class ClsModelSupportGroups extends JModelList {

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
                    'm.name',
                    'm.description',
                    'complaints_count'
            );
        }

        parent::__construct($config);
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

        // List state information.
        parent::populateState('m.id', 'asc');
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

        $query->from('#__complaint_support_groups AS m');

        // Join
        $query->select('IFNULL(tbl3.cnt, 0) as complaints_count');
        $query->join('LEFT', '(select support_group_id, count(*) as cnt from jos_complaints group by support_group_id) as tbl3 ON (m.id = tbl3.support_group_id)');

        // Filter by search in name.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->Quote('%'.$db->escape($search, true).'%');
            $query->where('(name LIKE '.$search.' OR description LIKE '.$search.')');
        }

        // Add the list ordering clause.
        $orderCol   = $this->state->get('list.ordering', 'm.id');
        $orderDirn  = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol.' '.$orderDirn));

        return $query;
    }

}
