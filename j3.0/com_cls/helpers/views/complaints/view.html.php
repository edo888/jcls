<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');
require_once(JPATH_ADMINISTRATOR.'/includes/toolbar.php');

class ClsFrontViewComplaints extends JViewLegacy {

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
        $contracts_options  = $this->get('Contract_options');

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

class CLSFrontViewComplaints2 extends JViewLegacy {
    function display($tpl = null) {
        CLSView::showToolbar();

        global $option;
        $mainframe = JFactory::getApplication();

        // authorize
        $user =& JFactory::getUser();
        /* todo
        if($user->getParam('role', '') == '') {
            $return = JURI::base() . 'index.php?option=com_user&view=login';
            $return .= '&return=' . base64_encode(JURI::base() . 'index.php?' . JURI::getInstance()->getQuery());
            $mainframe->redirect($return);
        }
        */

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        /* todo
        if($user_type == 'Guest') {
            JError::raiseWarning(403, 'You are not authorized to view this page.');
            return;
        }
        */

        $document =& JFactory::getDocument();
        $document->addScript(JURI::base().'includes/js/joomla.javascript.js');
        $document->addStyleSheet(JURI::base().'administrator/templates/khepri/css/general.css');

        $db               =& JFactory::getDBO();
        $filter_order     = $mainframe->getUserStateFromRequest("$option.filter_order",'filter_order','m.id');
        $filter_order_Dir = $mainframe->getUserStateFromRequest("$option.filter_order_Dir",'filter_order_Dir','desc');
        $filter_area_id   = $mainframe->getUserStateFromRequest("$option.filter_area_id",'filter_area_id','');
        $filter_source    = $mainframe->getUserStateFromRequest("$option.filter_source",'filter_source','');
        $filter_priority  = $mainframe->getUserStateFromRequest("$option.filter_priority",'filter_priority','');
        $filter_status    = $mainframe->getUserStateFromRequest("$option.filter_status",'filter_status','');
        $search           = $mainframe->getUserStateFromRequest("$option.search",'search','');
        $search           = $db->getEscaped(trim(JString::strtolower($search)));

        $limit      = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = $mainframe->getUserStateFromRequest($option.'limitstart', 'limitstart', 0, 'int');

        $where = array();

        if($filter_area_id)
            $where[] = 'm.complaint_area_id = "'.$filter_area_id.'"';

        if($filter_source)
            $where[] = 'm.message_source = "'.$filter_source.'"';

        if($filter_priority)
            $where[] = 'm.message_priority = "'.$filter_priority.'"';

        if($filter_status)
            $where[] = 'confirmed_closed = "'.$filter_status.'"';

        if($search)
            $where[] = '(message_id LIKE "%'.$search.'%" OR raw_message LIKE "%'.$search.'%" OR processed_message LIKE "%'.$search.'%" OR resolution LIKE "%'.$search.'%")';

        // for level 2 users show only complaints assigned to them
        if($user_type == 'Level 2') {
            $query = 'select group_id from #__complaint_support_groups_users_map where user_id = ' . $user->id;
            $db->setQuery($query);
            $support_groups = $db->loadObjectList();
            $support_group_ids = array();
            foreach($support_groups as $support_group)
                $support_group_ids[] = $support_group->group_id;
            $support_group_ids = implode(',', $support_group_ids);

            $where[] = "m.support_group_id in ($support_group_ids)";
        }

        $where   = (count($where) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
        $orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

        $query = 'SELECT COUNT(m.id) FROM #__complaints as m left join #__complaint_areas as g on (m.complaint_area_id = g.id) left join #__users as u on (m.resolver_id = u.id) left join #__users as e on (m.editor_id = e.id) '.$where;
        $db->setQuery($query);
        $total = $db->loadResult();

        jimport('joomla.html.pagination');
        $pageNav = new JPagination($total,$limitstart,$limit);

        $query = 'SELECT m.*, concat(m.name, " ", m.email, " ", m.phone, " ", m.ip_address) as sender, g.area as area, u.name as resolver, e.name as editor FROM #__complaints as m left join #__complaint_areas as g on (m.complaint_area_id = g.id) left join #__users as u on (m.resolver_id = u.id) left join #__users as e on (m.editor_id = e.id) '.$where.' '.$orderby;
        $db->setQuery($query, $pageNav->limitstart, $pageNav->limit);
        $rows = $db->loadObjectList();
        //echo $query;

        if($db->getErrorNum()) {
            echo $db->stderr();
            return false;
        }

        // area_id filter
        $query = 'select * from #__complaint_areas';
        $db->setQuery($query);
        $areas = $db->loadObjectList();
        //$area[] = array('key' => '', 'value' => '- Select Area -');
        $area[] = array('key' => '', 'value' => '- Select Category -');
        foreach($areas as $a)
            $area[] = array('key' => $a->id, 'value' => $a->area);
        $lists['area'] = JHTML::_('select.genericlist', $area, 'filter_area_id', 'onchange=submitform();', 'key', 'value', $filter_area_id);

        // source filter
        $lists['source'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Source -' ), array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Email', 'value' => 'Email'), array('key' => 'Website', 'value' => 'Website'), array('key' => 'Telephone Call', 'value' => 'Telephone Call'), array('key' => 'Personal Visit', 'value' => 'Personal Visit'), array('key' => 'Field Visit by Project Staff', 'value' => 'Field Visit by Project Staff'), array('key' => 'Other', 'value' => 'Other')), 'filter_source', 'onchange=submitform();', 'key', 'value', $filter_source);

        // priority filter
        $lists['priority'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Priority -' ), array('key' => 'Low', 'value' => 'Low'), array('key' => 'Medium', 'value' => 'Medium'), array('key' => 'High', 'value' => 'High')), 'filter_priority', 'onchange=submitform();', 'key', 'value', $filter_priority);

        // priority status
        $lists['status'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Status -' ), array('key' => 'N', 'value' => 'Open'), array('key' => 'Y', 'value' => 'Resolved')), 'filter_status', 'onchange=submitform();', 'key', 'value', $filter_status);

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;

        // search filter
        $lists['search'] = $search;

        $this->lists = $lists;
        $this->rows = $rows;
        $this->pageNav = $pageNav;
        $this->option = $option;

        parent::display($tpl);
    }
}
