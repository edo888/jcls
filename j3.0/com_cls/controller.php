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
require_once(JPATH_COMPONENT.'/helpers/helper.php');

class clsFrontController extends JControllerLegacy {
    /**
     * @var     string  The default view.
     * @since   1.6
     */
    protected $default_view = 'reports';

    function __construct($default = array()) {
        parent::__construct($default);

        $this->registerTask('download_report', 'downloadReport');
        $this->registerTask('notify_sms_acknowledge', 'notifySMSAcknowledge');
        $this->registerTask('notify_email_acknowledge', 'notifyEmailAcknowledge');
        $this->registerTask('notify_sms_resolve', 'notifySMSResolve');
        $this->registerTask('notify_email_resolve', 'notifyEmailResolve');
        $this->registerTask('upload_picture' , 'uploadPicture');

        $this->registerTask('submit', 'submitComplaint');
        $this->registerTask('newcomplaint', 'newComplaint');

        $this->registerTask('submit_supervision_report', 'submitSupervisionReport');
        $this->registerTask('submit_contractor_report', 'submitContractorReport');
        $this->registerTask('submit_incident_report', 'submitIncidentReport');
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

    function notifySMSAcknowledge() {
        $mainframe = JFactory::getApplication();

        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        $config = JComponentHelper::getParams('com_cls');
        $acknowledgment_text = str_replace('{sitename}', $mainframe->getCfg('sitename'), sprintf($config->get('acknowledgment_text'), $complaint->message_id));

        if($user_type != 'Guest') {
            $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) values($id, '$user->username', '$complaint->phone', '$acknowledgment_text', now(), 'Acknowledgment')");
            $db->query() or JError::raiseWarning(0, 'Unable to insert msg into queue');

            clsLog('SMS acknowledgment queued', "SMS acknowledgment queued to be sent to $complaint->phone for complaint #$complaint->message_id");
            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('SMS notification will be sent shortly'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifyEmailAcknowledge() {
        $mainframe = JFactory::getApplication();

        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        $config = JComponentHelper::getParams('com_cls');
        $acknowledgment_text = str_replace('{sitename}', $mainframe->getCfg('sitename'), sprintf($config->get('acknowledgment_text'), $complaint->message_id));

        if($user_type != 'Guest') {
            jimport('joomla.mail.mail');
            $mail = new JMail();
            $mail->setSender(array('no_reply@'.$_SERVER['HTTP_HOST'], 'Complaint Logging System'));
            $mail->setSubject('Complaint Received: #'.$complaint->message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML("<p>$acknowledgment_text</p>");
            $mail->AddAddress($complaint->email);
            $mail->Send() or JError::raiseWarning(0, 'Unable to send Email notification');

            clsLog('Email acknowledgment sent', "Email acknowledgment has been sent to $complaint->email for complaint #$complaint->message_id");

            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('Email notification successfully sent'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifySMSResolve() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user_type !='Guest') {
            $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, '$user->username', '$complaint->phone', 'Thank you, your complaint #{$complaint->message_id} was resolved. Feel free to send further complaints if any.', now(), 'Resolved')");
            $db->query() or JError::raiseWarning(0, 'Unable to insert msg into queue');

            clsLog('SMS resolution acknowledgment queued', 'Complaint #' . $complaint->message_id . ' SMS notification has been queued to be sent to ' . $complaint->phone . ' number');

            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('SMS notification will be sent shortly'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function notifyEmailResolve() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select * from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        if($user_type !='Guest') {
            jimport('joomla.mail.mail');
            $mail = new JMail();
            $mail->setSender(array('no_reply@'.$_SERVER['HTTP_HOST'], 'Complaint Logging System'));
            $mail->setSubject('Complaint Resolved: #'.$complaint->message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML('<p>Thank you, your complaint was resolved. Feel free to send further complaints if any.</p>');
            $mail->AddAddress($complaint->email);
            $mail->Send() or JError::raiseWarning(0, 'Unable to send Email notification');

            clsLog('Email resolution acknowledgment sent', 'Complaint #' . $complaint->message_id . ' email notification has been sent to ' . $complaint->email . ' address');

            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('Email notification successfully sent'));
        } else {
            $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('You don\'t have permission to send notifications'));
        }
    }

    function uploadPicture() {
        $user_type = JFactory::getUser()->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Supervisor') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
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
                    echo JText::_('FILE TO LARGE THAN PHP INI ALLOWS');
                    return;
                case 2:
                    echo JText::_('FILE TO LARGE THAN HTML FORM ALLOWS');
                    return;
                case 3:
                    echo JText::_('ERROR PARTIAL UPLOAD');
                    return;
                case 4:
                    echo JText::_('ERROR NO FILE');
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
            echo JText::_('INVALID EXTENSION');
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
            echo JText::_('INVALID FILETYPE');
            return;
        }

        //lose any special characters in the filename
        $fileName = preg_replace("/[^A-Za-z0-9.]/", "-", $fileName);

        // generate random filename
        $fileName = uniqid(JRequest::getInt('id').'_') . '-' . $fileName;

        //always use constants when making file paths, to avoid the possibilty of remote file inclusion
        $uploadPath = JPATH_ADMINISTRATOR.'/components/com_cls/pictures/'.$fileName;

        if(!JFile::upload($fileTemp, $uploadPath)) {
            echo JText::_('ERROR MOVING FILE');
            return;
        } else {
            // going to insert the picture into db
            $db = JFactory::getDBO();
            $user = JFactory::getUser();
            $user_type = $user->getParam('role', 'Guest');
            $complaint_id = JRequest::getInt('id', 0);
            if($user_type !='Guest') {

                $pic = new JObject();
                $pic->id = NULL;
                $pic->complaint_id = $complaint_id;
                $pic->path = str_replace(JPATH_ADMINISTRATOR.'/', '', $uploadPath);

                $db->insertObject('#__complaint_pictures', $pic, 'id');
            }

            $db->setQuery('select message_id from #__complaints where id = ' . $complaint_id);
            $complaint = $db->loadObject();
            clsLog('Image uploaded', 'User uploaded an image for Complaint #' . $complaint->message_id);

            // success, exit with code 0 for Mac users, otherwise they receive an IO Error
            exit(0);
        }
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
        $session->set('cls_gender', JRequest::getVar('gender'));
        $session->set('cls_gbv', JRequest::getVar('gbv'));
        $session->set('cls_gbv_type', JRequest::getVar('gbv_type'));
        $session->set('cls_gbv_relation', JRequest::getVar('gbv_relation'));

        if($session->get('cls_captcha') != strtoupper(JRequest::getVar('captcha'))) {
            $session->set('cls_msg', JRequest::getVar('msg'));
            $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('COMPLAINT_FORM_INVALID_CAPTCHA'));
            return;
        } else {
            $session->set('cls_msg', '');
        }

        $db =& JFactory::getDBO();

        $name         = $db->escape(JRequest::getVar('name', 'Anonymous', 'post', 'string'));
        $email        = $db->escape(JRequest::getVar('email', '', 'post', 'string'));
        $tel          = $db->escape(JRequest::getVar('tel', '', 'post', 'string'));
        $address      = $db->escape(JRequest::getVar('address', '', 'post', 'string'));
        $gender       = $db->escape(JRequest::getVar('gender', '', 'post', 'string'));
        $gbv          = $db->escape(JRequest::getVar('gbv', '', 'post', 'int'));
        $gbv_type     = $db->escape(JRequest::getVar('gbv_type', '', 'post', 'string'));
        $gbv_relation = $db->escape(JRequest::getVar('gbv_relation', '', 'post', 'string'));
        $location     = $db->escape(JRequest::getVar('location', '', 'post', 'string'));
        $msg          = $db->escape(JRequest::getVar('msg', '', 'post', 'string'));

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

        /*
        $query = "insert into #__complaints (message_id, name, email, phone, address, gender, ip_address, raw_message, message_source, date_received) value('$message_id', '$name', '$email', '$tel', '$address', '$gender', '$ip_address', '$msg', 'Website', now())";
        $db->setQuery($query);
        $db->query();
        $complaint_id = $db->insertid();
        */

        // encrpyt complaint data
        if(JRequest::getInt('gbv', 0)) {
            $gbv = 1;

            list($password, $encrypted_msg) = gbv_encrypt(JRequest::getVar('msg'));
            list($password, $encrypted_name) = gbv_encrypt(JRequest::getVar('name', 'Anonymous'), $password);
            list($password, $encrypted_email) = gbv_encrypt(JRequest::getVar('email'), $password);
            list($password, $encrypted_tel) = gbv_encrypt(JRequest::getVar('tel'), $password);
            list($password, $encrypted_address) = gbv_encrypt(JRequest::getVar('address'), $password);
            list($password, $encrypted_ip_address) = gbv_encrypt($ip_address, $password);
        }

        // constructing the complaint object
        $complaint = new stdClass();
        $complaint->id = NULL;
        $complaint->message_id = $message_id;
        $complaint->name = isset($encrypted_name) ? $encrypted_name : JRequest::getVar('name', 'Anonymous');
        $complaint->email = isset($encrypted_email) ? $encrypted_email : JRequest::getVar('email');
        $complaint->phone = isset($encrypted_tel) ? $encrypted_tel : JRequest::getVar('tel');
        $complaint->address = isset($encrypted_address) ? $encrypted_address : JRequest::getVar('address');
        $complaint->gender = JRequest::getVar('gender');
        $complaint->gbv = JRequest::getInt('gbv', 0);
        $complaint->gbv_type = JRequest::getVar('gbv_type', '');
        $complaint->gbv_relation = JRequest::getVar('gbv_relation', 'unknown');
        $complaint->location = JRequest::getVar('location');
        $complaint->ip_address = isset($encrypted_ip_address) ? $encrypted_ip_address : $ip_address;
        $complaint->raw_message = isset($encrypted_msg) ? $encrypted_msg : JRequest::getVar('msg');
        $complaint->date_received = date('Y-m-d H:i:s');
        $complaint->message_source = 'Website';
        $complaint->preferred_contact = JRequest::getVar('preferred_contact');

        $db->insertObject('#__complaints', $complaint, 'id');
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
                $picture = new JObject();
                $picture->set('complaint_id', $complaint_id);
                $picture->set('path', str_replace(JPATH_ADMINISTRATOR.'/', '', $uploadPath));
                JFactory::getDbo()->insertObject('#__complaint_pictures', $picture);
            }
        }

        // Send new complaint notification to members
        $config =& JComponentHelper::getParams('com_cls');

        $db->setQuery("select email, name, params from #__users where block = 0 and params like '%\"receive_notifications\":\"1\"%' and (params like '%\"role\":\"System Administrator\"%' or params like '%\"role\":\"Level 1\"%')");
        $rows = $db->loadRowList();

        jimport('joomla.mail.mail');
        $mail = new JMail();
        $mail->setSender(array($config->get('complaints_email'), 'Complaint Logging System'));
        if($gbv)
            $mail->setSubject('GBV/VAC Alert! Complaint: #' . $message_id);
        else
            $mail->setSubject('New Website Complaint: #' . $message_id);
        $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';

        if($gbv)
            $mail->MsgHTML('<p>Login to '.JURI::base().'administrator/index.php?option=com_cls to take appropriate action.</p>');
        else
            $mail->MsgHTML('<p>New complaint received from ' . "$name $email $tel" . '. Login to '.JURI::base().'administrator/index.php?option=com_cls to process it.</p>' . $msg);

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

        if($gbv) { // send data to trusted people
            $mail = new JMail();
            $mail->setSender(array($config->get('complaints_email'), 'Complaint Logging System'));
            $mail->setSubject('GBV/VAC Alert! Complaint: #' . $message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML('<p>New GBV complaint received. Passphrase is: <b>' . $password . '</b>. Login to '.JURI::base().'administrator/index.php?option=com_cls to process it.</p>' . $msg);
            $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
            $mail->AddAddress('edo888@gmail.com');
            $mail->AddAddress('cbennett2@worldbank.org');
            $mail->AddAddress('nweisskopf@worldbank.org');
            $trusted_emails = explode(',', $config->get('gbv_emails'));
            foreach($trusted_emails as $e)
                $mail->AddAddress($e);
            $mail->Send();
        }

        // Send acknowledgment email to the complainer
        $sms_acknowledgment = (int) $config->get('sms_acknowledgment', 0);
        $email_acknowledgment = (int) $config->get('email_acknowledgment', 0);
        $acknowledgment_text = str_replace('{sitename}', $mainframe->getCfg('sitename'), sprintf($config->get('acknowledgment_text'), $message_id));

        if($email_acknowledgment and $email != '') {
            $mail = new JMail();
            $mail->setSender(array($config->get('complaints_email'), 'Complaint Logging System'));
            $mail->setSubject('Complaint Received: #' . $message_id);
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->MsgHTML('<p>'.$acknowledgment_text.'</p>' . JURI::base());
            $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
            $mail->AddAddress($email);
            $mail->Send();

            if($gbv)
                clsLog('New complaint acknowledgment', "New complaint #{$message_id} acknowledgment has been sent to sender.");
            else
                clsLog('New complaint acknowledgment', "New complaint #{$message_id} acknowledgment has been sent to $email");
        }

        if(!$gbv and $sms_acknowledgment and $tel != '') {
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
        //$complaint = new JTable('#__complaints', 'id', $db);
        $complaint = new ComplaintTableComplaint;
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

    function submitIncidentReport() {

        $mainframe = JFactory::getApplication();
        $session = JFactory::getSession();
        $user = JFactory::getUser();
        $db = JFactory::getDBO();

        // remember input data
        $session->set('cls_location_of_incident', JRequest::getVar('location_of_incident'));
        $session->set('cls_injury_type', JRequest::getInt('injury_type'));
        $session->set('cls_summary_of_events', JRequest::getVar('summary_of_events'));
        $session->set('cls_persons_involved', JRequest::getVar('persons_involved'));
        $session->set('cls_immediate_cause_of_incident', JRequest::getVar('immediate_cause_of_incident'));
        $session->set('cls_underlying_cause_of_incident', JRequest::getVar('underlying_cause_of_incident'));
        $session->set('cls_root_cause_of_incident', JRequest::getVar('root_cause_of_incident'));
        $session->set('cls_immediate_action_taken', JRequest::getVar('immediate_action_taken'));
        $session->set('cls_human_factors', JRequest::getVar('human_factors'));
        $session->set('cls_outcome_of_incident', JRequest::getVar('outcome_of_incident'));
        $session->set('cls_corrective_actions', JRequest::getVar('corrective_actions'));
        $session->set('cls_support_provided', JRequest::getVar('support_provided'));
        $session->set('cls_recommendations_for_further_improvement', JRequest::getVar('recommendations_for_further_improvement'));

        // constructing the report object
        $report = new stdClass();
        $report->contract_id = JRequest::getInt('contract_id');
        $report->user_id = $user->id;
        $report->date_of_incident = date('Y-m-d H:i:s');

        $report->location_of_incident = JRequest::getVar('location_of_incident');
        $report->injury_type = JRequest::getInt('injury_type');
        $report->summary_of_events = JRequest::getVar('summary_of_events');
        $report->persons_involved = JRequest::getVar('persons_involved');
        $report->immediate_cause_of_incident = JRequest::getVar('immediate_cause_of_incident');
        $report->underlying_cause_of_incident = JRequest::getVar('underlying_cause_of_incident');
        $report->root_cause_of_incident = JRequest::getVar('root_cause_of_incident');
        $report->immediate_action_taken = JRequest::getVar('immediate_action_taken');
        $report->human_factors = JRequest::getVar('human_factors');
        $report->outcome_of_incident = JRequest::getVar('outcome_of_incident');
        $report->corrective_actions = JRequest::getVar('corrective_actions');
        $report->support_provided = JRequest::getVar('support_provided');
        $report->recommendations_for_further_improvement = JRequest::getVar('recommendations_for_further_improvement');

        $query = $db->getQuery(true);
        $query->select('name')->from($db->quoteName('#__complaint_contracts'))->where($db->quoteName('id')." = ".$db->quote($report->contract_id));
        $db->setQuery($query);
        $contract_name = $db->loadResult();

        $db->insertObject('#__ohs_incident_reporting', $report);
        clsLog('Incident report submitted', 'Incident report submitted for '.$contract_name);
        $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('CLS_OHS_INCIDENT_FORM_SUBMITTED'));
    }

    function submitContractorReport() {

        $mainframe = JFactory::getApplication();
        $session = JFactory::getSession();
        $user = JFactory::getUser();
        $db = JFactory::getDBO();

        // remember input data
        $session->set('cls_number_of_hours_worked_this_month_male', JRequest::getInt('number_of_hours_worked_this_month_male'));
        $session->set('cls_number_of_hours_worked_this_month_female', JRequest::getInt('number_of_hours_worked_this_month_female'));
        $session->set('cls_number_of_workers_male', JRequest::getInt('number_of_workers_male'));
        $session->set('cls_number_of_workers_female', JRequest::getInt('number_of_workers_female'));
        $session->set('cls_update_to_the_ohs_safety_plan', JRequest::getInt('update_to_the_ohs_safety_plan'));
        $session->set('cls_number_of_workers_trained', JRequest::getInt('number_of_workers_trained'));
        $session->set('cls_number_of_competency_assessments', JRequest::getInt('number_of_competency_assessments'));
        $session->set('cls_number_of_new_skill_training_sessions', JRequest::getInt('number_of_new_skill_training_sessions'));
        $session->set('cls_number_of_ohs_training', JRequest::getInt('number_of_ohs_training'));
        $session->set('cls_number_of_hiv_aids_training', JRequest::getInt('number_of_hiv_aids_training'));
        $session->set('cls_number_of_gbv_vac_training', JRequest::getInt('number_of_gbv_vac_training'));
        $session->set('cls_checks_site_health_and_safety_audits', JRequest::getInt('checks_site_health_and_safety_audits'));
        $session->set('cls_checks_safety_briefings', JRequest::getInt('checks_safety_briefings'));
        $session->set('cls_checks_drugs', JRequest::getInt('checks_drugs'));
        $session->set('cls_checks_drugs_positive', JRequest::getInt('checks_drugs_positive'));
        $session->set('cls_checks_alcohol', JRequest::getInt('checks_alcohol'));
        $session->set('cls_checks_alcohol_positive', JRequest::getInt('checks_alcohol_positive'));
        $session->set('cls_checks_hiv', JRequest::getInt('checks_hiv'));
        $session->set('cls_checks_hiv_positive', JRequest::getInt('checks_hiv_positive'));
        $session->set('cls_number_of_near_misses', JRequest::getInt('number_of_near_misses'));
        $session->set('cls_number_of_stop_work_actions', JRequest::getInt('number_of_stop_work_actions'));
        $session->set('cls_number_of_traffic_management_inspections', JRequest::getInt('number_of_traffic_management_inspections'));
        $session->set('cls_number_of_completed_investigations', JRequest::getInt('number_of_completed_investigations'));
        $session->set('cls_number_of_new_risks_identified', JRequest::getInt('number_of_new_risks_identified'));
        $session->set('cls_number_of_suggestions_for_improvement_identified', JRequest::getInt('number_of_suggestions_for_improvement_identified'));
        $session->set('cls_fatal_injuries', JRequest::getInt('fatal_injuries'));
        $session->set('cls_notifiable_injuries_or_incidents', JRequest::getInt('notifiable_injuries_or_incidents'));
        $session->set('cls_lost_time_injuries_or_illnesses', JRequest::getInt('lost_time_injuries_or_illnesses'));
        $session->set('cls_medically_treated_injuries_or_illnesses', JRequest::getInt('medically_treated_injuries_or_illnesses'));
        $session->set('cls_first_aid_injuries', JRequest::getInt('first_aid_injuries'));
        $session->set('cls_injury_with_no_treatment', JRequest::getInt('injury_with_no_treatment'));
        $session->set('cls_traffic_accidents_involving_project_vehicles_equipment', JRequest::getInt('traffic_accidents_involving_project_vehicles_equipment'));
        $session->set('cls_accidents_involving_non_project_vehicles_or_property', JRequest::getInt('accidents_involving_non_project_vehicles_or_property'));
        $session->set('cls_environmental_incident', JRequest::getInt('environmental_incident'));
        $session->set('cls_escape_of_a_substance_into_the_atmosphere', JRequest::getInt('escape_of_a_substance_into_the_atmosphere'));
        $session->set('cls_utility_or_service_strike', JRequest::getInt('utility_or_service_strike'));
        $session->set('cls_damage_to_public_property_or_equipment', JRequest::getInt('damage_to_public_property_or_equipment'));
        $session->set('cls_damage_to_contractors_equipment', JRequest::getInt('damage_to_contractors_equipment'));
        $session->set('cls_worker_leaving_site_due_to_safety_concerns', JRequest::getInt('worker_leaving_site_due_to_safety_concerns'));
        $session->set('cls_staff_on_reduced_alternate_duties', JRequest::getInt('staff_on_reduced_alternate_duties'));

        // constructing the report object
        $report = new stdClass();
        $report->contract_id = JRequest::getInt('contract_id');
        $report->user_id = $user->id;
        $report->report_month = date('Y-m-d', strtotime(JRequest::getVar('report_month') . '-01'));
        $report->date_submitted = date('Y-m-d H:i:s');

        $report->number_of_hours_worked_this_month_male = JRequest::getInt('number_of_hours_worked_this_month_male');
        $report->number_of_hours_worked_this_month_female = JRequest::getInt('number_of_hours_worked_this_month_female');
        $report->number_of_workers_male = JRequest::getInt('number_of_workers_male');
        $report->number_of_workers_female = JRequest::getInt('number_of_workers_female');

        $report->update_to_the_ohs_safety_plan = JRequest::getInt('update_to_the_ohs_safety_plan') ? 'Y' : 'N';

        $report->number_of_workers_trained = JRequest::getInt('number_of_workers_trained');
        $report->number_of_competency_assessments = JRequest::getInt('number_of_competency_assessments');
        $report->number_of_new_skill_training_sessions = JRequest::getInt('number_of_new_skill_training_sessions');
        $report->number_of_ohs_training = JRequest::getInt('number_of_ohs_training');
        $report->number_of_hiv_aids_training = JRequest::getInt('number_of_hiv_aids_training');
        $report->number_of_gbv_vac_training = JRequest::getInt('number_of_gbv_vac_training');
        $report->checks_site_health_and_safety_audits = JRequest::getInt('checks_site_health_and_safety_audits');
        $report->checks_safety_briefings = JRequest::getInt('checks_safety_briefings');
        $report->checks_drugs = JRequest::getInt('checks_drugs');
        $report->checks_drugs_positive = JRequest::getInt('checks_drugs_positive');
        $report->checks_alcohol = JRequest::getInt('checks_alcohol');
        $report->checks_alcohol_positive = JRequest::getInt('checks_alcohol_positive');
        $report->checks_hiv = JRequest::getInt('checks_hiv');
        $report->checks_hiv_positive = JRequest::getInt('checks_hiv_positive');
        $report->number_of_near_misses = JRequest::getInt('number_of_near_misses');
        $report->number_of_stop_work_actions = JRequest::getInt('number_of_stop_work_actions');
        $report->number_of_traffic_management_inspections = JRequest::getInt('number_of_traffic_management_inspections');
        $report->number_of_completed_investigations = JRequest::getInt('number_of_completed_investigations');
        $report->number_of_new_risks_identified = JRequest::getInt('number_of_new_risks_identified');
        $report->number_of_suggestions_for_improvement_identified = JRequest::getInt('number_of_suggestions_for_improvement_identified');
        $report->fatal_injuries = JRequest::getInt('fatal_injuries');
        $report->notifiable_injuries_or_incidents = JRequest::getInt('notifiable_injuries_or_incidents');
        $report->lost_time_injuries_or_illnesses = JRequest::getInt('lost_time_injuries_or_illnesses');
        $report->medically_treated_injuries_or_illnesses = JRequest::getInt('medically_treated_injuries_or_illnesses');
        $report->first_aid_injuries = JRequest::getInt('first_aid_injuries');
        $report->injury_with_no_treatment = JRequest::getInt('injury_with_no_treatment');
        $report->traffic_accidents_involving_project_vehicles_equipment = JRequest::getInt('traffic_accidents_involving_project_vehicles_equipment');
        $report->accidents_involving_non_project_vehicles_or_property = JRequest::getInt('accidents_involving_non_project_vehicles_or_property');
        $report->environmental_incident = JRequest::getInt('environmental_incident');
        $report->escape_of_a_substance_into_the_atmosphere = JRequest::getInt('escape_of_a_substance_into_the_atmosphere');
        $report->utility_or_service_strike = JRequest::getInt('utility_or_service_strike');
        $report->damage_to_public_property_or_equipment = JRequest::getInt('damage_to_public_property_or_equipment');
        $report->damage_to_contractors_equipment = JRequest::getInt('damage_to_contractors_equipment');
        $report->worker_leaving_site_due_to_safety_concerns = JRequest::getInt('worker_leaving_site_due_to_safety_concerns');
        $report->staff_on_reduced_alternate_duties = JRequest::getInt('staff_on_reduced_alternate_duties');

        // upload files
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');

        $my_files = array($_FILES['ohsmp_updates_or_changes'], $_FILES['esss_monthly_report'], $_FILES['safety_officers_monthly_report'], $_FILES['other_safety_related_documents']);
        $uploaded_files = array();

        foreach($my_files as $file) { // upload file
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
            if($fileSize > 10000000)
                JError::raiseWarning(14, JText::_('FILE BIGGER THAN 10MB'));

            //check the file extension is ok
            $fileName = $file['name'];
            $uploadedFileNameParts = explode('.', $fileName);
            $uploadedFileExtension = array_pop($uploadedFileNameParts);

            $validFileExts = explode(',', 'pdf,doc,docx');

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

            //lose any special characters in the filename
            $fileName = ereg_replace("[^A-Za-z0-9.]", "-", $fileName);

            // generate random filename
            $fileName = uniqid('contractor_'.$report->report_month.'_') . '-' . $fileName;

            //always use constants when making file paths, to avoid the possibilty of remote file inclusion
            $uploadPath = JPATH_ADMINISTRATOR.'/components/com_cls/uploads/'.$fileName;

            if(!JFile::upload($fileTemp, $uploadPath)) {
                JError::raiseWarning(16, JText::_('ERROR MOVING FILE'));
                $uploaded_files[] = '';
                continue;
            } else {
                $uploaded_files[] = $fileName;
            }
        }

        list($report->ohsmp_updates_or_changes, $report->esss_monthly_report, $report->safety_officers_monthly_report, $report->other_safety_related_documents) = $uploaded_files;

        $query = $db->getQuery(true);
        $query->select('name')->from($db->quoteName('#__complaint_contracts'))->where($db->quoteName('id')." = ".$db->quote($report->contract_id));
        $db->setQuery($query);
        $contract_name = $db->loadResult();

        $query = $db->getQuery(true);
        $query->select('COUNT(*)')->from($db->quoteName('#__ohs_contractor_reporting'))->where($db->quoteName('contract_id')." = ".$db->quote($report->contract_id))->where($db->quoteName('report_month')." = ".$db->quote($report->report_month));
        $db->setQuery($query);
        $count = $db->loadResult();

        if($count == 0) {
            $db->insertObject('#__ohs_contractor_reporting', $report);
            clsLog('Contractor report submitted', 'Contractor report submitted for '.$contract_name.' for report month: ' . date('Y-m', strtotime($report->report_month)));
            $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('CLS_OHS_CONTRACTOR_FORM_SUBMITTED'));
        } else {
            $db->updateObject('#__ohs_contractor_reporting', $report, array('contract_id', 'report_month'));
            clsLog('Contractor report updated', 'Contractor report updated for '.$contract_name.' for report month: ' . date('Y-m', strtotime($report->report_month)));
            $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('CLS_OHS_CONTRACTOR_FORM_UPDATED'));
        }

    }

    function submitSupervisionReport() {

        $mainframe = JFactory::getApplication();
        $session = JFactory::getSession();
        $user = JFactory::getUser();
        $db = JFactory::getDBO();

        // remember input data
        $session->set('cls_number_of_serious_ohs_issues_during_the_month', JRequest::getInt('number_of_serious_ohs_issues_during_the_month'));
        $session->set('cls_serious_ohs_issues_reported', JRequest::getInt('serious_ohs_issues_reported'));
        $session->set('cls_serious_ohs_issues_reported_comment', JRequest::getVar('serious_ohs_issues_reported_comment'));
        $session->set('cls_number_of_days_worked', JRequest::getInt('number_of_days_worked'));
        $session->set('cls_number_of_full_inspections', JRequest::getInt('number_of_full_inspections'));
        $session->set('cls_number_of_partial_inspections', JRequest::getInt('number_of_partial_inspections'));
        $session->set('cls_trained_first_aid_officer_available', JRequest::getInt('trained_first_aid_officer_available'));
        $session->set('cls_trained_first_aid_officer_available_comment', JRequest::getVar('trained_first_aid_officer_available_comment'));
        $session->set('cls_first_aid_kits_available', JRequest::getInt('first_aid_kits_available'));
        $session->set('cls_first_aid_kits_available_comment', JRequest::getVar('first_aid_kits_available_comment'));
        $session->set('cls_transport_for_injured_personnel_available', JRequest::getInt('transport_for_injured_personnel_available'));
        $session->set('cls_transport_for_injured_personnel_available_comment', JRequest::getVar('transport_for_injured_personnel_available_comment'));
        $session->set('cls_emergency_transport_directions_available', JRequest::getInt('emergency_transport_directions_available'));
        $session->set('cls_emergency_transport_directions_available_comment', JRequest::getVar('emergency_transport_directions_available_comment'));
        $session->set('cls_plan_updated', JRequest::getInt('plan_updated'));
        $session->set('cls_plan_updated_comment', JRequest::getVar('plan_updated_comment'));
        $session->set('cls_plan_reviewed_submitted_approved', JRequest::getInt('plan_reviewed_submitted_approved'));
        $session->set('cls_plan_reviewed_submitted_approved_comment', JRequest::getVar('plan_reviewed_submitted_approved_comment'));
        $session->set('cls_number_of_workers_male', JRequest::getInt('number_of_workers_male'));
        $session->set('cls_number_of_workers_female', JRequest::getInt('number_of_workers_female'));
        $session->set('cls_total_hours_worked_during_month_male', JRequest::getInt('total_hours_worked_during_month_male'));
        $session->set('cls_total_hours_worked_during_month_female', JRequest::getInt('total_hours_worked_during_month_female'));
        $session->set('cls_percentage_of_workers_with_full_ppe_male', JRequest::getVar('percentage_of_workers_with_full_ppe_male'));
        $session->set('cls_percentage_of_workers_with_full_ppe_female', JRequest::getVar('percentage_of_workers_with_full_ppe_female'));
        $session->set('cls_violations_ppe_male', JRequest::getInt('violations_ppe_male'));
        $session->set('cls_violations_ppe_female', JRequest::getInt('violations_ppe_female'));
        $session->set('cls_warnings_ppe_male', JRequest::getInt('warnings_ppe_male'));
        $session->set('cls_warnings_ppe_female', JRequest::getInt('warnings_ppe_female'));
        $session->set('cls_repeat_warnings_ppe_male', JRequest::getInt('repeat_warnings_ppe_male'));
        $session->set('cls_repeat_warnings_ppe_female', JRequest::getInt('repeat_warnings_ppe_female'));
        $session->set('cls_violations_driving_male', JRequest::getInt('violations_driving_male'));
        $session->set('cls_violations_driving_female', JRequest::getInt('violations_driving_female'));
        $session->set('cls_warnings_driving_male', JRequest::getInt('warnings_driving_male'));
        $session->set('cls_warnings_driving_female', JRequest::getInt('warnings_driving_female'));
        $session->set('cls_repeat_warnings_driving_male', JRequest::getInt('repeat_warnings_driving_male'));
        $session->set('cls_repeat_warnings_driving_female', JRequest::getInt('repeat_warnings_driving_female'));
        $session->set('cls_violations_traffic_management_male', JRequest::getInt('violations_traffic_management_male'));
        $session->set('cls_violations_traffic_management_female', JRequest::getInt('violations_traffic_management_female'));
        $session->set('cls_warnings_traffic_management_male', JRequest::getInt('warnings_traffic_management_male'));
        $session->set('cls_warnings_traffic_management_female', JRequest::getInt('warnings_traffic_management_female'));
        $session->set('cls_repeat_warnings_traffic_management_male', JRequest::getInt('repeat_warnings_traffic_management_male'));
        $session->set('cls_repeat_warnings_traffic_management_female', JRequest::getInt('repeat_warnings_traffic_management_female'));
        $session->set('cls_violations_work_practice_male', JRequest::getInt('violations_work_practice_male'));
        $session->set('cls_violations_work_practice_female', JRequest::getInt('violations_work_practice_female'));
        $session->set('cls_warnings_work_practice_male', JRequest::getInt('warnings_work_practice_male'));
        $session->set('cls_warnings_work_practice_female', JRequest::getInt('warnings_work_practice_female'));
        $session->set('cls_repeat_warnings_work_practice_male', JRequest::getInt('repeat_warnings_work_practice_male'));
        $session->set('cls_repeat_warnings_work_practice_female', JRequest::getInt('repeat_warnings_work_practice_female'));
        $session->set('cls_violations_others_male', JRequest::getInt('violations_others_male'));
        $session->set('cls_violations_others_female', JRequest::getInt('violations_others_female'));
        $session->set('cls_warnings_others_male', JRequest::getInt('warnings_others_male'));
        $session->set('cls_warnings_others_female', JRequest::getInt('warnings_others_female'));
        $session->set('cls_repeat_warnings_others_male', JRequest::getInt('repeat_warnings_others_male'));
        $session->set('cls_repeat_warnings_others_female', JRequest::getInt('repeat_warnings_others_female'));
        $session->set('cls_no_children_are_working_on_the_project', JRequest::getInt('no_children_are_working_on_the_project'));
        $session->set('cls_number_of_children_for_the_month', JRequest::getInt('number_of_children_for_the_month'));
        $session->set('cls_children_are_working_on_the_project_comment', JRequest::getVar('children_are_working_on_the_project_comment'));
        $session->set('cls_workers_living_in_camps', JRequest::getInt('workers_living_in_camps'));
        $session->set('cls_number_of_expatriates_workers_in_camps', JRequest::getInt('number_of_expatriates_workers_in_camps'));
        $session->set('cls_number_of_local_workers_in_camps', JRequest::getInt('number_of_local_workers_in_camps'));
        $session->set('cls_date_of_last_inspection', JRequest::getVar('date_of_last_inspection'));
        $session->set('cls_facilities_in_compliance_with_local_laws_and_esmp', JRequest::getInt('facilities_in_compliance_with_local_laws_and_esmp'));
        $session->set('cls_facilities_in_compliance_with_local_laws_and_esmp_comment', JRequest::getVar('facilities_in_compliance_with_local_laws_and_esmp_comment'));
        $session->set('cls_proper_sanitation_facility', JRequest::getInt('proper_sanitation_facility'));
        $session->set('cls_proper_sanitation_facility_comment', JRequest::getVar('proper_sanitation_facility_comment'));
        $session->set('cls_appropriate_living_and_recreational_space_for_workers', JRequest::getInt('appropriate_living_and_recreational_space_for_workers'));
        $session->set('cls_appropriate_living_and_recreational_space_for_workers_comment', JRequest::getVar('appropriate_living_and_recreational_space_for_workers_comment'));
        $session->set('cls_recommendations_to_improve_living_conditions', JRequest::getVar('recommendations_to_improve_living_conditions'));
        $session->set('cls_number_of_vehicles_or_equipment_unsafe_or_improperly_maintained', JRequest::getInt('number_of_vehicles_or_equipment_unsafe_or_improperly_maintained'));
        $session->set('cls_recommendations_to_improve_vehicles_equipment', JRequest::getVar('recommendations_to_improve_vehicles_equipment'));
        $session->set('cls_recommendations_and_guidance_given_to_contractor', JRequest::getVar('recommendations_and_guidance_given_to_contractor'));
        $session->set('cls_actions_to_be_followed_up_on_next_month', JRequest::getVar('actions_to_be_followed_up_on_next_month'));

        // constructing the report object
        $report = new stdClass();
        $report->contract_id = JRequest::getInt('contract_id');
        $report->user_id = $user->id;
        $report->report_month = date('Y-m-d', strtotime(JRequest::getVar('report_month') . '-01'));
        $report->date_submitted = date('Y-m-d H:i:s');

        $report->number_of_serious_ohs_issues_during_the_month = JRequest::getInt('number_of_serious_ohs_issues_during_the_month');
        $report->serious_ohs_issues_reported = JRequest::getInt('serious_ohs_issues_reported') ? 'Y' : 'N';
        $report->serious_ohs_issues_reported_comment = JRequest::getVar('serious_ohs_issues_reported_comment');

        $report->number_of_days_worked = JRequest::getInt('number_of_days_worked');
        $report->number_of_full_inspections = JRequest::getInt('number_of_full_inspections');
        $report->number_of_partial_inspections = JRequest::getInt('number_of_partial_inspections');

        $report->trained_first_aid_officer_available = JRequest::getInt('trained_first_aid_officer_available') ? 'Y' : 'N';
        $report->trained_first_aid_officer_available_comment = JRequest::getVar('trained_first_aid_officer_available_comment');
        $report->first_aid_kits_available = JRequest::getInt('first_aid_kits_available') ? 'Y' : 'N';
        $report->first_aid_kits_available_comment = JRequest::getVar('first_aid_kits_available_comment');
        $report->transport_for_injured_personnel_available = JRequest::getInt('') ? 'Y' : 'N';
        $report->transport_for_injured_personnel_available_comment = JRequest::getVar('transport_for_injured_personnel_available');
        $report->emergency_transport_directions_available = JRequest::getInt('emergency_transport_directions_available') ? 'Y' : 'N';
        $report->emergency_transport_directions_available_comment = JRequest::getVar('emergency_transport_directions_available_comment');

        $report->plan_updated = JRequest::getInt('plan_updated') ? 'Y' : 'N';
        $report->plan_updated_comment = JRequest::getVar('plan_updated_comment');
        $report->plan_reviewed_submitted_approved = JRequest::getInt('plan_reviewed_submitted_approved') ? 'Y' : 'N';
        $report->plan_reviewed_submitted_approved_comment = JRequest::getVar('plan_reviewed_submitted_approved_comment');

        $report->number_of_workers_male = JRequest::getInt('number_of_workers_male');
        $report->number_of_workers_female = JRequest::getInt('number_of_workers_female');
        $report->total_hours_worked_during_month_male = JRequest::getInt('total_hours_worked_during_month_male');
        $report->total_hours_worked_during_month_female = JRequest::getInt('total_hours_worked_during_month_female');
        $report->percentage_of_workers_with_full_ppe_male = JRequest::getVar('percentage_of_workers_with_full_ppe_male');
        $report->percentage_of_workers_with_full_ppe_female = JRequest::getVar('percentage_of_workers_with_full_ppe_female');

        $report->violations_ppe_male = JRequest::getInt('violations_ppe_male');
        $report->violations_ppe_female = JRequest::getInt('violations_ppe_female');
        $report->warnings_ppe_male = JRequest::getInt('warnings_ppe_male');
        $report->warnings_ppe_female = JRequest::getInt('warnings_ppe_female');
        $report->repeat_warnings_ppe_male = JRequest::getInt('repeat_warnings_ppe_male');
        $report->repeat_warnings_ppe_female = JRequest::getInt('repeat_warnings_ppe_female');
        $report->violations_driving_male = JRequest::getInt('violations_driving_male');
        $report->violations_driving_female = JRequest::getInt('violations_driving_female');
        $report->warnings_driving_male = JRequest::getInt('warnings_driving_male');
        $report->warnings_driving_female = JRequest::getInt('warnings_driving_female');
        $report->repeat_warnings_driving_male = JRequest::getInt('repeat_warnings_driving_male');
        $report->repeat_warnings_driving_female = JRequest::getInt('repeat_warnings_driving_female');
        $report->violations_traffic_management_male = JRequest::getInt('violations_traffic_management_male');
        $report->violations_traffic_management_female = JRequest::getInt('violations_traffic_management_female');
        $report->warnings_traffic_management_male = JRequest::getInt('warnings_traffic_management_male');
        $report->warnings_traffic_management_female = JRequest::getInt('warnings_traffic_management_female');
        $report->repeat_warnings_traffic_management_male = JRequest::getInt('repeat_warnings_traffic_management_male');
        $report->repeat_warnings_traffic_management_female = JRequest::getInt('repeat_warnings_traffic_management_female');
        $report->violations_work_practice_male = JRequest::getInt('violations_work_practice_male');
        $report->violations_work_practice_female = JRequest::getInt('violations_work_practice_female');
        $report->warnings_work_practice_male = JRequest::getInt('warnings_work_practice_male');
        $report->warnings_work_practice_female = JRequest::getInt('warnings_work_practice_female');
        $report->repeat_warnings_work_practice_male = JRequest::getInt('repeat_warnings_work_practice_male');
        $report->repeat_warnings_work_practice_female = JRequest::getInt('repeat_warnings_work_practice_female');
        $report->violations_others_male = JRequest::getInt('violations_others_male');
        $report->violations_others_female = JRequest::getInt('violations_others_female');
        $report->warnings_others_male = JRequest::getInt('warnings_others_male');
        $report->warnings_others_female = JRequest::getInt('warnings_others_female');
        $report->repeat_warnings_others_male = JRequest::getInt('repeat_warnings_others_male');
        $report->repeat_warnings_others_female = JRequest::getInt('repeat_warnings_others_female');

        $report->no_children_are_working_on_the_project = JRequest::getInt('no_children_are_working_on_the_project') ? 'Y' : 'N';
        $report->number_of_children_for_the_month = JRequest::getInt('number_of_children_for_the_month');
        $report->children_are_working_on_the_project_comment = JRequest::getVar('children_are_working_on_the_project_comment');

        $report->workers_living_in_camps = JRequest::getInt('workers_living_in_camps') ? 'Y' : 'N';
        $report->number_of_expatriates_workers_in_camps = JRequest::getInt('number_of_expatriates_workers_in_camps');
        $report->number_of_local_workers_in_camps = JRequest::getInt('number_of_local_workers_in_camps');
        $report->date_of_last_inspection = JRequest::getVar('date_of_last_inspection');
        $report->facilities_in_compliance_with_local_laws_and_esmp = JRequest::getInt('facilities_in_compliance_with_local_laws_and_esmp') ? 'Y' : 'N';
        $report->facilities_in_compliance_with_local_laws_and_esmp_comment = JRequest::getVar('facilities_in_compliance_with_local_laws_and_esmp_comment');
        $report->proper_sanitation_facility = JRequest::getInt('proper_sanitation_facility') ? 'Y' : 'N';
        $report->proper_sanitation_facility_comment = JRequest::getVar('proper_sanitation_facility_comment');
        $report->appropriate_living_and_recreational_space_for_workers = JRequest::getInt('appropriate_living_and_recreational_space_for_workers') ? 'Y' : 'N';
        $report->appropriate_living_and_recreational_space_for_workers_comment = JRequest::getVar('appropriate_living_and_recreational_space_for_workers_comment');
        $report->recommendations_to_improve_living_conditions = JRequest::getVar('recommendations_to_improve_living_conditions');

        $report->number_of_vehicles_or_equipment_unsafe_or_improperly_maintained = JRequest::getInt('number_of_vehicles_or_equipment_unsafe_or_improperly_maintained');
        $report->recommendations_to_improve_vehicles_equipment = JRequest::getVar('recommendations_to_improve_vehicles_equipment');
        $report->recommendations_and_guidance_given_to_contractor = JRequest::getVar('recommendations_and_guidance_given_to_contractor');
        $report->actions_to_be_followed_up_on_next_month = JRequest::getVar('actions_to_be_followed_up_on_next_month');

        // upload files
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');

        $my_files = array($_FILES['monthtly_safety_officer_report'], $_FILES['other_safety_related_documents']);
        $uploaded_files = array();

        foreach($my_files as $file) { // upload file
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
            if($fileSize > 10000000)
                JError::raiseWarning(14, JText::_('FILE BIGGER THAN 10MB'));

            //check the file extension is ok
            $fileName = $file['name'];
            $uploadedFileNameParts = explode('.', $fileName);
            $uploadedFileExtension = array_pop($uploadedFileNameParts);

            $validFileExts = explode(',', 'pdf,doc,docx');

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

            //lose any special characters in the filename
            $fileName = ereg_replace("[^A-Za-z0-9.]", "-", $fileName);

            // generate random filename
            $fileName = uniqid('supervision_'.$report->report_month.'_') . '-' . $fileName;

            //always use constants when making file paths, to avoid the possibilty of remote file inclusion
            $uploadPath = JPATH_ADMINISTRATOR.'/components/com_cls/uploads/'.$fileName;

            if(!JFile::upload($fileTemp, $uploadPath)) {
                JError::raiseWarning(16, JText::_('ERROR MOVING FILE'));
                $uploaded_files[] = '';
                continue;
            } else {
                $uploaded_files[] = $fileName;
            }
        }

        list($report->monthtly_safety_officer_report, $report->other_safety_related_documents) = $uploaded_files;

        $query = $db->getQuery(true);
        $query->select('name')->from($db->quoteName('#__complaint_contracts'))->where($db->quoteName('id')." = ".$db->quote($report->contract_id));
        $db->setQuery($query);
        $contract_name = $db->loadResult();

        $query = $db->getQuery(true);
        $query->select('COUNT(*)')->from($db->quoteName('#__ohs_supervision_reporting'))->where($db->quoteName('contract_id')." = ".$db->quote($report->contract_id))->where($db->quoteName('report_month')." = ".$db->quote($report->report_month));
        $db->setQuery($query);
        $count = $db->loadResult();

        if($count == 0) {
            $db->insertObject('#__ohs_supervision_reporting', $report);
            clsLog('Supervision report submitted', 'Supervision report submitted for '.$contract_name.' for report month: ' . date('Y-m', strtotime($report->report_month)));
            $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('CLS_OHS_SUPERVISION_FORM_SUBMITTED'));
        } else {
            $db->updateObject('#__ohs_supervision_reporting', $report, array('contract_id', 'report_month'));
            clsLog('Supervision report updated', 'Supervision report updated for '.$contract_name.' for report month: ' . date('Y-m', strtotime($report->report_month)));
            $this->setRedirect(JRoute::_('index.php?option=com_cls&Itemid='.JRequest::getInt('Itemid')), JText::_('CLS_OHS_SUPERVISION_FORM_UPDATED'));
        }
    }

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