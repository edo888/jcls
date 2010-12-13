<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');

class CLSViewComplaints extends JView {
    function display($tpl = null) {
        global $mainframe, $option;

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
            $where[] = '(raw_message LIKE "%'.$search.'%" OR processed_message LIKE "%'.$search.'%" OR resolution LIKE "%'.$search.'%")';

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
        $area[] = array('key' => '', 'value' => '- Select Area -');
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

        $this->assignRef('lists', $lists);
        $this->assignRef('rows', $rows);
        $this->assignRef('pageNav', $pageNav);
        $this->assignRef('option', $option);

        parent::display($tpl);
    }
}