<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class CLSController extends JController {
    function __construct($default = array()) {
        parent::__construct($default);
        $this->registerTask('add' , 'editComplaint');
        $this->registerTask('edit', 'editComplaint');
        $this->registerTask('save', 'saveComplaint');
        $this->registerTask('apply', 'saveComplaint');
        $this->registerTask('remove', 'removeComplaint');
        $this->registerTask('addContract' , 'editContract');
        $this->registerTask('editContract', 'editContract');
        $this->registerTask('saveContract', 'saveContract');
        $this->registerTask('applyContract', 'saveContract');
        $this->registerTask('removeContract', 'removeContract');
        $this->registerTask('cancelContract', 'showContracts');
        $this->registerTask('addSection' , 'editSection');
        $this->registerTask('editSection', 'editSection');
        $this->registerTask('saveSection', 'saveSection');
        $this->registerTask('applySection', 'saveSection');
        $this->registerTask('removeSection', 'removeSection');
        $this->registerTask('cancelSection', 'showSections');
        $this->registerTask('addSupportGroup' , 'editSupportGroup');
        $this->registerTask('editSupportGroup', 'editSupportGroup');
        $this->registerTask('saveSupportGroup', 'saveSupportGroup');
        $this->registerTask('applySupportGroup', 'saveSupportGroup');
        $this->registerTask('removeSupportGroup', 'removeSupportGroup');
        $this->registerTask('cancelSupportGroup', 'showSupportGroups');
        $this->registerTask('download_report', 'downloadReport');
        $this->registerTask('notify_sms_acknowledge', 'notifySMSAcknowledge');
        $this->registerTask('notify_email_acknowledge', 'notifyEmailAcknowledge');
        $this->registerTask('notify_sms_resolve', 'notifySMSResolve');
        $this->registerTask('notify_email_resolve', 'notifyEmailResolve');
        $this->registerTask('upload_picture', 'uploadPicture');
    }

    function showComplaints() {
        global $mainframe, $option;

        $db                 =& JFactory::getDBO();
        $filter_order       = $mainframe->getUserStateFromRequest("$option.filter_order",'filter_order','m.id');
        $filter_order_Dir   = $mainframe->getUserStateFromRequest("$option.filter_order_Dir",'filter_order_Dir','desc');
        $filter_area_id     = $mainframe->getUserStateFromRequest("$option.filter_area_id",'filter_area_id','');
        $filter_contract_id = $mainframe->getUserStateFromRequest("$option.filter_contract_id",'filter_contract_id','');
        $filter_source      = $mainframe->getUserStateFromRequest("$option.filter_source",'filter_source','');
        $filter_priority    = $mainframe->getUserStateFromRequest("$option.filter_priority",'filter_priority','');
        $filter_status      = $mainframe->getUserStateFromRequest("$option.filter_status",'filter_status','');
        $search             = $mainframe->getUserStateFromRequest("$option.search",'search','');
        $search             = $db->getEscaped(trim(JString::strtolower($search)));

        $limit      = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = $mainframe->getUserStateFromRequest($option.'limitstart', 'limitstart', 0, 'int');

        $where = array();

        if($filter_area_id)
            $where[] = 'm.complaint_area_id = "'.$filter_area_id.'"';

        if($filter_contract_id)
            $where[] = 'm.contract_id = "'.$filter_contract_id.'"';

        if($filter_source)
            $where[] = 'm.message_source = "'.$filter_source.'"';

        if($filter_priority)
            $where[] = 'm.message_priority = "'.$filter_priority.'"';

        if($filter_status)
            $where[] = 'confirmed_closed = "'.$filter_status.'"';

        if($search)
            $where[] = '(message_id LIKE "%'.$search.'%" OR raw_message LIKE "%'.$search.'%" OR processed_message LIKE "%'.$search.'%" OR resolution LIKE "%'.$search.'%")';

        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

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

        // contract_id filter
        $query = 'select * from #__complaint_contracts';
        $db->setQuery($query);
        $contracts = $db->loadObjectList();
        $contract[] = array('key' => '', 'value' => '- Select Contract -');
        foreach($contracts as $a)
            $contract[] = array('key' => $a->id, 'value' => $a->name);
        $lists['contract'] = JHTML::_('select.genericlist', $contract, 'filter_contract_id', 'onchange=submitform();', 'key', 'value', $filter_contract_id);

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

        CLSView::showComplaints($rows, $pageNav, $option, $lists);
    }

    function viewLocation() {
        CLSView::viewLocation();
    }

    function editLocation() {
        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        CLSView::editLocation();
    }

    function viewSectionMap() {
        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Supervisor' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        CLSView::viewSectionMap();
    }

    function editSectionMap() {
        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        CLSView::editSectionMap();
    }

    function showReports() {
        CLSView::showReports();
    }

    function showNotifications() {
        global $mainframe, $option;

        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db     =& JFactory::getDBO();
        $config =& JComponentHelper::getParams('com_cls');

        $filter_order     = $mainframe->getUserStateFromRequest("$option.filter_order",'filter_order','m.id');
        $filter_order_Dir = $mainframe->getUserStateFromRequest("$option.filter_order_Dir",'filter_order_Dir','desc');
        $filter_user_id   = $mainframe->getUserStateFromRequest("$option.filter_user_id",'filter_user_id','');
        $filter_action    = $mainframe->getUserStateFromRequest("$option.filter_action",'filter_action','');
        $search           = $mainframe->getUserStateFromRequest("$option.search",'search','');
        $search           = $db->getEscaped(trim(JString::strtolower($search)));

        $limit      = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = $mainframe->getUserStateFromRequest($option.'limitstart', 'limitstart', 0, 'int');

        $where = array();

        if($filter_user_id)
            $where[] = 'm.user_id = "'.$filter_user_id.'"';

        if($filter_action)
            $where[] = 'm.action = "'.$filter_action.'"';

        if($search)
            $where[] = 'm.description LIKE "%'.$search.'%"';

        $where   = (count($where) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
        $orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

        $query = 'SELECT COUNT(m.id) FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) ' . $where;
        $db->setQuery($query);
        $total = $db->loadResult();

        jimport('joomla.html.pagination');
        $pageNav = new JPagination($total,$limitstart,$limit);

        $query = 'SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) ' . $where . ' ' . $orderby;
        $db->setQuery($query, $pageNav->limitstart, $pageNav->limit);
        $rows = $db->loadObjectList();
        //echo $query;

        if($db->getErrorNum()) {
            echo $db->stderr();
            return false;
        }

        // user_id filter
        $query = 'select distinct n.user_id, u.name from #__complaint_notifications as n left join #__users as u on (n.user_id = u.id)';
        $db->setQuery($query);
        $users = $db->loadObjectList();
        $user[] = array('key' => '', 'value' => '- Select User -');
        foreach($users as $u) {
            $u->name = $u->user_id == 0 ? 'System' : $u->name;
            $user[] = array('key' => $u->user_id, 'value' => $u->name);
        }
        $lists['user_id'] = JHTML::_('select.genericlist', $user, 'filter_user_id', 'onchange=submitform();', 'key', 'value', $filter_user_id);

        // action filter
        $query = 'select distinct action from #__complaint_notifications';
        $db->setQuery($query);
        $actions = $db->loadObjectList();
        $action[] = array('key' => '', 'value' => '- Select Action -');
        foreach($actions as $a)
            $action[] = array('key' => $a->action, 'value' => $a->action);
        $lists['action'] = JHTML::_('select.genericlist', $action, 'filter_action', 'onchange=submitform();', 'key', 'value', $filter_action);

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;

        // search filter
        $lists['search'] = $search;

        CLSView::showNotifications($rows, $pageNav, $option, $lists);
    }

    function showContracts() {
        global $mainframe, $option;

        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db               =& JFactory::getDBO();
        $filter_order     = $mainframe->getUserStateFromRequest("$option.filter_order",'filter_order','m.id');
        $filter_order_Dir = $mainframe->getUserStateFromRequest("$option.filter_order_Dir",'filter_order_Dir','desc');
        $filter_section   = $mainframe->getUserStateFromRequest("$option.filter_section",'filter_section','');
        $search           = $mainframe->getUserStateFromRequest("$option.search",'search','');
        $search           = $db->getEscaped(trim(JString::strtolower($search)));

        $limit      = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = $mainframe->getUserStateFromRequest($option.'limitstart', 'limitstart', 0, 'int');

        $where = array();

        if($filter_section)
            $where[] = 'm.section_id = "'.$filter_section.'"';

        if($search)
            $where[] = '(m.name LIKE "%'.$search.'%" OR m.description LIKE "%'.$search.'%")';

        $where   = (count($where) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
        $orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

        $query = 'SELECT COUNT(m.id) FROM #__complaint_contracts as m left join #__complaint_sections as s on (m.section_id = s.id) '.$where;
        $db->setQuery($query);
        $total = $db->loadResult();

        jimport('joomla.html.pagination');
        $pageNav = new JPagination($total,$limitstart,$limit);

        $query = 'SELECT m.*, s.name as section_name FROM #__complaint_contracts as m left join #__complaint_sections as s on (m.section_id = s.id) '.$where.' '.$orderby;
        $db->setQuery($query, $pageNav->limitstart, $pageNav->limit);
        $rows = $db->loadObjectList();
        //echo $query;

        if($db->getErrorNum()) {
            echo $db->stderr();
            return false;
        }

        // section filter
        $query = 'select * from #__complaint_sections';
        $db->setQuery($query);
        $sections = $db->loadObjectList();
        $section[] = array('key' => '', 'value' => '- Select Section -');
        foreach($sections as $a)
            $section[] = array('key' => $a->id, 'value' => $a->name);
        $lists['section'] = JHTML::_('select.genericlist', $section, 'filter_section', 'onchange=submitform();', 'key', 'value', $filter_section);

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;

        // search filter
        $lists['search'] = $search;

        CLSView::showContracts($rows, $pageNav, $option, $lists);
    }

    function showSections() {
        global $mainframe, $option;

        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db               =& JFactory::getDBO();
        $filter_order     = $mainframe->getUserStateFromRequest("$option.filter_order",'filter_order','m.id');
        $filter_order_Dir = $mainframe->getUserStateFromRequest("$option.filter_order_Dir",'filter_order_Dir','desc');
        $search           = $mainframe->getUserStateFromRequest("$option.search",'search','');
        $search           = $db->getEscaped(trim(JString::strtolower($search)));

        $limit      = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = $mainframe->getUserStateFromRequest($option.'limitstart', 'limitstart', 0, 'int');

        $where = array();

        if($search)
            $where[] = '(name LIKE "%'.$search.'%" OR description LIKE "%'.$search.'%")';

        $where   = (count($where) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
        $orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

        $query = 'SELECT COUNT(m.id) FROM #__complaint_sections as m '.$where;
        $db->setQuery($query);
        $total = $db->loadResult();

        jimport('joomla.html.pagination');
        $pageNav = new JPagination($total,$limitstart,$limit);

        $query = 'SELECT m.* FROM #__complaint_sections as m '.$where.' '.$orderby;
        $db->setQuery($query, $pageNav->limitstart, $pageNav->limit);
        $rows = $db->loadObjectList();
        //echo $query;

        if($db->getErrorNum()) {
            echo $db->stderr();
            return false;
        }

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;

        // search filter
        $lists['search'] = $search;

        CLSView::showSections($rows, $pageNav, $option, $lists);
    }

    function showSupportGroups() {
        global $mainframe, $option;

        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db               =& JFactory::getDBO();
        $filter_order     = $mainframe->getUserStateFromRequest("$option.filter_order",'filter_order','m.id');
        $filter_order_Dir = $mainframe->getUserStateFromRequest("$option.filter_order_Dir",'filter_order_Dir','desc');
        $search           = $mainframe->getUserStateFromRequest("$option.search",'search','');
        $search           = $db->getEscaped(trim(JString::strtolower($search)));

        $limit      = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $limitstart = $mainframe->getUserStateFromRequest($option.'limitstart', 'limitstart', 0, 'int');

        $where = array();

        if($search)
            $where[] = '(name LIKE "%'.$search.'%" OR description LIKE "%'.$search.'%")';

        $where   = (count($where) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
        $orderby = ' ORDER BY '. $filter_order .' '. $filter_order_Dir;

        $query = 'SELECT COUNT(m.id) FROM #__complaint_support_groups as m '.$where;
        $db->setQuery($query);
        $total = $db->loadResult();

        jimport('joomla.html.pagination');
        $pageNav = new JPagination($total,$limitstart,$limit);

        $query = 'SELECT m.* FROM #__complaint_support_groups as m '.$where.' '.$orderby;
        $db->setQuery($query, $pageNav->limitstart, $pageNav->limit);
        $rows = $db->loadObjectList();
        //echo $query;

        if($db->getErrorNum()) {
            echo $db->stderr();
            return false;
        }

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;

        // search filter
        $lists['search'] = $search;

        CLSView::showSupportGroups($rows, $pageNav, $option, $lists);
    }

    function downloadReport() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $doc  =& JFactory::getDocument();

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $session =& JFactory::getSession();
        $config =& JComponentHelper::getParams('com_cls');

        $statistics_period = (int) $config->get('statistics_period', 20);
        $startdate = JRequest::getCmd('startdate', $session->get('startdate', date('Y-m-d', strtotime("-$statistics_period days")), 'com_cls'));
        $session->set('startdate', $startdate, 'com_cls');
        $enddate = JRequest::getCmd('enddate', $session->get('enddate', date('Y-m-d'), 'com_cls'));
        $session->set('enddate', $enddate, 'com_cls');

        $period = JRequest::getVar('period', 'all');

        $query = 'select c.*, e.name as editor, r.name as resolver, a.area as complaint_area from #__complaints as c left join #__complaint_areas as a on (c.complaint_area_id = a.id) left join #__users as e on (c.editor_id = e.id) left join #__users as r on (c.resolver_id = r.id)';

        switch($period) {
            case 'period': $query .= " where date_received >= '$startdate' and date_received <= '$enddate'"; break;
            case 'month': $query .= ' where date_received >= DATE_ADD(now(), interval -1 month)'; break;
            case 'current_month': $query .= " where date_received >= '" . date("Y-m-01") . "'"; break;
            case 'prev_month': $query .= " where date_received < '" . date("Y-m-01") . "' and date_received >= DATE_ADD('".date("Y-m-01")."', interval -1 month)"; break;
            default: break;
        }

        $db->setQuery($query);
        $complaints = $db->loadObjectList();

        $tmp_file = tempnam(JPATH_ROOT.'/dmdocuments', 'cls');
        $fh = fopen($tmp_file, 'w') or die('cannot open file for writing');
        //fputcsv($fh, array('MessageID', 'Name', 'Email', 'Tel', 'Address', 'Sender IP', 'Message Source', 'Message Priority', 'Complaint Area', 'Editor', 'Resolver', 'Resolution', 'Resolved and Closed', 'Raw Message', 'Processed Message', 'Comments')) or die('cannot write');
        fputcsv($fh, array('MessageID', 'Name', 'Email', 'Tel', 'Address', 'Sender IP', 'Message Source', 'Message Priority', 'Complaint Category', 'Editor', 'Resolver', 'Resolution', 'Resolved and Closed', 'Raw Message', 'Processed Message', 'Comments')) or die('cannot write');
        foreach($complaints as $complaint)
            fputcsv($fh, array($complaint->message_id, $complaint->name, $complaint->email, $complaint->phone, $complaint->address, $complaint->ip_address, $complaint->message_source, $complaint->message_priority, $complaint->complaint_area, $complaint->editor, $complaint->resolver, $complaint->resolution, $complaint->confirmed_closed, $complaint->raw_message, $complaint->processed_message, $complaint->comments));
        fclose($fh);

        header("Cache-Control: public, must-revalidate");
        header("Pragma: hack");
        header("Content-Type: application/octet-stream");
        header("Content-Length: " . filesize($tmp_file));
        header('Content-Disposition: attachment; filename="'.basename($tmp_file).'.csv"');
        header("Content-Transfer-Encoding: binary\n");

        echo file_get_contents($tmp_file);
        unlink($tmp_file);

        clsLog('Report downloaded', 'User have downloaded a report for the ' . $period . ' period');
        exit;
    }

    function editComplaint() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        if($this->_task == 'edit') {
            $cid = JRequest::getVar('cid', array(0), 'method', 'array');
            $cid = array((int) $cid[0]);
        } else {
            $cid = array( 0 );
        }

        $query = 'select c.*, e.name as editor, r.name as resolver, a.area as complaint_area, p.name as contract, s.name as support_group from #__complaints as c left join #__complaint_areas as a on (c.complaint_area_id = a.id) left join #__users as e on (c.editor_id = e.id) left join #__users as r on (c.resolver_id = r.id) left join #__complaint_contracts as p on (c.contract_id = p.id) left join #__complaint_support_groups as s on (c.support_group_id = s.id) where c.id = ' . $cid[0];
        $db->setQuery($query);
        $row = $db->loadObject();

        // complaint pictures
        $query = 'select * from #__complaint_pictures where complaint_id = ' . $cid[0];
        $db->setQuery($query);
        $row->pictures = $db->loadObjectList();

        // notifications queue
        $query = "select * from #__complaint_message_queue where complaint_id = $row->id order by id desc";
        $db->setQuery($query);
        $row->notifications_queue = $db->loadObjectList();

        // activity log
        $query = "SELECT m.*, u.name as user FROM #__complaint_notifications as m left join #__users as u on (m.user_id = u.id) where m.description like '%#$row->message_id%' order by m.id desc limit 10";
        $db->setQuery($query);
        $row->activity_log = $db->loadObjectList();

        // area_id list
        $query = 'select * from #__complaint_areas';
        $db->setQuery($query);
        $areas = $db->loadObjectList();
        //$area[] = array('key' => '', 'value' => '- Select Area -');
        $area[] = array('key' => '', 'value' => '- Select Category -');
        foreach($areas as $a)
            $area[] = array('key' => $a->id, 'value' => $a->area);
        $lists['area'] = JHTML::_('select.genericlist', $area, 'complaint_area_id', null, 'key', 'value', $row->complaint_area_id);

        // contract_id list
        $query = 'select * from #__complaint_contracts';
        $db->setQuery($query);
        $contracts = $db->loadObjectList();
        $contract[] = array('key' => '', 'value' => '- Select Contract -');
        foreach($contracts as $a)
            $contract[] = array('key' => $a->id, 'value' => $a->name);
        $lists['contract'] = JHTML::_('select.genericlist', $contract, 'contract_id', null, 'key', 'value', $row->contract_id);

        // editor list
        $query = 'select * from #__users where params like "%role=Level 1%" or params like "%role=System Administrator%"';
        $db->setQuery($query);
        $editors = $db->loadObjectList();
        $editor[] = array('key' => '', 'value' => '- Select Editor -');
        foreach($editors as $e)
            $editor[] = array('key' => $e->id, 'value' => $e->name);
        $lists['editor'] = JHTML::_('select.genericlist', $editor, 'editor_id', null, 'key', 'value', $row->editor_id);

        // resolver list
        $query = 'select * from #__users where params like "%role=Level 1%" or params like "%role=System Administrator%"';
        $db->setQuery($query);
        $resolvers = $db->loadObjectList();
        $resolver[] = array('key' => '', 'value' => '- Select Resolver -');
        foreach($resolvers as $r)
            $resolver[] = array('key' => $r->id, 'value' => $r->name);
        $lists['resolver'] = JHTML::_('select.genericlist', $resolver, 'resolver_id', null, 'key', 'value', $row->resolver_id);

        // support groups list
        $query = 'select * from #__complaint_support_groups';
        $db->setQuery($query);
        $support_groups = $db->loadObjectList();
        $support_group[] = array('key' => '', 'value' => '- Select Support Group -');
        foreach($support_groups as $g)
            $support_group[] = array('key' => $g->id, 'value' => $g->name);
        $lists['support_group'] = JHTML::_('select.genericlist', $support_group, 'support_group_id', null, 'key', 'value', $row->support_group_id);

        // source list
        $lists['source'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Source -' ), array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Email', 'value' => 'Email'), array('key' => 'Website', 'value' => 'Website'), array('key' => 'Telephone Call', 'value' => 'Telephone Call'), array('key' => 'Personal Visit', 'value' => 'Personal Visit'), array('key' => 'Field Visit by Project Staff', 'value' => 'Field Visit by Project Staff'), array('key' => 'Other', 'value' => 'Other')), 'message_source', null, 'key', 'value', $row->message_source);

        // preferred contact list
        $lists['preferred_contact'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Contact Method -' ), array('key' => 'Email', 'value' => 'Email'), array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Telephone Call', 'value' => 'Telephone Call')), 'preferred_contact', null, 'key', 'value', $row->preferred_contact);

        // priority list
        //$lists['priority'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Priority -' ), array('key' => 'Low', 'value' => 'Low'), array('key' => 'Medium', 'value' => 'Medium'), array('key' => 'High', 'value' => 'High')), 'message_priority', null, 'key', 'value', $row->message_priority);

        $config =& JComponentHelper::getParams('com_cls');
        JHTML::_('behavior.tooltip');

        $priority[0] = new stdClass();
        $priority[0]->value = 'Low';
        $priority[0]->text = JHTML::tooltip($config->get('low_priority_description', ''), JText::_('Low'), '', JText::_('Low'));
        $priority[1] = new stdClass();
        $priority[1]->value = 'Medium';
        $priority[1]->text = JHTML::tooltip($config->get('medium_priority_description', ''), JText::_('Medium'), '', JText::_('Medium'));
        $priority[2] = new stdClass();
        $priority[2]->value = 'High';
        $priority[2]->text = JHTML::tooltip($config->get('high_priority_description', ''), JText::_('High'), '', JText::_('High'));

        $lists['priority'] = JHTML::_('select.radiolist', $priority, 'message_priority', null, 'value', 'text', $row->message_priority);

        // confirmed_closed list
        $lists['confirmed'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Confirmation -' ), array('key' => 'Y', 'value' => 'Yes'), array('key' => 'N', 'value' => 'No')), 'confirmed_closed', null, 'key', 'value', $row->confirmed_closed);

        CLSView::editComplaint($row, $lists, $user_type);
    }

    function editContract() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        if($this->_task == 'editContract') {
            $cid = JRequest::getVar('cid', array(0), 'method', 'array');
            $cid = array((int) $cid[0]);
        } else {
            $cid = array( 0 );
        }

        $query = 'select c.*, s.name as section_name from #__complaint_contracts as c left join #__complaint_sections as s on (c.section_id = s.id) where c.id = ' . $cid[0];
        $db->setQuery($query);
        $row = $db->loadObject();

        // section list
        $query = 'select * from #__complaint_sections';
        $db->setQuery($query);
        $sections = $db->loadObjectList();
        $section[] = array('key' => '', 'value' => '- Select Section -');
        foreach($sections as $a)
            $section[] = array('key' => $a->id, 'value' => $a->name);
        $lists['section'] = JHTML::_('select.genericlist', $section, 'section_id', null, 'key', 'value', $row->section_id);

        CLSView::editContract($row, $lists, $user_type);
    }

    function editSection() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        if($this->_task == 'editSection') {
            $cid = JRequest::getVar('cid', array(0), 'method', 'array');
            $cid = array((int) $cid[0]);
        } else {
            $cid = array( 0 );
        }

        $query = 'select c.* from #__complaint_sections as c where c.id = ' . $cid[0];
        $db->setQuery($query);
        $row = $db->loadObject();

        $lists = array();

        CLSView::editSection($row, $lists, $user_type);
    }

    function editSupportGroup() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        if($this->_task == 'editSupportGroup') {
            $cid = JRequest::getVar('cid', array(0), 'method', 'array');
            $cid = array((int) $cid[0]);
        } else {
            $cid = array( 0 );
        }

        $query = 'select c.* from #__complaint_support_groups as c where c.id = ' . $cid[0];
        $db->setQuery($query);
        $row = $db->loadObject();

        $query = "select u.id as user_id, u.name, c.group_id from #__users as u left join #__complaint_support_groups_users_map as c on (u.id = c.user_id and (c.group_id is null or c.group_id = $cid[0])) where u.params like '%role=Level 2%'";
        //$query = "select u.id as user_id, u.name, c.group_id from #__users as u left join #__complaint_support_groups_users_map as c on (u.id = c.user_id and (c.group_id is null or c.group_id = $cid[0]))";
        $db->setQuery($query);
        $row->users = $db->loadObjectList();

        foreach($row->users as $i => $u) {
            if($u->group_id != $cid[0])
                $row->users[$i]->group_id = '';
        }

        $lists = array();

        CLSView::editSupportGroup($row, $lists, $user_type);
    }

    function saveComplaint() {
        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        if($id == 0) { // going to insert new complaint
            // generating message_id
            $date = date('Y-m-d');
            $query = "select count(*) from #__complaints where date_received >= '$date 00:00:00' and date_received <= '$date 23:59:59'";
            $db->setQuery($query);
            $count = $db->loadResult();
            if($count == 0) { // reset the counter for current day
                $db->setQuery('delete from #__complaint_message_ids');
                $db->query();
                $db->setQuery('alter table #__complaint_message_ids auto_increment = 0');
                $db->query();
            }
            $db->setQuery('insert into #__complaint_message_ids value(null)');
            $db->query();
            $message_id = $db->insertid();
            $message_id = $date.'-'.str_pad($message_id, 4, '0', STR_PAD_LEFT);

            // constructing the complaint object
            $complaint = new JTable('#__complaints', 'id', $db);
            $complaint->set('message_id', $message_id);
            $complaint->set('name', JRequest::getVar('name'));
            $complaint->set('email', JRequest::getVar('email'));
            $complaint->set('phone', JRequest::getVar('phone'));
            $complaint->set('address', JRequest::getVar('address'));
            $complaint->set('ip_address', JRequest::getVar('ip_address'));
            $complaint->set('raw_message', JRequest::getVar('raw_message'));
            $complaint->set('date_received', date('Y-m-d H:i:s'));
            $complaint->set('message_source', JRequest::getVar('message_source'));
            $complaint->store();

            // adding notification
            clsLog('New back-end complaint', 'New back-end complaint created #' . $message_id);

            $this->setRedirect('index.php?option=com_cls', JText::_('Complaint successfully created'));
        } else { // going to update complaint

            // constructing the complaint object
            $complaint = new JTable('#__complaints', 'id', $db);
            $complaint->set('id', $id);
            $complaint->set('message_id', null);
            $complaint->set('name', null);
            $complaint->set('email', null);
            $complaint->set('phone', null);
            $complaint->set('address', null);
            $complaint->set('ip_address', null);
            $complaint->set('preferred_contact', null);
            $complaint->set('editor_id', null);
            $complaint->set('raw_message', null);
            $complaint->set('processed_message', null);
            $complaint->set('contract_id', null);
            $complaint->set('support_group_id', null);
            $complaint->set('location', null);
            $complaint->set('complaint_area_id', null);
            $complaint->set('date_received', null);
            $complaint->set('date_processed', null);
            $complaint->set('date_resolved', null);
            $complaint->set('resolver_id', null);
            $complaint->set('resolution', null);
            $complaint->set('message_source', null);
            $complaint->set('message_priority', null);
            $complaint->set('confirmed_closed', null);
            $complaint->set('date_closed', null);
            $complaint->set('comments', null);
            $complaint->load();

            if($user_type == 'System Administrator') {
                $complaint->set('ip_address', JRequest::getVar('ip_address'));
                $complaint->set('raw_message', JRequest::getVar('raw_message'));
                $complaint->set('message_source', JRequest::getVar('message_source'));
                $complaint->set('editor_id', JRequest::getInt('editor_id'));
                $complaint->set('resolver_id', JRequest::getInt('resolver_id'));
            }

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $complaint->set('name', JRequest::getVar('name'));
                $complaint->set('email', JRequest::getVar('email'));
                $complaint->set('phone', JRequest::getVar('phone'));
                $complaint->set('address', JRequest::getVar('address'));
                $complaint->set('preferred_contact', JRequest::getVar('preferred_contact'));
                $complaint->set('confirmed_closed', JRequest::getVar('confirmed_closed'));
                $complaint->set('message_priority', JRequest::getVar('message_priority'));
                $complaint->set('complaint_area_id', JRequest::getInt('complaint_area_id'));
                $complaint->set('processed_message', JRequest::getVar('processed_message'));
                $complaint->set('contract_id', JRequest::getInt('contract_id'));
                $complaint->set('support_group_id', JRequest::getInt('support_group_id'));
                if(JRequest::getVar('location') != '')
                    $complaint->set('location', JRequest::getVar('location'));
                if($complaint->date_processed == '' and $complaint->processed_message != '') {
                    $complaint->set('date_processed', date('Y-m-d H:i:s'));

                    clsLog('Complaint processed', 'The user processed the complaint #' . $complaint->message_id);

                    // Send processed complaint notification to members
                    $config =& JComponentHelper::getParams('com_cls');

                    $support_group_id = JRequest::getInt('support_group_id');

                    $query = array();
                    // Send notification to Supervisors
                    $query[] = "(select email, name, params from #__users where params like '%receive_notifications=1%' and params like '%role=Supervisor%')";
                    if($support_group_id)// Send notification to assagned Level 2 support groups
                        $query[] = "(select email, name, params from #__users where params like '%receive_notifications=1%' and params like '%role=Level 2%' and id in (select user_id from #__complaint_support_groups_users_map where group_id = $support_group_id))";

                    $query = implode(' UNION ALL ', $query);

                    $db->setQuery($query);
                    $res = $db->query();

                    jimport('joomla.mail.mail');
                    $mail = new JMail();
                    $mail->From = $config->get('complaints_email');
                    $mail->FromName = 'Complaint Logging System';
                    $mail->Subject = 'New Processed Complaint: #' . $complaint->message_id;
                    $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
                    $mail->msgHTML('<p>A complaint was processed. Login to http://'.$_SERVER['HTTP_HOST'].'/administrator/index.php?option=com_cls to resolve it.</p>' . $complaint->processed_message);
                    $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
                    while($row = mysql_fetch_array($res, MYSQL_NUM)) {
                        if(preg_match('/receive_by_email=1/', $row[2])) { // send email notification
                            $mail->AddAddress($row[0]);
                            clsLog('Processed notification sent', 'Complaint #' . $complaint->message_id . ' processed notification sent to ' . $row[1]);
                        }

                        if(preg_match('/receive_by_sms=1/', $row[2])) { // send sms notification
                            preg_match('/telephone=(.*)/', $row[2], $matches);
                            if(isset($matches[1]) and $matches[1] != '') {
                                $telephone = $matches[1];
                                $db->setQuery("insert into #__complaint_message_queue value(null, $id, 'CLS', '$telephone', 'Complaint #$complaint->message_id processed, please login to the system to resolve it.', now(), 'Pending', 'Notification')");
                                $db->query();

                                clsLog('Processed notification sent', 'Complaint #' . $complaint->message_id . ' processed notification sent to ' . $telephone);
                            }
                        }
                    }
                    $mail->Send();
                }
            }

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $complaint->set('resolution', JRequest::getVar('resolution'));
                if($complaint->date_resolved == '' and $complaint->resolution != '') {
                    $complaint->set('date_resolved', date('Y-m-d H:i:s'));
                    $complaint->set('resolver_id', $user->id);

                    clsLog('Complaint resolved', 'The user resolved the complaint #' . $complaint->message_id);
                }

                // send notifications
                if($complaint->confirmed_closed == 'Y') {
                    $complaint->set('date_closed', date('Y-m-d H:i:s'));

                    // notify supervisors and level 2 group assigned to the complaint
                    $config =& JComponentHelper::getParams('com_cls');
                    $support_group_id = JRequest::getInt('support_group_id');

                    $query = array();
                    // Send notification to Supervisors
                    $query[] = "(select email, name, params from #__users where params like '%receive_notifications=1%' and params like '%role=Supervisor%')";
                    // Send notification to assagned Level 2 support groups
                    if($support_group_id)
                        $query[] = "(select email, name, params from #__users where params like '%receive_notifications=1%' and params like '%role=Level 2%' and id in (select user_id from #__complaint_support_groups_users_map where group_id = $support_group_id))";

                    $query = implode(' UNION ALL ', $query);

                    $db->setQuery($query);
                    $res = $db->query();

                    jimport('joomla.mail.mail');
                    $mail = new JMail();
                    $mail->From = $config->get('complaints_email');
                    $mail->FromName = 'Complaint Logging System';
                    $mail->Subject = 'Complaint resolved and closed: #' . $complaint->message_id;
                    $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
                    $mail->msgHTML("<p>Complaint #$complaint->message_id has been resolved and closed. Thanks for your efforts.</p>");
                    $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
                    while($row = mysql_fetch_array($res, MYSQL_NUM)) {
                        if(preg_match('/receive_by_email=1/', $row[2])) { // send email notification
                            $mail->AddAddress($row[0]);
                            clsLog('Resolved and closed notification', 'Complaint #' . $complaint->message_id . ' resolution notification has been sent to ' . $row[1]);
                        }

                        if(preg_match('/receive_by_sms=1/', $row[2])) { // send sms notification
                            preg_match('/telephone=(.*)/', $row[2], $matches);
                            if(isset($matches[1]) and $matches[1] != '') {
                                $telephone = $matches[1];
                                $db->setQuery("insert into #__complaint_message_queue value(null, $id, 'CLS', '$telephone', 'Complaint #$complaint->message_id has been resolved and closed. Thanks for your efforts.', now(), 'Pending', 'Notification')");
                                $db->query();

                                clsLog('Resolved and closed notification', 'Complaint #' . $complaint->message_id . ' resolution notification has been sent to ' . $telephone);
                            }
                        }
                    }
                    $mail->Send();

                    // send resolved complaint acknowledgment
                    JRequest::setVar('id', $complaint->id);
                    $sms_acknowledgment = (int) $config->get('sms_acknowledgment', 0);
                    $email_acknowledgment = (int) $config->get('email_acknowledgment', 0);

                    if($sms_acknowledgment)
                        $this->notifySMSResolve();
                    if($email_acknowledgment)
                        $this->notifyEmailResolve();
                }
            }

            if($user_type != 'Guest') {
                if(JRequest::getVar('comments', '') != '') { // append comment
                    $complaint->set('comments', $complaint->comments . 'On ' . date('Y-m-d H:i:s') . ' ' . $user->name . " wrote:\n" . JRequest::getVar('comments') . "\n\n");
                    clsLog('Complaint comment added', 'The user added a follow up comment on the complaint #' . $complaint->message_id);

                    $config =& JComponentHelper::getParams('com_cls');
                    $support_group_id = JRequest::getInt('support_group_id');

                    // Send notification
                    $query = array();

                    // Send notification to Supervisors
                    $query[] = "(select email, name, params from #__users where params like '%receive_notifications=1%' and params like '%role=Supervisor%')";

                    // Send notification to assagned Level 2 support groups
                    if($support_group_id)
                        $query[] = "(select email, name, params from #__users where params like '%receive_notifications=1%' and params like '%role=Level 2%' and id in (select user_id from #__complaint_support_groups_users_map where group_id = $support_group_id))";

                    $query = implode(' UNION ALL ', $query);

                    $db->setQuery($query);
                    $res = $db->query();

                    jimport('joomla.mail.mail');
                    $mail = new JMail();
                    $mail->From = $config->get('complaints_email');
                    $mail->FromName = 'Complaint Logging System';
                    $mail->Subject = 'New Comment Posted: #' . $complaint->message_id;
                    $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
                    $mail->msgHTML('<h3>New comment has been posted:</h3><pre>' . $complaint->comments . '</pre>');
                    $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
                    while($row = mysql_fetch_array($res, MYSQL_NUM)) {
                        if(preg_match('/receive_by_email=1/', $row[2])) { // send email notification
                            $mail->AddAddress($row[0]);
                            clsLog('New comment notification', 'Complaint #' . $complaint->message_id . ' comment notification sent to ' . $row[1]);
                        }

                        if(preg_match('/receive_by_sms=1/', $row[2])) { // send sms notification
                            preg_match('/telephone=(.*)/', $row[2], $matches);
                            if(isset($matches[1]) and $matches[1] != '') {
                                $telephone = $matches[1];
                                $db->setQuery("insert into #__complaint_message_queue value(null, $id, 'CLS', '$telephone', 'Complaint #$complaint->message_id got new comments, please login to the system to take actions.', now(), 'Pending', 'Notification')");
                                $db->query();

                                clsLog('New comment notification', 'Complaint #' . $complaint->message_id . ' comment notification sent to ' . $telephone);
                            }
                        }
                    }
                    $mail->Send();
                }

                // storing updated data
                //echo '<pre>', print_r($complaint, true), '</pre>';
                //exit;
                $complaint->store();
                clsLog('Complaint updated', 'The user updated complaint #' . $complaint->message_id . ' data');
            }

            if($this->_task == 'save')
                $this->setRedirect('index.php?option=com_cls', JText::_('Complaint successfully saved'));
            elseif($this->_task == 'apply')
                $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('Complaint successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function saveContract() {
        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        if($id == 0) { // going to insert new contract
            // constructing the contract object
            $contract = new JTable('#__complaint_contracts', 'id', $db);
            $contract->set('name', JRequest::getVar('name'));
            $contract->set('contract_id', JRequest::getVar('contract_id'));
            $contract->set('start_date', JRequest::getVar('start_date'));
            $contract->set('end_date', JRequest::getVar('end_date'));
            $contract->set('contractors', JRequest::getVar('contractors'));
            $contract->set('section_id', JRequest::getInt('section_id'));
            $contract->set('description', JRequest::getVar('description'));
            $contract->store();

            // adding notification
            clsLog('New contract', 'New contract created #' . $db->insertid());

            $this->setRedirect('index.php?option=com_cls&c=contracts', JText::_('Contract successfully created'));
        } else { // going to update section
            // constructing the contract object
            $contract = new JTable('#__complaint_contracts', 'id', $db);
            $contract->set('id', $id);
            $contract->set('name', null);
            $contract->set('contract_id', null);
            $contract->set('start_date', null);
            $contract->set('end_date', null);
            $contract->set('contractors', null);
            $contract->set('section_id', null);
            $contract->set('description', null);
            $contract->load();

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $contract->set('name', JRequest::getVar('name'));
                $contract->set('contract_id', JRequest::getVar('contract_id'));
                $contract->set('start_date', JRequest::getVar('start_date'));
                $contract->set('end_date', JRequest::getVar('end_date'));
                $contract->set('contractors', JRequest::getVar('contractors'));
                $contract->set('section_id', JRequest::getInt('section_id'));
                $contract->set('description', JRequest::getVar('description'));

                // storing updated data
                $contract->store();
                clsLog('Contract updated', 'The user updated contract #' . $contract->id . ' data');
            }

            if($this->_task == 'saveContract')
                $this->setRedirect('index.php?option=com_cls&c=contracts', JText::_('Contract successfully saved'));
            elseif($this->_task == 'applyContract')
                $this->setRedirect('index.php?option=com_cls&task=editContract&cid[]='.$id, JText::_('Contract successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function saveSection() {
        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        if($id == 0) { // going to insert new section
            // constructing the section object
            $section = new JTable('#__complaint_sections', 'id', $db);
            $section->set('name', JRequest::getVar('name'));
            $section->set('description', JRequest::getVar('description'));
            $section->set('polyline', JRequest::getVar('polyline'));
            $section->set('polygon', JRequest::getVar('polygone'));
            $section->store();

            // adding notification
            clsLog('New section', 'New section created #' . $db->insertid());

            $this->setRedirect('index.php?option=com_cls&c=sections', JText::_('Section successfully created'));
        } else { // going to update section
            // constructing the section object
            $section = new JTable('#__complaint_sections', 'id', $db);
            $section->set('id', $id);
            $section->set('name', null);
            $section->set('polyline', null);
            $section->set('polygon', null);
            $section->set('description', null);
            $section->load();

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $section->set('name', JRequest::getVar('name'));
                $section->set('description', JRequest::getVar('description'));
                $section->set('polyline', JRequest::getVar('polyline'));
                $section->set('polygon', JRequest::getVar('polygon'));

                // storing updated data
                $section->store();
                clsLog('Section updated', 'The user updated section #' . $section->id . ' data');
            }

            if($this->_task == 'saveSection')
                $this->setRedirect('index.php?option=com_cls&c=sections', JText::_('Section successfully saved'));
            elseif($this->_task == 'applySection')
                $this->setRedirect('index.php?option=com_cls&task=editSection&cid[]='.$id, JText::_('Section successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function saveSupportGroup() {
        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $assigned_users = JRequest::getVar('users', array(), 'default', 'array');
        //echo '<pre>', print_r($assigned_users, true), '</pre>';
        //exit;

        if($id == 0) { // going to insert new support group
            // constructing the section object
            $support_group = new JTable('#__complaint_support_groups', 'id', $db);
            $support_group->set('name', JRequest::getVar('name'));
            $support_group->set('description', JRequest::getVar('description'));
            $support_group->store();

            $group_id = $db->insertid();

            // assign users to support group
            foreach($assigned_users as $user_id) {
                $query = "insert into #__complaint_support_groups_users_map value(null, $group_id, $user_id)";
                $db->setQuery($query);
                $db->query();
            }

            // adding notification
            clsLog('New support group', 'New support group created #' . $group_id);

            $this->setRedirect('index.php?option=com_cls&c=SupportGroups', JText::_('Support Group successfully created'));
        } else { // going to update section
            // constructing the support group object
            $support_group = new JTable('#__complaint_support_groups', 'id', $db);
            $support_group->set('id', $id);
            $support_group->set('name', null);
            $support_group->set('description', null);
            $support_group->load();

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                $support_group->set('name', JRequest::getVar('name'));
                $support_group->set('description', JRequest::getVar('description'));

                // storing updated data
                $support_group->store();

                // delete assigned users
                $query = "delete from #__complaint_support_groups_users_map where group_id = $id";
                $db->setQuery($query);
                $db->query();

                // assign users to support group
                foreach($assigned_users as $user_id) {
                    $query = "insert into #__complaint_support_groups_users_map value(null, $id, $user_id)";
                    $db->setQuery($query);
                    $db->query();
                }

                // adding notification
                clsLog('Support Group updated', 'The user updated Support Group #' . $section->id . ' data');
            }

            if($this->_task == 'saveSupportGroup')
                $this->setRedirect('index.php?option=com_cls&c=SupportGroups', JText::_('Support Group successfully saved'));
            elseif($this->_task == 'applySupportGroup')
                $this->setRedirect('index.php?option=com_cls&task=editSupportGroup&cid[]='.$id, JText::_('Support Group successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));
        }
    }

    function removeComplaint() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        $user_type = $user->getParam('role', 'Guest');

        if($user->getParam('role', 'Guest') == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaints where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Complaint removed', 'The complaint with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls', JText::_('Complaint(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to deleted"));
        }
    }

    function removeContract() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        if($user->getParam('role', 'Guest') == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaint_contracts where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Contract removed', 'The contract with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls&c=contracts', JText::_('Contract(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to deleted"));
        }
    }

    function removeSection() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        if($user->getParam('role', 'Guest') == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaint_sections where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Section removed', 'The section with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls&c=sections', JText::_('Section(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to deleted"));
        }
    }

    function removeSupportGroup() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        if($user->getParam('role', 'Guest') == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaint_support_groups where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();

                $query = "delete from #__complaint_support_groups_users_map where group_id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Support Group removed', 'The support group with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls&c=SupportGroups', JText::_('Support Group(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to deleted"));
        }
    }

    function notifySMSAcknowledge() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        $config =& JComponentHelper::getParams('com_cls');
        $acknowledgment_text = sprintf($config->get('acknowledgment_text'), $complaint->message_id);

        if($user->getParam('role', 'Guest') != 'Guest') {
            $db->setQuery("insert into #__complaint_message_queue values(null, '$id', '$user->username', '$complaint->phone', '$acknowledgment_text', now(), 'Pending', 'Acknowledgment')");
            $db->query() or JError::raiseWarning(0, 'Unable to insert msg into queue');

            clsLog('SMS acknowledgment queued', "SMS acknowledgment queued to be sent to $complaint->phone for complaint #$complaint->message_id");
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('SMS notification will be sent shortly'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifyEmailAcknowledge() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        $config =& JComponentHelper::getParams('com_cls');
        $acknowledgment_text = sprintf($config->get('acknowledgment_text'), $complaint->message_id);

        if($user->getParam('role', 'Guest') != 'Guest') {
            jimport('joomla.mail.mail');
            $mail = new JMail();
            $mail->setSender(array('no_reply@'.$_SERVER['HTTP_HOST'], 'Complaint Logging System'));
            $mail->setSubject('Complaint Received: #'.$complaint->message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML("<p>$acknowledgment_text</p>");
            $mail->AddAddress($complaint->email);
            $mail->Send() or JError::raiseWarning(0, 'Unable to send Email notification');

            clsLog('Email acknowledgment sent', "Email acknowledgment has been sent to $complaint->email for complaint #$complaint->message_id");

            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('Email notification successfully sent'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifySMSResolve() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user->getParam('role', 'Guest') !='Guest') {
            $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, '$user->username', '$complaint->phone', 'Thank you, your complaint #{$complaint->message_id} was resolved. Feel free to send further complaints if any.', now(), 'Resolved')");
            $db->query() or JError::raiseWarning(0, 'Unable to insert msg into queue');

            clsLog('SMS resolution acknowledgment queued', 'Complaint #' . $complaint->message_id . ' SMS notification has been queued to be sent to ' . $complaint->phone . ' number');

            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('SMS notification will be sent shortly'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifyEmailResolve() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user->getParam('role', 'Guest') !='Guest') {
            jimport('joomla.mail.mail');
            $mail = new JMail();
            $mail->setSender(array('no_reply@'.$_SERVER['HTTP_HOST'], 'Complaint Logging System'));
            $mail->setSubject('Complaint Resolved: #'.$complaint->message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML('<p>Thank you, your complaint was resolved. Feel free to send further complaints if any.</p>');
            $mail->AddAddress($complaint->email);
            $mail->Send() or JError::raiseWarning(0, 'Unable to send Email notification');

            clsLog('Email resolution acknowledgment sent', 'Complaint #' . $complaint->message_id . ' email notification has been sent to ' . $complaint->email . ' address');

            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('Email notification successfully sent'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function uploadPicture() {
        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&c=reports', JText::_("You don't have permission"));
            return;
        }

        //import joomlas filesystem functions, we will do all the filewriting with joomlas functions,
        //so if the ftp layer is on, joomla will write with that, not the apache user, which might
        //not have the correct permissions
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');

        //this is the name of the field in the html form, filedata is the default name for swfupload
        //so we will leave it as that
        $fieldName = 'Filedata';

        //any errors the server registered on uploading
        $fileError = $_FILES[$fieldName]['error'];
        if($fileError > 0)  {
            switch ($fileError) {
                case 1:
                    echo JText::_( 'FILE TO LARGE THAN PHP INI ALLOWS' );
                    return;
                case 2:
                    echo JText::_( 'FILE TO LARGE THAN HTML FORM ALLOWS' );
                    return;
                case 3:
                    echo JText::_( 'ERROR PARTIAL UPLOAD' );
                    return;
                case 4:
                   echo JText::_( 'ERROR NO FILE' );
                   return;
            }
        }

        //check for filesize
        $fileSize = $_FILES[$fieldName]['size'];
        if($fileSize > 2000000)
            echo JText::_( 'FILE BIGGER THAN 2MB' );

        //check the file extension is ok
        $fileName = $_FILES[$fieldName]['name'];
        $uploadedFileNameParts = explode('.',$fileName);
        $uploadedFileExtension = array_pop($uploadedFileNameParts);

        $validFileExts = explode(',', 'jpeg,jpg,png,gif');

        //assume the extension is false until we know its ok
        $extOk = false;

        //go through every ok extension, if the ok extension matches the file extension (case insensitive)
        //then the file extension is ok
        foreach($validFileExts as $key => $value)
            if(preg_match("/$value/i", $uploadedFileExtension))
                $extOk = true;

        if($extOk == false) {
            echo JText::_( 'INVALID EXTENSION' );
            return;
        }

        //the name of the file in PHP's temp directory that we are going to move to our folder
        $fileTemp = $_FILES[$fieldName]['tmp_name'];

        //for security purposes, we will also do a getimagesize on the temp file (before we have moved it
        //to the folder) to check the MIME type of the file, and whether it has a width and height
        $imageinfo = getimagesize($fileTemp);

        //we are going to define what file extensions/MIMEs are ok, and only let these ones in (whitelisting), rather than try to scan for bad
        //types, where we might miss one (whitelisting is always better than blacklisting)
        $okMIMETypes = 'image/jpeg,image/pjpeg,image/png,image/x-png,image/gif';
        $validFileTypes = explode(",", $okMIMETypes);

        //if the temp file does not have a width or a height, or it has a non ok MIME, return
        if(!is_int($imageinfo[0]) or !is_int($imageinfo[1]) or  !in_array($imageinfo['mime'], $validFileTypes)) {
            echo JText::_( 'INVALID FILETYPE' );
            return;
        }

        //lose any special characters in the filename
        $fileName = ereg_replace("[^A-Za-z0-9.]", "-", $fileName);

        // generate random filename
        $fileName = uniqid(JRequest::getInt('id').'_') . '-' . $fileName;

        //always use constants when making file paths, to avoid the possibilty of remote file inclusion
        $uploadPath = JPATH_ADMINISTRATOR.'/components/com_cls/pictures/'.$fileName;

        if(!JFile::upload($fileTemp, $uploadPath)) {
            echo JText::_( 'ERROR MOVING FILE' );
            return;
        } else {
            // going to insert the picture into db
            $db =& JFactory::getDBO();
            $user =& JFactory::getUser();
            $user_type = $user->getParam('role', 'Guest');
            $complaint_id = JRequest::getInt('id', 0);
            if($user_type !='Guest') {
                $picture = new JTable('#__complaint_pictures', 'id', $db);
                $picture->set('complaint_id', $complaint_id);
                $picture->set('path', str_replace(JPATH_ADMINISTRATOR.'/', '', $uploadPath));
                $picture->store();
            }

            $db->setQuery('select message_id from #__complaints where id = ' . $complaint_id);
            $complaint = $db->loadObject();
            clsLog('Image uploaded', 'User uploaded an image for Complaint #' . $complaint->message_id);

            // success, exit with code 0 for Mac users, otherwise they receive an IO Error
            exit(0);
        }
    }
}