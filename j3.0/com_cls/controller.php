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

require_once(JPATH_COMPONENT_ADMINISTRATOR.'/controller.php');
require_once(JPATH_COMPONENT.'/cls.html.php');

class clsFrontController extends JControllerLegacy {
    function __construct($default = array()) {
        parent::__construct($default);
        $this->registerTask('download_report', 'downloadReport');
        $this->registerTask('submit', 'submitComplaint');
        $this->registerTask('newcomplaint', 'newComplaint');

        // backend tasks referencing
        $this->registerTask('edit', 'adminRefferenceTask');
        $this->registerTask('save', 'adminRefferenceTask');
        $this->registerTask('apply', 'adminRefferenceTask');
        $this->registerTask('remove', 'adminRefferenceTask');
        #$this->registerTask('addContract', 'adminRefferenceTask');
        #$this->registerTask('editContract', 'adminRefferenceTask');
        #$this->registerTask('saveContract', 'adminRefferenceTask');
        #$this->registerTask('applyContract', 'adminRefferenceTask');
        #$this->registerTask('removeContract', 'adminRefferenceTask');
        #$this->registerTask('cancelContract', 'adminRefferenceTask');
        #$this->registerTask('addSection', 'adminRefferenceTask');
        #$this->registerTask('editSection', 'adminRefferenceTask');
        #$this->registerTask('saveSection', 'adminRefferenceTask');
        #$this->registerTask('applySection', 'adminRefferenceTask');
        #$this->registerTask('removeSection', 'adminRefferenceTask');
        #$this->registerTask('cancelSection', 'adminRefferenceTask');
        #$this->registerTask('download_report', 'adminRefferenceTask');
        $this->registerTask('notify_sms_acknowledge', 'adminRefferenceTask');
        $this->registerTask('notify_email_acknowledge', 'adminRefferenceTask');
        $this->registerTask('notify_sms_resolve', 'adminRefferenceTask');
        $this->registerTask('notify_email_resolve', 'adminRefferenceTask');
        $this->registerTask('upload_picture', 'adminRefferenceTask');
    }

    function display() {
        $view = JRequest::getVar('view');
        parent::display(true);
    }

    function downloadReport() {
        $db   =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $doc  =& JFactory::getDocument();

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2') {
            $this->setRedirect('index.php', JText::_("You don't have permission"));
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
            case 'period': $query .= " where date_received >= '$startdate' and date_received <= '$enddate 23:59:59'"; break;
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

    function submitComplaint() {
        $mainframe = JFactory::getApplication();

        // check captcha
        $session =& JFactory::getSession();

        // remember input data
        $session->set('cls_name', JRequest::getVar('name'));
        $session->set('cls_email', JRequest::getVar('email'));
        $session->set('cls_tel', JRequest::getVar('tel'));
        $session->set('cls_address', JRequest::getVar('address'));

        if($session->get('cls_captcha') != strtoupper(JRequest::getVar('captcha'))) {
            $session->set('cls_msg', JRequest::getVar('msg'));
            $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('COMPLAINT_FORM_INVALID_CAPTCHA'));
            return;
        } else {
            $session->set('cls_msg', '');
        }

        $db =& JFactory::getDBO();

        $name    = $db->escape(JRequest::getVar('name', 'Anonymous', 'post', 'string'));
        $email   = $db->escape(JRequest::getVar('email', '', 'post', 'string'));
        $tel     = $db->escape(JRequest::getVar('tel', '', 'post', 'string'));
        $address = $db->escape(JRequest::getVar('address', '', 'post', 'string'));
        $msg     = $db->escape(JRequest::getVar('msg', '', 'post', 'string'));

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

        $query = "insert into #__complaints (message_id, name, email, phone, address, ip_address, raw_message, message_source, date_received) value('$message_id', '$name', '$email', '$tel', '$address', '$ip_address', '$msg', 'Website', now())";
        $db->setQuery($query);
        $db->query();
        $complaint_id = $db->insertid();

        // adding pictures if any
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');

        // fix the odd indexing of the $_FILES['field']
        fixFilesArray($_FILES['pictures']);

        foreach($_FILES['pictures'] as $file) { // upload file
            $fileError = $file['error'];
            if($fileError > 0)  {
                switch ($fileError) {
                    case 1: JError::raiseWarning(10, JText::_('FILE TO LARGE THAN PHP INI ALLOWS')); continue 2; break;
                    case 2: JError::raiseWarning(11, JText::_('FILE TO LARGE THAN HTML FORM ALLOWS')); continue 2; break;
                    case 3: JError::raiseWarning(12, JText::_('ERROR PARTIAL UPLOAD')); continue 2; break;
                    case 4: /*JError::raiseWarning(13, JText::_('ERROR NO FILE'));*/ continue 2; break;
                }
            }

            //check for filesize
            $fileSize = $file['size'];
            if($fileSize > 2000000)
                JError::raiseWarning(14, JText::_('FILE BIGGER THAN 2MB'));

            //check the file extension is ok
            $fileName = $file['name'];
            $uploadedFileNameParts = explode('.', $fileName);
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
                JError::raiseWarning(14, JText::_('INVALID EXTENSION'));
                continue;
            }

            //the name of the file in PHP's temp directory that we are going to move to our folder
            $fileTemp = $file['tmp_name'];

            //for security purposes, we will also do a getimagesize on the temp file (before we have moved it
            //to the folder) to check the MIME type of the file, and whether it has a width and height
            $imageinfo = getimagesize($fileTemp);

            //we are going to define what file extensions/MIMEs are ok, and only let these ones in (whitelisting), rather than try to scan for bad
            //types, where we might miss one (whitelisting is always better than blacklisting)
            $okMIMETypes = 'image/jpeg,image/pjpeg,image/png,image/x-png,image/gif';
            $validFileTypes = explode(",", $okMIMETypes);

            //if the temp file does not have a width or a height, or it has a non ok MIME, return
            if(!is_int($imageinfo[0]) or !is_int($imageinfo[1]) or  !in_array($imageinfo['mime'], $validFileTypes)) {
                JError::raiseWarning(15, JText::_('INVALID FILETYPE'));
                continue;
            }

            //lose any special characters in the filename
            $fileName = ereg_replace("[^A-Za-z0-9.]", "-", $fileName);

            // generate random filename
            $fileName = uniqid($complaint_id.'_') . '-' . $fileName;

            //always use constants when making file paths, to avoid the possibilty of remote file inclusion
            $uploadPath = JPATH_ADMINISTRATOR.'/components/com_cls/pictures/'.$fileName;

            if(!JFile::upload($fileTemp, $uploadPath)) {
                JError::raiseWarning(16, JText::_('ERROR MOVING FILE'));
                continue;
            } else {
                // going to insert the picture into db
                $picture = new JTable('#__complaint_pictures', 'id', $db);
                $picture->set('complaint_id', $complaint_id);
                $picture->set('path', str_replace(JPATH_ADMINISTRATOR.'/', '', $uploadPath));
                $picture->store();
            }
        }


        // Send new complaint notification to members
        $config =& JComponentHelper::getParams('com_cls');

        $db->setQuery("select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and (params like '%\"role\":\"System Administrator\"%' or params like '%\"role\":\"Level 1\"%')");
        $rows = $db->loadRowList();

        jimport('joomla.mail.mail');
        $mail = new JMail();
        $mail->setSender(array($config->get('complaints_email'), 'Complaint Logging System'));
        $mail->setSubject('New Website Complaint: #' . $message_id);
        $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
        $mail->MsgHTML('<p>New complaint received from ' . "$name $email $tel" . '. Login to http://'.$_SERVER['HTTP_HOST'].'/administrator/index.php?option=com_cls to process it.</p>' . $msg);
        $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
        foreach($rows as $row) {
            $params = json_decode($row[2]);
            if($params->receive_by_email == "1") { // send email notification
                $mail->AddAddress($row[0]);
                clsLog('New email complaint notification', "New complaint #{$message_id} notification has been sent to $row[1]");
            }

            if($params->receive_by_sms == "1") { // send sms notification
                $telephone = $params->telephone;
                if(!empty($telephone)) {
                    $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($complaint_id, 'CLS', '$telephone', 'New complaint #{$message_id} received, please login to the system to process it.', now(), 'Notification')");
                    $db->query();

                    clsLog('New SMS complaint notification', "New complaint #{$message_id} notification has been sent to $telephone");
                }
            }
        }
        $mail->Send();

        // Send acknowledgment email to the complainer
        $sms_acknowledgment = (int) $config->get('sms_acknowledgment', 0);
        $email_acknowledgment = (int) $config->get('email_acknowledgment', 0);
        $acknowledgment_text = str_replace('{sitename}', $mainframe->getCfg('sitename'), sprintf($config->get('acknowledgment_text'), $message_id));

        if($email_acknowledgment and $email != '') {
            $mail = new JMail();
            $mail->setSender(array($config->get('complaints_email'), 'Complaint Logging System'));
            $mail->setSubject('Your Complaint Received: #' . $message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML('<p>'.$acknowledgment_text.'</p>' . 'http://'.$_SERVER['HTTP_HOST']);
            $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
            $mail->AddAddress($email);
            $mail->Send();

            clsLog('New complaint acknowledgment', "New complaint #{$message_id} acknowledgment has been sent to $email");
        }

        if($sms_acknowledgment and $tel != '') {
            $acknowledgment_text = $db->escape($acknowledgment_text);
            $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($complaint_id, 'CLS', '$tel', '$acknowledgment_text', now(), 'Acknowledgment')");
            $db->query();

            clsLog('SMS acknowledgment queued', "SMS acknowledgment queued to be sent to $tel for complaint #{$message_id}");
        }

        $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('COMPLAINT_FORM_SUBMIT'));
    }

    function newComplaint() {
        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $user_type = $user->getParam('role', 'Guest');

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

    function adminRefferenceTask() {
        $controller = new CLSController();
        $controller->execute(JRequest::getVar('task'));
    }

}

function clsLog($action, $description) {
    $db   =& JFactory::getDBO();
    $user =& JFactory::getUser();
    $description = $db->escape($description);
    $db->setQuery("insert into #__complaint_notifications values(null, {$user->id}, '$action', now(), '$description')");
    $db->query();
}

/**
 * Fixes the odd indexing of multiple file uploads from the format:
 *
 * $_FILES['field']['key']['index']
 *
 * To the more standard and appropriate:
 *
 * $_FILES['field']['index']['key']
 *
 * @param array $files
 * @author Corey Ballou
 * @link http://www.jqueryin.com
 */
function fixFilesArray(&$files) {
    $names = array( 'name' => 1, 'type' => 1, 'tmp_name' => 1, 'error' => 1, 'size' => 1);

    foreach ($files as $key => $part) {
        // only deal with valid keys and multiple files
        $key = (string) $key;
        if (isset($names[$key]) && is_array($part)) {
            foreach ($part as $position => $value) {
                $files[$position][$key] = $value;
            }
            // remove old key reference
            unset($files[$key]);
        }
    }
}