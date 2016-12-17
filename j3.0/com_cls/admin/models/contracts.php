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

class ClsModelContracts extends JModelList {

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
                    'm.contract_id',
                    'm.start_date',
                    'm.end_date',
                    's.name',
                    'complaints_count'
            );
        }

        parent::__construct($config);
    }

    /**
     * Method to get sections
     *
     */
    public function getSection_options() {
        $db     = $this->getDbo();
        $sql = "select * from #__complaint_sections";
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

        $section_id = $this->getUserStateFromRequest($this->context.'.filter.section_id', 'filter_section_id');
        $this->setState('filter.section_id', $section_id);

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

        $query->from('#__complaint_contracts AS m');

        // Join
        $query->select('IFNULL(tbl3.cnt, 0) as complaints_count');
        $query->join('LEFT', '(select contract_id, count(*) as cnt from #__complaints group by contract_id) as tbl3 ON (m.id = tbl3.contract_id)');

        // Join
        $query->select('s.name as section_name');
        $query->join('LEFT', '#__complaint_sections AS s ON m.section_id = s.id');

        // Filter
        $section_id = $this->getState('filter.section_id');
        if (is_numeric($section_id)) {
            $query->where('m.section_id = '.(int) $section_id);
        }

        // Filter by search in name.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            $search = $db->Quote('%'.$db->escape($search, true).'%');
            $query->where('(m.name LIKE '.$search.' OR m.description LIKE '.$search.' OR m.email LIKE '.$search.' OR m.phone LIKE '.$search.' OR m.contractors LIKE '.$search.')');
        }

        // Add the list ordering clause.
        $orderCol   = $this->state->get('list.ordering', 'm.id');
        $orderDirn  = $this->state->get('list.direction', 'asc');

        $query->order($db->escape($orderCol.' '.$orderDirn));

        return $query;
    }

}
