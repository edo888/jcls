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
        $this->registerTask('download_report', 'downloadReport');
        $this->registerTask('notify_sms_process', 'notifySMSProcess');
        $this->registerTask('notify_email_process', 'notifyEmailProcess');
        $this->registerTask('notify_sms_resolve', 'notifySMSResolve');
        $this->registerTask('notify_email_resolve', 'notifyEmailResolve');
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
        CLSView::editLocation();
    }

    function viewSectionMap() {
        CLSView::viewSectionMap();
    }

    function editSectionMap() {
        CLSView::editSectionMap();
    }

    function showReports() {
        CLSView::showReports();
    }

    function showNotifications() {
        global $mainframe, $option;

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
        $lists['action'] = JHTML::_('select.genericlist', $action, 'filter_user_id', 'onchange=submitform();', 'key', 'value', $filter_action);

        // table ordering
        $lists['order_Dir'] = $filter_order_Dir;
        $lists['order']     = $filter_order;

        // search filter
        $lists['search'] = $search;

        CLSView::showNotifications($rows, $pageNav, $option, $lists);
    }

    function showContracts() {
        global $mainframe, $option;

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

    function downloadReport() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $doc  =& JFactory::getDocument();

        $period = JRequest::getVar('period', 'all');

        $query = 'select c.*, e.name as editor, r.name as resolver, a.area as complaint_area from #__complaints as c left join #__complaint_areas as a on (c.complaint_area_id = a.id) left join #__users as e on (c.editor_id = e.id) left join #__users as r on (c.resolver_id = r.id)';

        switch($period) {
            case 'month': $query .= ' where date_received >= DATE_ADD(now(), interval -1 month)'; break;
            case 'current_month': $query .= " where date_received >= '" . date("Y-m-01") . "'"; break;
            case 'prev_month': $query .= " where date_received < '" . date("Y-m-01") . "' and date_received >= DATE_ADD('".date("Y-m-01")."', interval -1 month)"; break;
            default: break;
        }

        $db->setQuery($query);
        $complaints = $db->loadObjectList();

        $tmp_file = tempnam(JPATH_ROOT.'/dmdocuments', 'cls');
        $fh = fopen($tmp_file, 'w') or die('cannot open file for writing');
        fputcsv($fh, array('MessageID', 'Name', 'Email', 'Tel', 'Address', 'Sender IP', 'Message Source', 'Message Priority', 'Complaint Area', 'Editor', 'Resolver', 'Resolution', 'Resolved and Closed', 'Raw Message', 'Processed Message', 'Comments')) or die('cannot write');
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
        $user_type = $user->getParam('role', 'Viewer');

        if($this->_task == 'edit') {
            $cid = JRequest::getVar('cid', array(0), 'method', 'array');
            $cid = array((int) $cid[0]);
        } else {
            $cid = array( 0 );
        }

        $query = 'select c.*, e.name as editor, r.name as resolver, a.area as complaint_area, p.name as contract from #__complaints as c left join #__complaint_areas as a on (c.complaint_area_id = a.id) left join #__users as e on (c.editor_id = e.id) left join #__users as r on (c.resolver_id = r.id) left join #__complaint_contracts as p on (c.contract_id = p.id) where c.id = ' . $cid[0];
        $db->setQuery($query);
        $row = $db->loadObject();

        // area_id list
        $query = 'select * from #__complaint_areas';
        $db->setQuery($query);
        $areas = $db->loadObjectList();
        $area[] = array('key' => '', 'value' => '- Select Area -');
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
        $query = 'select * from #__users where params like "%role=Auditor%" or params like "%role=Admin%" or params like "%role=Super User%"';
        $db->setQuery($query);
        $editors = $db->loadObjectList();
        $editor[] = array('key' => '', 'value' => '- Select Editor -');
        foreach($editors as $e)
            $editor[] = array('key' => $e->id, 'value' => $e->name);
        $lists['editor'] = JHTML::_('select.genericlist', $editor, 'editor_id', null, 'key', 'value', $row->editor_id);

        // resolver list
        $query = 'select * from #__users where params like "%role=Resolver%" or params like "%role=Admin%" or params like "%role=Super User%"';
        $db->setQuery($query);
        $resolvers = $db->loadObjectList();
        $resolver[] = array('key' => '', 'value' => '- Select Resolver -');
        foreach($resolvers as $r)
            $resolver[] = array('key' => $r->id, 'value' => $r->name);
        $lists['resolver'] = JHTML::_('select.genericlist', $resolver, 'resolver_id', null, 'key', 'value', $row->resolver_id);

        // source list
        $lists['source'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Source -' ), array('key' => 'SMS', 'value' => 'SMS'), array('key' => 'Email', 'value' => 'Email'), array('key' => 'Website', 'value' => 'Website'), array('key' => 'Telephone Call', 'value' => 'Telephone Call'), array('key' => 'Personal Visit', 'value' => 'Personal Visit'), array('key' => 'Field Visit by Project Staff', 'value' => 'Field Visit by Project Staff'), array('key' => 'Other', 'value' => 'Other')), 'message_source', null, 'key', 'value', $row->message_source);

        // priority list
        $lists['priority'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Priority -' ), array('key' => 'Low', 'value' => 'Low'), array('key' => 'Medium', 'value' => 'Medium'), array('key' => 'High', 'value' => 'High')), 'message_priority', null, 'key', 'value', $row->message_priority);

        // confirmed_closed list
        $lists['confirmed'] = JHTML::_('select.genericlist', array(array('key' => '', 'value' => '- Select Confirmation -' ), array('key' => 'Y', 'value' => 'Yes'), array('key' => 'N', 'value' => 'No')), 'confirmed_closed', null, 'key', 'value', $row->confirmed_closed);

        CLSView::editComplaint($row, $lists, $user_type);
    }

    function editContract() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Viewer');

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
        $user_type = $user->getParam('role', 'Viewer');

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

    function saveComplaint() {
        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Viewer');
        $id = JRequest::getInt('id', 0);

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
            $complaint->set('editor_id', null);
            $complaint->set('raw_message', null);
            $complaint->set('processed_message', null);
            $complaint->set('contract_id', null);
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
            $complaint->set('comments', null);
            $complaint->load();

            if($user_type == 'Super User') {
            }

            if($user_type == 'Super User' or $user_type == 'Administrator') {
                $complaint->set('name', JRequest::getVar('name'));
                $complaint->set('email', JRequest::getVar('email'));
                $complaint->set('phone', JRequest::getVar('phone'));
                $complaint->set('address', JRequest::getVar('address'));
                $complaint->set('ip_address', JRequest::getVar('ip_address'));
                $complaint->set('raw_message', JRequest::getVar('raw_message'));
                $complaint->set('message_source', JRequest::getVar('message_source'));
                $complaint->set('confirmed_closed', JRequest::getVar('confirmed_closed'));
                $complaint->set('editor_id', JRequest::getInt('editor_id'));
                $complaint->set('resolver_id', JRequest::getInt('resolver_id'));
            }

            if($user_type == 'Super User' or $user_type == 'Administrator' or $user_type == 'Auditor') {
                if($user_type == 'Auditor')
                    $complaint->set('editor_id', $user->id);
                $complaint->set('message_priority', JRequest::getVar('message_priority'));
                $complaint->set('complaint_area_id', JRequest::getInt('complaint_area_id'));
                $complaint->set('processed_message', JRequest::getVar('processed_message'));
                $complaint->set('contract_id', JRequest::getInt('contract_id'));
                if(JRequest::getVar('location') != '')
                    $complaint->set('location', JRequest::getVar('location'));
                if($complaint->date_processed == '' and $complaint->processed_message != '') {
                    $complaint->set('date_processed', date('Y-m-d H:i:s'));

                    clsLog('Complaint processed', 'The user processed the complaint #' . $complaint->message_id);

                    // Send processed complaint to members
                    $config =& JComponentHelper::getParams('com_cls');
                    $processed_message_send_count = (int) $config->get('processed_message_send_count', 3);

                    $db->setQuery("select email, name, rand() as r from #__users where params like '%receive_processed_messages=1%' order by r limit $processed_message_send_count");
                    $res = $db->query();

                    jimport('joomla.mail.mail');
                    $mail = new JMail();
                    $mail->From = 'complaints@lrip.am';
                    $mail->FromName = 'Complaint Logging System';
                    $mail->Subject = 'New Processed Complaint: #' . $complaint->message_id;
                    $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
                    $mail->msgHTML('<p>A complaint was processed. Login to http://www.lrip.am/administrator/index.php?option=com_cls to resolve it.</p>' . $complaint->processed_message);
                    $mail->AddReplyTo('no_reply@lrip.am');
                    while($row = mysql_fetch_array($res, MYSQL_NUM)) {
                        $mail->AddAddress($row[0]);
                        clsLog('Processed notification send', 'Complaint #' . $complaint->message_id . ' processed notification send to ' . $row[1]);
                    }
                    $mail->Send();
                }
            }

            if($user_type == 'Super User' or $user_type == 'Administrator' or $user_type == 'Resolver') {
                if($user_type == 'Resolver')
                    $complaint->set('resolver_id', $user->id);
                $complaint->set('resolution', JRequest::getVar('resolution'));
                if($complaint->date_resolved == '' and $complaint->resolution != '') {
                    $complaint->set('date_resolved', date('Y-m-d H:i:s'));
                    clsLog('Complaint resolved', 'The user resolved the complaint #' . $complaint->message_id);
                }
            }

            if($user_type != 'Viewer') {
                if(JRequest::getVar('comments', '') != '') { // append comment
                    $complaint->set('comments', $complaint->comments . 'On ' . date('Y-m-d H:i:s') . ' ' . $user->name . " wrote:\n" . JRequest::getVar('comments') . "\n\n");
                    clsLog('Complaint comment added', 'The user added a follow up comment on the complaint #' . $complaint->message_id);
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
        $user_type = $user->getParam('role', 'Viewer');
        $id = JRequest::getInt('id', 0);

        if($id == 0) { // going to insert new contract
            // constructing the contract object
            $contract = new JTable('#__complaint_contracts', 'id', $db);
            $contract->set('name', JRequest::getVar('name'));
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
            $contract->set('section_id', null);
            $contract->set('description', null);
            $contract->load();

            if($user_type == 'Super User' or $user_type == 'Administrator') {
                $contract->set('name', JRequest::getVar('name'));
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
        $user_type = $user->getParam('role', 'Viewer');
        $id = JRequest::getInt('id', 0);

        if($id == 0) { // going to insert new section
            // constructing the section object
            $section = new JTable('#__complaint_sections', 'id', $db);
            $section->set('name', JRequest::getVar('name'));
            $section->set('description', JRequest::getVar('description'));
            // TODO: polyline and polygon are missing
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

            if($user_type == 'Super User' or $user_type == 'Administrator') {
                $section->set('name', JRequest::getVar('name'));
                $section->set('description', JRequest::getVar('description'));

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

    function removeComplaint() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        if($user->getParam('role', 'Viewer') == 'Super User') {
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

        if($user->getParam('role', 'Viewer') == 'Super User') {
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

        if($user->getParam('role', 'Viewer') == 'Super User') {
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

    function notifySMSProcess() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user->getParam('role', 'Viewer') != 'Viewer') {
            $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, '$user->username', '$complaint->phone', 'Thank you, your complaint #{$complaint->message_id} was processed, we will contact you soon.', now(), 'Processed')");
            $db->query() or JError::raiseWarning(0, 'Unable to insert msg into queue');
            clsLog('Processed notification SMSed', 'Complaint #' . $complaint->message_id . ' SMS notification has been queued to be sent to ' . $complaint->phone . ' number');
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('SMS notification will be sent shortly'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifyEmailProcess() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user->getParam('role', 'Viewer') != 'Viewer') {
            jimport('joomla.mail.mail');
            $mail = new JMail();
            $mail->setSender(array('no_reply@lrip.am', 'Complaint Logging System'));
            $mail->setSubject('Complaint Processed: #'.$complaint->message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML('<p>Thank you, your complaint was processed, we will contact you soon.</p>');
            $mail->AddAddress($complaint->email);
            $mail->Send() or JError::raiseWarning(0, 'Unable to send Email notification');

            clsLog('Processed notification emailed', 'Complaint #' . $complaint->message_id . ' email notification has been sent to ' . $complaint->email . ' address');

            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('Email notification successfully sent'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifySMSResolve() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user->getParam('role', 'Viewer') != 'Viewer') {
            $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, '$user->username', '$complaint->phone', 'Thank you, your complaint #{$complaint->message_id} was resolved. Feel free to send further complaints if any.', now(), 'Resolved')");
            $db->query() or JError::raiseWarning(0, 'Unable to insert msg into queue');

            clsLog('Resolved notification SMSed', 'Complaint #' . $complaint->message_id . ' SMS notification has been queued to be sent to ' . $complaint->phone . ' number');

            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('SMS notification will be sent shortly'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifyEmailResolve() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user->getParam('role', 'Viewer') != 'Viewer') {
            jimport('joomla.mail.mail');
            $mail = new JMail();
            $mail->setSender(array('no_reply@lrip.am', 'Complaint Logging System'));
            $mail->setSubject('Complaint Resolved: #'.$complaint->message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML('<p>Thank you, your complaint was resolved. Feel free to send further complaints if any.</p>');
            $mail->AddAddress($complaint->email);
            $mail->Send() or JError::raiseWarning(0, 'Unable to send Email notification');

            clsLog('Resolved notification emailed', 'Complaint #' . $complaint->message_id . ' email notification has been sent to ' . $complaint->email . ' address');

            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('Email notification successfully sent'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=edit&cid[]='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }
}

class CLSView {
    function showComplaints($rows, $pageNav, $options, $lists) {
        $user = & JFactory::getUser();

        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls', true);
        JSubMenuHelper::addEntry(JText::_('Reports'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Notifications'), 'index.php?option=com_cls&c=notifications');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');

        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');
        $raw_complaint_warning_period = (int) $config->get('raw_complaint_warning_period', 2);
        $processed_complaint_warning_period = (int) $config->get('processed_complaint_warning_period', 4);

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                    <?php echo $lists['area']; ?>
                    <?php echo $lists['contract']; ?>
                    <?php echo $lists['source']; ?>
                    <?php echo $lists['priority']; ?>
                    <?php echo $lists['status']; ?>
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="6%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Message ID', 'm.message_id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Source', 'm.message_source', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Sender', 'sender', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Received', 'm.date_received', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Area', 'g.area', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Priority', 'm.message_priority', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Processed', 'm.date_processed', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Editor', 'e.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Resolved', 'm.date_resolved', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Resolver', 'u.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                if($row->date_processed == '' and $raw_complaint_warning_period*24*60*60 < time() - strtotime($row->date_received))
                    JError::raiseNotice(0, 'Complaint #' . $row->message_id . ' is not processed yet.');
                if($row->confirmed_closed == 'N' and $row->date_processed != '' and $processed_complaint_warning_period*24*60*60 < time() - strtotime($row->date_processed))
                    JError::raiseNotice(0, 'Complaint #' . $row->message_id . ' is not resolved yet.');

                $link        = JRoute::_('index.php?option=com_cls&task=edit&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Complaint' ); ?>">
                            <?php echo $row->message_id; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->message_source; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->sender; ?>
                    </td>
                    <td align="center">
                        <?php echo date('Y-m-d', strtotime($row->date_received)); ?>
                    </td>
                    <td align="center">
                        <?php echo $row->area; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->message_priority; ?>
                    </td>
                    <td align="center">
                        <?php
                        if($row->date_processed)
                            echo date('Y-m-d', strtotime($row->date_processed));
                        ?>
                    </td>
                    <td align="center">
                        <?php echo $row->editor; ?>
                    </td>
                    <td align="center">
                        <?php
                        if($row->date_resolved)
                            echo date('Y-m-d', strtotime($row->date_resolved));
                        ?>
                    </td>
                    <td align="center">
                        <?php echo $row->resolver; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showReports() {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Reports'), 'index.php?option=com_cls&c=reports', true);
        JSubMenuHelper::addEntry(JText::_('Notifications'), 'index.php?option=com_cls&c=notifications');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');

        $db =& JFactory::getDBO();
        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');
        $statistics_period = (int) $config->get('statistics_period', 20);
        $statistics_period_compare = (int) $config->get('statistics_period_compare', 5);
        $delayed_resolution_period = (int) $config->get('delayed_resolution_period', 30);

        # -- Complaints Downloads --
        echo '<h3>Complaints Downloads</h3>';
        echo '<a href="index.php?option=com_cls&amp;task=download_report&period=current_month">Download Current Month</a><br />';
        echo '<a href="index.php?option=com_cls&amp;task=download_report&period=prev_month">Download Previous Month</a><br />';
        //echo '<a href="index.php?option=com_cls&amp;task=download_report&period=month">Download Month</a><br />';
        echo '<a href="index.php?option=com_cls&amp;task=download_report&period=all">Download All</a>';
        # -- End Complaints Downloads --

        # -- Complaints Averages --
        $db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints_received = $db->loadResult();
        $complaints_received_per_day = round($complaints_received/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where date_processed >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints_processed = $db->loadResult();
        $complaints_processed_per_day = round($complaints_processed/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where date_resolved >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints_resolved = $db->loadResult();
        $complaints_resolved_per_day = round($complaints_resolved/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N' and date_processed >= DATE_ADD(now(), interval -$statistics_period day) and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= now()");
        $complaints_delayed = $db->loadResult();

        $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_received >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_received <= DATE_ADD(now(), interval -$statistics_period_compare day)");
        $complaints_received_per_day2 = $db->loadResult();
        $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_processed >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_processed <= DATE_ADD(now(), interval -$statistics_period_compare day)");
        $complaints_processed_per_day2 = $db->loadResult();
        $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_resolved >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_resolved <= DATE_ADD(now(), interval -$statistics_period_compare day)");
        $complaints_resolved_per_day2 = $db->loadResult();

        $complaints_received_growth = ($complaints_received_per_day >= $complaints_received_per_day2 ? '+' : '-') . round(abs($complaints_received_per_day - $complaints_received_per_day2)/$complaints_received_per_day*100, 2) . '%';
        $complaints_processed_growth = ($complaints_processed_per_day >= $complaints_processed_per_day2 ? '+' : '-') . round(abs($complaints_processed_per_day - $complaints_processed_per_day2)/$complaints_processed_per_day*100, 2) . '%';
        $complaints_resolved_growth = ($complaints_resolved_per_day >= $complaints_resolved_per_day2 ? '+' : '-') . round(abs($complaints_resolved_per_day - $complaints_resolved_per_day2)/$complaints_resolved_per_day*100, 2) . '%';

        echo '<h3>Summary of Complaints</h3>';
        echo '<i>Complaints Received Per Day:</i> ' . $complaints_received_per_day . ' <small style="color:#cc0000;">' . $complaints_received_growth . '</small><br />';
        echo '<i>Complaints Processed Per Day:</i> ' . $complaints_processed_per_day . ' <small style="color:#cc0000;">' . $complaints_processed_growth . '</small><br />';
        echo '<i>Complaints Resolved Per Day:</i> ' . $complaints_resolved_per_day . ' <small style="color:#cc0000;">' . $complaints_resolved_growth . '</small><br />';
        echo '<i>Number of Complaints Received:</i> ' . $complaints_received . ' <br />';
        echo '<i>Number of Complaints Resolved:</i> ' . $complaints_resolved . ' <br />';
        echo '<i>Number of Complaints Outstanding:</i> ' . ($complaints_received - $complaints_resolved < 0 ? 0 : $complaints_received - $complaints_resolved) . ' <br />';
        echo '<i>Number of With Delayed Resolution:</i> ' . $complaints_delayed . ' <br />';

        echo '<br /><small><i>The averages are based on ' . $statistics_period . ' days period data.</i></small>';
        # -- End Complaints Averages --

        # -- Complaints Statistics --
        for($i = 0, $time = strtotime("-$statistics_period days"); $time < time() + 86400; $i++, $time = strtotime("-$statistics_period days +$i days"))
            $dates[date('M j', $time)] = 0;
        //echo '<pre>', print_r($dates, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_received, '%b %e') as date from #__complaints where date_received >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_received");
        $received = $db->loadObjectList();
        $complaints_received = $dates;
        foreach($received as $complaint)
            $complaints_received[$complaint->date] = $complaint->count;
        //echo '<pre>', print_r($complaints_received, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_processed, '%b %e') as date from #__complaints where date_processed >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_processed");
        $processed = $db->loadObjectList();
        $complaints_processed = $dates;
        foreach($processed as $complaint)
            $complaints_processed[$complaint->date] = $complaint->count;
        //echo '<pre>', print_r($complaints_processed, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_resolved, '%b %e') as date from #__complaints where date_resolved >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_resolved");
        $resolved = $db->loadObjectList();
        $complaints_resolved = $dates;
        foreach($resolved as $complaint)
            $complaints_resolved[$complaint->date] = $complaint->count;
        //echo '<pre>', print_r($complaints_resolved, true), '</pre>';

        for($i = 0, $time = strtotime("-$statistics_period days"); $time < time() + 86400; $i++, $time = strtotime("-$statistics_period days +$i days")) {
            $date = date('Y-m-d', $time);
            $key = date('M j', $time);
            $db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD('$date', interval -" . ($delayed_resolution_period + $statistics_period) . " day)and date_received <= '$date' and ((date_resolved is not null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= date_resolved) or (date_resolved is null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= '$date'))");
            $delayed_resolution[$key] = $db->loadResult();
        }
        //echo '<pre>', print_r($delayed_resolution, true), '</pre>';

        $max = max(max($complaints_received), max($complaints_processed), max($complaints_resolved), max($delayed_resolution));
        $max = ceil($max/5)*5;
        //echo 'Max: ', $max;

        $x_axis  = implode('|', array_keys($dates));
        $y_axis  = implode('|', range(0, $max, $max/5));

        $complaints_per_day_link  = "http://chart.apis.google.com/chart?chs=900x330&amp;";
        $complaints_per_day_link .= "cht=lc&amp;";
        $complaints_per_day_link .= "chdl=Complaints Received|Complaints Processed|Complaints Resolved|Delayed Resolution&amp;";
        $complaints_per_day_link .= "chdlp=b&amp;";
        $complaints_per_day_link .= "chco=000080FF,008000FF,808000FF,808080FF&amp;";
        $complaints_per_day_link .= "chxt=x,y&amp;";
        $complaints_per_day_link .= "chxl=0:|".$x_axis."|1:|".$y_axis."&amp;";
        $complaints_per_day_link .= "chd=s:".self::simpleEncode($complaints_received, 0, $max).",".self::simpleEncode($complaints_processed, 0, $max).",".self::simpleEncode($complaints_resolved, 0, $max).",".self::simpleEncode($delayed_resolution, 0, $max);

        echo '<h3>Complaints Statistics</h3>';
        echo '<img src="' . $complaints_per_day_link . '" alt="complaints statistics" />';
        # -- End Complaints Statistics --

        # -- Complaints Map --
        echo '<h3>Complaints Map</h3>';
        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);
        $db->setQuery("select * from #__complaints where location != '' and date_received >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints = $db->loadObjectList();
        ?>
        <div id="map" style="width:900px;height:500px;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();

            <?php
            foreach($complaints as $complaint) {
                echo 'var point = new GLatLng('.$complaint->location.');';
                echo 'var marker = new GMarker(point, {icon: G_DEFAULT_ICON, draggable: false});';
                echo 'map.addOverlay(marker);';
                echo 'marker.bindInfoWindowHtml(\'<b>#'.$complaint->message_id.'</b><br/><i>Status:</i> ' . ($complaint->confirmed_closed == 'Y' ? 'Resolved' : 'Open') . '<p>'.addslashes($complaint->processed_message).'</p>\');';
            }
            ?>
        //]]>
        </script>
        <?php
        # -- End Complaints Map --
    }

    function showNotifications($rows, $pageNav, $options, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Reports'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Notifications'), 'index.php?option=com_cls&c=notifications', true);
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');

        JHTML::_('behavior.tooltip');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                    <?php echo $lists['user_id']; ?>
                    <?php echo $lists['action']; ?>
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JHTML::_('grid.sort', 'User', 'u.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Action', 'm.action', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Date', 'm.date', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="68%" align="left">Description</th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php if($row->user_id == 0) echo 'System'; else echo $row->user; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->action; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->date; ?>
                    </td>
                    <td align="left">
                        <?php echo $row->description; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="notifications" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showContracts($rows, $pageNav, $option, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Reports'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Notifications'), 'index.php?option=com_cls&c=notifications');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts', true);
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');
        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                    <?php echo $lists['section']; ?>
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Name', 'm.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="77%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Section', 's.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=editContract&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Contract' ); ?>">
                            <?php echo $row->name; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->section_name; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="contracts" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showSections($rows, $pageNav, $option, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Reports'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Notifications'), 'index.php?option=com_cls&c=notifications');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections', true);
        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Name', 'm.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="77%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Description', 'm.description', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=editSection&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Section' ); ?>">
                            <?php echo $row->name; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->description; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="sections" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    /**
     * Simple encodeing
     * @param $values array of integers
     * @param $min min value to scale
     * @param $max max value to scale
     */
    function simpleEncode($values, $min, $max) {
        $simple_table = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $chardata = '';
        $delta = $max - $min;
        $size = strlen($simple_table) - 1;
        foreach($values as $k => $v)
                if($v >= $min && $v <= $max)
                        $chardata .= $simple_table[round($size * ($v - $min) / $delta)];
                else
                        $chardata .= '_';
        return $chardata;
    }

    function editComplaint($row, $lists, $user_type) {
        //TODO: Make sure the user is authorized to view this page
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancel') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.message_source && form.message_source.value == "")
                alert('Message Source is required');
            else if(form.name.value == "" && form.email.value == "" && form.phone.value == "" && form.address.value == "" && form.ip_address.value == "")
                alert('Sender is required');
            else if(form.raw_message && form.raw_message.value == "")
                alert('Raw message is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <?php if(property_exists($row, 'message_id')): ?>
            <tr>
                <td width="200" class="key">
                    <label for="title">
                        <?php echo JText::_( 'Message ID' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->message_id; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Message Source' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User')
                        echo @$row->message_source;
                    else
                        echo $lists['source'];
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User')
                        echo @$row->name;
                    else
                        echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @$row->name, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Email' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User')
                        echo @$row->email;
                    else
                        echo '<input class="inputbox" type="text" name="email" id="email" size="60" value="', @$row->email, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Phone' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User')
                        echo @$row->phone;
                    else
                        echo '<input class="inputbox" type="text" name="phone" id="phone" size="60" value="', @$row->phone, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Address' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User')
                        echo @$row->address;
                    else
                        echo '<input class="inputbox" type="text" name="address" id="address" size="60" value="', @$row->address, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Sender IP' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User')
                        echo @$row->ip_address;
                    else
                        echo '<input class="inputbox" type="text" name="ip_address" id="ip_address" size="60" value="', @$row->ip_address, '" />';
                    ?>
                </td>
            </tr>
            <?php if(property_exists($row, 'date_received')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Date Received' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_received; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Raw Message' ); ?>
                    </label>
                </td>
                <td>
                        <?php
                        if($user_type != 'Super User')
                            echo '<pre>', @$row->raw_message, '</pre>';
                        else
                            echo '<textarea name="raw_message" id="raw_message" cols="80" rows="5">', @$row->raw_message, '</textarea>';
                        ?>
                </td>
            </tr>
            <?php if(isset($row->date_processed)): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Date Processed' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_processed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'processed_message')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Processed Message' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator' and $user_type != 'Auditor')
                        echo '<pre>', @$row->processed_message, '</pre>';
                    else
                        echo '<textarea name="processed_message" id="processed_message" cols="80" rows="5">', @$row->processed_message, '</textarea>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'contract_id')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Contract' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator')
                        echo @$row->contract;
                    else
                        echo $lists['contract'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'location')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Location' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator' and $user_type != 'Auditor')
                        echo '<a href="index.php?option=com_cls&c=view_location&ll=' . @$row->location . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">View Map</a>';
                    else
                        echo '<input type="hidden" name="location" id="location" value="', @$row->location, '" /><a href="index.php?option=com_cls&c=edit_location&ll=' . @$row->location . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">'.( empty($row->location) ? 'Add Location' : 'Edit Location' ).'</a>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'editor_id')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Editor' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator')
                        echo @$row->editor;
                    else
                        echo $lists['editor'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'complaint_area_id')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Complaint Area' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator' and $user_type != 'Auditor')
                        echo @$row->complaint_area;
                    else
                        echo $lists['area'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'message_priority')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Message Priority' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator' and $user_type != 'Auditor')
                        echo @$row->message_priority;
                    else
                        echo $lists['priority'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(isset($row->date_resolved)): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Date Resolved' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_resolved; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'confirmed_closed')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Resolved and Closed' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator')
                        echo @$row->confirmed_closed;
                    else
                        echo $lists['confirmed'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'resolution')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="custom_script">
                        <?php echo JText::_( 'Resolution' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator' and $user_type != 'Auditor')
                        echo @$row->resolution;
                    else
                        echo '<textarea name="resolution" id="resolution" cols="80" rows="3">', @$row->resolution, '</textarea>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'resolver_id')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Resolver' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($user_type != 'Super User' and $user_type != 'Administrator')
                        echo @$row->resolver;
                    else
                        echo $lists['resolver'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'comments')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="custom_script">
                        <?php echo JText::_( 'Comments' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        if(isset($row->comments))
                            echo '<pre>', $row->comments, '</pre>';
                        if($user_type != 'Viewer') {
                            echo JText::_('Add your comment here'), ':<br />';
                            echo '<textarea name="comments" id="comments" cols="80" rows="3"></textarea>';
                        }
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>

        <?php if(isset($row->id)): ?>
        <form action="index.php" method="post" name="notificationForm">
        <fieldset class="adminform">
            <legend><?php echo JText::_('Notifications'); ?></legend>

            <p><i>Save your changes before sending a notification.</i></p>

            <table class="admintable">
            <?php if($row->phone != '' and $row->date_processed != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send message proccess SMS notification' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =& JFactory::getDBO();
                    $db->setQuery("select status from #__complaint_message_queue where complaint_id = $row->id and msg_type = 'Processed'");
                    $status = $db->loadResult();
                    if($status == '')
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_process\';document.notificationForm.submit();">Click here</a>';
                    elseif($status == 'Pending' or $status == 'Outgoing')
                        echo JText::_('Message is in the queue');
                    elseif($status == 'Sent')
                        echo JText::_('Message is sent');
                    elseif($status == 'Failed')
                        echo 'Failed to send. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_process\';document.notificationForm.submit();">Click here</a> to try again.';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->email != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send message proccess Email notification' ); ?>
                    </label>
                </td>
                <td>
                    <a href="javascript:void(0);" onclick="document.notificationForm.task.value='notify_email_process';document.notificationForm.submit();">Click here</a>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->phone != '' and $row->date_resolved != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send resolution SMS notification' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =& JFactory::getDBO();
                    $db->setQuery("select status from #__complaint_message_queue where complaint_id = $row->id and msg_type = 'Resolved'");
                    $status = $db->loadResult();
                    if($status == '')
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_resolve\';document.notificationForm.submit();">Click here</a>';
                    elseif($status == 'Pending' or $status == 'Outgoing')
                        echo JText::_('Message is in the queue');
                    elseif($status == 'Sent')
                        echo JText::_('Message is sent');
                    elseif($status == 'Failed')
                        echo 'Failed to send. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_resolve\';document.notificationForm.submit();">Click here</a> to try again.';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->email != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send resolution Email notification' ); ?>
                    </label>
                </td>
                <td>
                    <a href="javascript:void(0);" onclick="document.notificationForm.task.value='notify_email_resolve';document.notificationForm.submit();">Click here</a>
                </td>
            </tr>
            <?php endif; ?>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        </form>
        <?php endif; ?>
    <?php
    }

    function editContract($row, $lists, $user_type) {
        //TODO: Make sure the user is authorized to view this page
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancelContract') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.name && form.name.value == "")
                alert('Name is required');
            else if(form.section_id && form.section_id.value == "")
                alert('Section is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @$row->name, '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Section' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo $lists['section']; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Description' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @$row->description, '</textarea>'; ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>
    <?php
    }

    function editSection($row, $lists, $user_type) {
        //TODO: Make sure the user is authorized to view this page
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancelSection') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.name && form.name.value == "")
                alert('Name is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @$row->name, '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Description' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @$row->description, '</textarea>'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Tag on the map' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        if($user_type != 'Super User' and $user_type != 'Administrator')
                            echo '<a href="index.php?option=com_cls&c=view_section_map&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">View Map</a>';
                        else
                            echo '<input type="hidden" name="polygon" id="polygon" value="', @$row->polygon, '" /><input type="hidden" name="polyline" id="polyline" value="', @$row->polyline, '" /><a href="index.php?option=com_cls&c=edit_section_map&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">'.( (empty($row->polygon) and empty($row->polyline)) ? 'Add a tag' : 'Edit the tag' ).'</a>';
                    ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>
    <?php
    }

    function viewLocation() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);
        ?>
        <div id="map" style="width:100%;height:100%;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            var myLatlng = new GLatLng(<?php echo JRequest::getVar('ll'); ?>);
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();
            var point = new GLatLng(<?php echo JRequest::getVar('ll'); ?>);
            var markerD2 = new GMarker(point, {icon:G_DEFAULT_ICON, draggable: false});
            map.addOverlay(markerD2);
        //]]>
        </script>
        <?php
    }

    function editLocation() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);
        ?>
        <div id="map" style="width:100%;height:100%;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            <?php if(JRequest::getVar('ll') != ''): ?>
            var myLatlng = new GLatLng(<?php echo JRequest::getVar('ll'); ?>);
            <?php else: ?>
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            <?php endif; ?>
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();

            <?php if(JRequest::getVar('ll') != ''): ?>
            var point = new GLatLng(<?php echo JRequest::getVar('ll'); ?>);
            <?php else: ?>
            var point = new GLatLng(<?php echo $center_map; ?>);
            <?php endif; ?>
            var markerD2 = new GMarker(point, {icon:G_DEFAULT_ICON, draggable: true});
            map.addOverlay(markerD2);
            markerD2.enableDragging();
            GEvent.addListener(markerD2, "drag", function(){
                window.parent.document.getElementById("location").value = markerD2.getPoint().toUrlValue();
            });
        //]]>
        </script>
        <?php
    }

    function viewSectionMap() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);

        $db =& JFactory::getDBO();
        $db->setQuery('select polyline, polygon from #__complaint_sections where id = ' . JRequest::getInt('id', 0));
        $row = $db->loadObject();
        $polyline = empty($row->polyline) ? array() : explode(';', $row->polyline);
        $polygon  = empty($row->polygon)  ? array() : explode(';', $row->polygon);
        ?>
        <div id="map" style="width:100%;height:100%;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();
            <?php if(count($polyline)): ?>
            var polyline = new GPolyline([
                <?php
                foreach($polyline as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                ?>
            ], "#aa5555", 5);
            map.addOverlay(polyline);
            <?php endif; ?>
            <?php if(count($polygon)): ?>
            var polygon = new GPolygon([
                <?php
                foreach($polygon as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                ?>
            ], "#f33f00", 5, 1, "#ff0000", 0.2);
            map.addOverlay(polygon);
            <?php endif; ?>
        //]]>
        </script>
        <?php
    }

    function editSectionMap() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);

        $db =& JFactory::getDBO();
        $db->setQuery('select polyline, polygon from #__complaint_sections where id = ' . JRequest::getInt('id', 0));
        $row = $db->loadObject();
        $polyline = empty($row->polyline) ? array() : explode(';', $row->polyline);
        $polygon  = empty($row->polygon)  ? array() : explode(';', $row->polygon);
        ?>
        <div style="width:100%;height:100%;">
            <div id="controls" style="height:5%;">Draw Line Draw Polygon</div>
            <div id="map" style="width:100%;height:95%;"></div>
        </div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();
            <?php if(count($polyline)): ?>
            var polyline = new GPolyline([
                <?php
                foreach($polyline as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                ?>
            ], "#aa5555", 5);
            map.addOverlay(polyline);
            <?php endif; ?>
            <?php if(count($polygon)): ?>
            var polygon = new GPolygon([
                <?php
                foreach($polygon as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                ?>
            ], "#f33f00", 5, 1, "#ff0000", 0.2);
            map.addOverlay(polygon);
            <?php endif; ?>
        //]]>
        </script>
        <?php
    }
}

function clsLog($action, $description) {
    $db   =& JFactory::getDBO();
    $user =& JFactory::getUser();
    $description = mysql_real_escape_string($description);
    $db->setQuery("insert into #__complaint_notifications values(null, {$user->id}, '$action', now(), '$description')");
    $db->query();
}


switch(JRequest::getCmd('c', 'complaints')) {
    case 'notifications': $controller = new CLSController(array('default_task' => 'showNotifications')); break;
    case 'reports': $controller = new CLSController(array('default_task' => 'showReports')); break;
    case 'complaints': $controller = new CLSController(array('default_task' => 'showComplaints')); break;
    case 'view_location': $controller = new CLSController(array('default_task' => 'viewLocation')); break;
    case 'edit_location': $controller = new CLSController(array('default_task' => 'editLocation')); break;
    case 'view_section_map': $controller = new CLSController(array('default_task' => 'viewSectionMap')); break;
    case 'edit_section_map': $controller = new CLSController(array('default_task' => 'editSectionMap')); break;
    case 'contracts': $controller = new CLSController(array('default_task' => 'showContracts')); break;
    case 'sections': $controller = new CLSController(array('default_task' => 'showSections')); break;
    default: $controller = new CLSController(array('default_task' => 'showComplaints')); break;
}

$task = JRequest::getVar('task');
$controller->execute(JRequest::getVar('task'));
$controller->redirect();