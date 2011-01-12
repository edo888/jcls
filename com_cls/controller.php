<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

// TODO: authorize

class CLSController extends JController {
    function __construct($default = array()) {
        parent::__construct($default);
        $this->registerTask('download_report', 'downloadReport');
        $this->registerTask('submit', 'submitComplaint');
        $this->registerTask('newcomplaint', 'newComplaint');
        $this->registerTask('edit', 'editComplaint');
        $this->registerTask('save', 'saveComplaint');
    }

    function display() {
        $view = JRequest::getVar('view');
        parent::display(true);
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

    function submitComplaint() {
        // check captcha
        $session =& JFactory::getSession();

        if($session->get('cls_captcha') != strtoupper(JRequest::getVar('captcha'))) {
            $session->set('cls_name', JRequest::getVar('name'));
            $session->set('cls_email', JRequest::getVar('email'));
            $session->set('cls_tel', JRequest::getVar('tel'));
            $session->set('cls_msg', JRequest::getVar('msg'));
            $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('COMPLAINT_FORM_INVALID_CAPTCHA'));
            return;
        } else {
            $session->set('cls_msg', '');
        }

        $db =& JFactory::getDBO();

        $name  = mysql_real_escape_string(JRequest::getVar('name', 'Anonymous', 'post', 'string'));
        $email = mysql_real_escape_string(JRequest::getVar('email', '', 'post', 'string'));
        $tel   = mysql_real_escape_string(JRequest::getVar('tel', '', 'post', 'string'));
        $msg   = mysql_real_escape_string(JRequest::getVar('msg', '', 'post', 'string'));

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
        $id = $db->insertid();
        $message_id = $date.'-'.str_pad($id, 4, '0', STR_PAD_LEFT);

        // sender
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $query = "insert into #__complaints (message_id, name, email, phone, ip_address, raw_message, message_source, date_received) value('$message_id', '$name', '$email', '$tel', '$ip_address', '$msg', 'Website', now())";
        $db->setQuery($query);
        $db->query();

        // Send raw complaint to members
        $config =& JComponentHelper::getParams('com_cls');
        $raw_message_send_count = (int) $config->get('raw_message_send_count', 3);

        $db->setQuery("select email, rand() as r from #__users where params like '%receive_raw_messages=1%' order by r limit $raw_message_send_count");
        $res = $db->query();

        jimport('joomla.mail.mail');
        $mail = new JMail();
        $mail->setSender(array('complaints@lrip.am', 'Complaint Logging System'));
        $mail->setSubject('New Website Complaint: #' . $message_id);
        $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
        $mail->MsgHTML('<p>New complaint received from ' . "$name $email $tel" . '. Login to http://www.lrip.am/administrator/index.php?option=com_cls to process it.</p>' . $msg);
        $mail->AddReplyTo('no_reply@lrip.am');
        while($row = mysql_fetch_array($res, MYSQL_NUM))
            $mail->AddAddress($row[0]);
        $mail->Send();

        $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('COMPLAINT_FORM_SUBMIT'));
    }

    function newComplaint() {
        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Viewer');

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
        $complaint->set('raw_message', JRequest::getVar('raw_message'));
        $complaint->set('date_received', date('Y-m-d H:i:s'));
        $complaint->set('message_source', JRequest::getVar('message_source'));
        $complaint->store();

        // adding notification
        clsLog('New front-end complaint', 'New front-end complaint created #' . $message_id);

        $this->setRedirect('index.php?option=com_cls&view=complaints', JText::_('Complaint successfully created'));
    }

    function editComplaint() {
        echo 'edit ku';
    }

    function saveComplaint() {
        echo 'save ku';
    }
}

function clsLog($action, $description) {
    $db   =& JFactory::getDBO();
    $user =& JFactory::getUser();
    $description = mysql_real_escape_string($description);
    $db->setQuery("insert into #__complaint_notifications values(null, {$user->id}, '$action', now(), '$description')");
    $db->query();
}