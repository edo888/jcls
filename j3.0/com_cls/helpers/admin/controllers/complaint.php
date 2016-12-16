<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

jimport('joomla.application.component.controllerform');

jimport('joomla.database.table');

class ComplaintTableComplaint extends JTable {

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function __construct()
    {
        $db   = JFactory::getDBO();
        parent::__construct('#__complaints', 'id', $db);
    }
}


class ClsControllerComplaint extends JControllerForm {

    function __construct($default = array()) {
        parent::__construct($default);

        $task = $_REQUEST['task'];
        echo $task;
        $this->registerTask('add' , 'editComplaint');
        $this->registerTask('edit', 'editComplaint');
        $this->registerTask('save', 'saveComplaint');
        $this->registerTask('apply', 'saveComplaint');
        $this->registerTask('remove', 'removeComplaint');
        $this->registerTask('cancel', 'close');

        $this->registerTask('addArea' , 'editArea');
        $this->registerTask('editArea', 'editArea');
        $this->registerTask('saveArea', 'saveArea');
        $this->registerTask('applyArea', 'saveArea');
        $this->registerTask('removeArea', 'removeArea');
        $this->registerTask('cancelArea', 'showAreas');
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

    function removeComplaint() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $cid  = JRequest::getVar( 'cid', array(), '', 'array' );

        $user_type = $user->getParam('role', 'Guest');

        if($user_type == 'System Administrator') {
            for($i = 0, $n = count($cid); $i < $n; $i++) {
                $query = "delete from #__complaints where id = $cid[$i]";
                $db->setQuery($query);
                $db->query();
                clsLog('Complaint removed', 'The complaint with ID=' . $cid[$i] . ' has been removed');
            }

            $this->setRedirect('index.php?option=com_cls', JText::_('Complaint(s) successfully deleted'));
        } else {
            $this->setRedirect('index.php?option=com_cls', JText::_("You don't have permission to delete"));
        }
    }

    function close() {
        $link = 'index.php?option=com_cls&view=complaints';
        $this->setRedirect($link, $msg);
    }

    function editComplaint() {
        $ids = $this->input->get('cid', array(), 'array');
        $id = JRequest::getInt('id', intval($ids[0]));

        $model = $this->getModel('complaint');

        $model->editComplaint($_REQUEST);
        $link = 'index.php?option=com_cls&view=complaint&layout=edit';
        if($id != 0)
            $link .= '&id='.$id;
        $this->setRedirect($link, $msg);
    }

    public function saveComplaint() {
        $db = JFactory::getDBO();
        $user = JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');
        $id = JRequest::getInt('id', 0);

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        if($id == 0) { // going to insert new complaint
            // generating message_id
            $date = JRequest::getVar('date_received', date('Y-m-d'));
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
            $complaint = new JObject();
            $complaint->id = NULL;
            $complaint->message_id = $message_id;
            $complaint->name = JRequest::getVar('name');
            $complaint->gender = JRequest::getVar('gender');
            $complaint->email = JRequest::getVar('email');
            $complaint->phone = JRequest::getVar('phone');
            $complaint->address = JRequest::getVar('address');
            $complaint->ip_address = JRequest::getVar('ip_address');
            $complaint->raw_message = JRequest::getVar('raw_message');
            $complaint->date_received = JRequest::getVar('date_received', date('Y-m-d H:i:s'));
            $complaint->message_source = JRequest::getVar('message_source');
            $complaint->preferred_contact = JRequest::getVar('preferred_contact');

            if ($db->insertObject( '#__complaints', $complaint, 'id' ))

            // adding notification
            clsLog('New back-end complaint', 'New back-end complaint created #' . $message_id);

            $this->setRedirect('index.php?option=com_cls', JText::_('Complaint successfully created'));
        } else { // going to update complaint

            jimport('joomla.database.table');

            // constructing the complaint object
            //$complaint = new JTable('#__complaints', 'id', $db);
            $complaint = new ComplaintTableComplaint;
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
            $complaint->set('related_to_pb', null);
            $complaint->set('issue_type', null);
            $complaint->set('gender', null);
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
                if(JRequest::getInt('editor_id', 0))
                    $complaint->set('editor_id', JRequest::getInt('editor_id'));
                if(JRequest::getInt('resolver_id', 0))
                    $complaint->set('resolver_id', JRequest::getInt('resolver_id'));
            }

            if($user_type == 'System Administrator' or $user_type == 'Level 1' or $user_type == 'Level 2') {
                $complaint->set('related_to_pb', JRequest::getInt('related_to_pb'));
                $complaint->set('issue_type', JRequest::getInt('issue_type'));
                $complaint->set('gender', JRequest::getVar('gender'));
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
                    $complaint->set('editor_id', $user->id);

                    clsLog('Complaint processed', 'The user processed the complaint #' . $complaint->message_id);

                    // Send processed complaint notification to members
                    $config =& JComponentHelper::getParams('com_cls');

                    $support_group_id = JRequest::getInt('support_group_id');

                    $query = array();
                    // Send notification to Supervisors
                    $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and params like '%\"role\":\"Supervisor\"%')";
                    if($support_group_id)// Send notification to assagned Level 2 support groups
                        $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and params like '%\"role\":\"Level 2\"%' and id in (select user_id from #__complaint_support_groups_users_map where group_id = $support_group_id))";

                    $query = implode(' UNION ALL ', $query);

                    $db->setQuery($query);
                    $rows = $db->loadRowList();


                    jimport('joomla.mail.mail');
                    $mail = new JMail();
                    $mail->From = $config->get('complaints_email');
                    $mail->FromName = 'Complaint Logging System';
                    $mail->Subject = 'New Processed Complaint: #' . $complaint->message_id;
                    $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
                    $mail->msgHTML('<p>A complaint was processed. Login to '.JURI::base().'administrator/index.php?option=com_cls to resolve it.</p>' . $complaint->processed_message);
                    $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
                    foreach($rows as $row) {
                        $params = json_decode($row[2]);
                        if($params->receive_by_email == "1") { // send email notification
                            $mail->AddAddress($row[0]);
                            clsLog('Processed notification sent', 'Complaint #' . $complaint->message_id . ' processed notification sent to ' . $row[1]);
                        }

                        if($params->receive_by_sms == "1") { // send sms notification
                            $telephone = $params->telephone;
                            if(!empty($telephone)) {
                                $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, 'CLS', '$telephone', 'Complaint #$complaint->message_id processed, please login to the system to resolve it.', now(), 'Notification')");
                                $db->query();

                                clsLog('Processed notification sent', 'Complaint #' . $complaint->message_id . ' processed notification sent to ' . $telephone);
                            }
                        }
                    }
                    $mail->Send();
                }
            }

            if($user_type == 'System Administrator' or $user_type == 'Level 1' or $user_type == 'Level 2') {
                $complaint->set('resolution', JRequest::getVar('resolution'));
                if($complaint->date_resolved == '' and $complaint->resolution != '') {
                    $complaint->set('date_resolved', date('Y-m-d H:i:s'));
                    $complaint->set('resolver_id', $user->id);

                    clsLog('Complaint resolved', 'The user resolved the complaint #' . $complaint->message_id);
                }

                // TODO: send notification to "interested" parties
            }

            if($user_type == 'System Administrator' or $user_type == 'Level 1') {
                // send notifications
                if($complaint->confirmed_closed == 'Y') {
                    $complaint->set('date_closed', date('Y-m-d H:i:s'));

                    // notify supervisors and level 2 group assigned to the complaint
                    $config =& JComponentHelper::getParams('com_cls');
                    $support_group_id = JRequest::getInt('support_group_id');

                    $query = array();
                    // Send notification to Supervisors
                    $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and params like '%\"role\":\"Supervisor\"%')";
                    // Send notification to assagned Level 2 support groups
                    if($support_group_id)
                        $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and params like '%\"role\":\"Level 2\"%' and id in (select user_id from #__complaint_support_groups_users_map where group_id = $support_group_id))";

                    $query = implode(' UNION ALL ', $query);

                    $db->setQuery($query);
                    $rows = $db->loadRowList();

                    jimport('joomla.mail.mail');
                    $mail = new JMail();
                    $mail->From = $config->get('complaints_email');
                    $mail->FromName = 'Complaint Logging System';
                    $mail->Subject = 'Complaint resolved and closed: #' . $complaint->message_id;
                    $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
                    $mail->msgHTML("<p>Complaint #$complaint->message_id has been resolved and closed. Thanks for your efforts.</p>");
                    $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
                    foreach($rows as $row) {
                        $params = json_decode($row[2]);
                        if($params->receive_by_email == "1") { // send email notification
                            $mail->AddAddress($row[0]);
                            clsLog('Resolved and closed notification', 'Complaint #' . $complaint->message_id . ' resolution notification has been sent to ' . $row[1]);
                        }

                        if($params->receive_by_sms == "1") { // send sms notification
                            $telephone = $params->telephone;
                            if(!empty($telephone)) {
                                $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, 'CLS', '$telephone', 'Complaint #$complaint->message_id has been resolved and closed. Thanks for your efforts.', now(), 'Notification')");
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
                if(JRequest::getVar('action', '') != '') { // record action
                    clsLog('Complaint action taken', 'Action taken for #' . $complaint->message_id . ": \n" . JRequest::getVar('action'));
                }

                if(JRequest::getVar('comments', '') != '') { // append comment
                    $complaint->set('comments', $complaint->comments . 'On ' . date('Y-m-d H:i:s') . ' ' . $user->name . " wrote:\n" . JRequest::getVar('comments') . "\n\n");
                    clsLog('Complaint comment added', 'The user added a follow up comment on the complaint #' . $complaint->message_id);

                    $config =& JComponentHelper::getParams('com_cls');
                    $support_group_id = JRequest::getInt('support_group_id');

                    // Send notification
                    $query = array();

                    // Send notification to Supervisors
                    $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and params like '%\"role\":\"Supervisor\"%')";

                    // Send notification to assagned Level 2 support groups
                    if($support_group_id)
                        $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and params like '%\"role\":\"Level 2\"%' and id in (select user_id from #__complaint_support_groups_users_map where group_id = $support_group_id))";

                    $query = implode(' UNION ALL ', $query);

                    $db->setQuery($query);
                    $rows = $db->loadRowList();

                    jimport('joomla.mail.mail');
                    $mail = new JMail();
                    $mail->From = $config->get('complaints_email');
                    $mail->FromName = 'Complaint Logging System';
                    $mail->Subject = 'New Comment Posted: #' . $complaint->message_id;
                    $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
                    $mail->msgHTML('<h3>New comment has been posted:</h3><pre>' . $complaint->comments . '</pre>');
                    $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
                    foreach($rows as $row) {
                        $params = json_decode($row[2]);
                        if($params->receive_by_email == "1") { // send email notification
                            $mail->AddAddress($row[0]);
                            clsLog('New comment notification', 'Complaint #' . $complaint->message_id . ' comment notification sent to ' . $row[1]);
                        }

                        if($params->receive_by_sms == "1") { // send sms notification
                            $telephone = $params->telephone;
                            if(!empty($telephone)) {
                                $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, 'CLS', '$telephone', 'Complaint #$complaint->message_id got new comments, please login to the system to take actions.', now(), 'Notification')");
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

            if($_REQUEST['task'] == 'save')
                $this->setRedirect('index.php?option=com_cls', JText::_('Complaint successfully saved'));
            elseif($_REQUEST['task'] == 'apply')
                $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('Complaint successfully saved'));
            else
                $this->setRedirect('index.php?option=com_cls', JText::_('Unknown task'));

        }
    }

}
