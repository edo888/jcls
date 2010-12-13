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

class CLSController extends JController {
    function __construct($default = array()) {
        parent::__construct($default);
        $this->registerTask('submit', 'submitComplaint');
    }

    function display() {
        $view = JRequest::getVar('view');
        parent::display(true);
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
}