<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

jimport('joomla.application.component.controller');
require_once(JPATH_COMPONENT_ADMINISTRATOR.'/helpers/helper.php');

class clsController extends JControllerLegacy {
    /**
     * @var     string  The default view.
     * @since   1.6
     */
    protected $default_view = 'complaints';

    function __construct($default = array()) {
        parent::__construct($default);

        $this->registerTask('download_report', 'downloadReport');
        $this->registerTask('notify_sms_acknowledge', 'notifySMSAcknowledge');
        $this->registerTask('notify_email_acknowledge', 'notifyEmailAcknowledge');
        $this->registerTask('notify_inperson_acknowledge', 'notifyInPersonAcknowledge');
        $this->registerTask('notify_phone_acknowledge', 'notifyPhoneAcknowledge');
        $this->registerTask('notify_sms_resolve', 'notifySMSResolve');
        $this->registerTask('notify_email_resolve', 'notifyEmailResolve');
        $this->registerTask('notify_inperson_resolve', 'notifyInPersonResolve');
        $this->registerTask('notify_phone_resolve', 'notifyPhoneResolve');
        $this->registerTask('upload_picture', 'uploadPicture');
        $this->registerTask('reopen', 'reopenComplaint');
    }

    /**
     * Method to display a view.
     *
     * @param   boolean         If true, the view output will be cached
     * @param   array           An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return  JController     This object to support chaining.
     * @since   1.5
     */
    public function display($cachable = false, $urlparams = false) {
        // Load the submenu.
        clsHelper::addSubmenu('COM_CLS_SUBMENU_COMPLAINTS', 'complaints');
        clsHelper::addSubmenu('COM_CLS_SUBMENU_COMPLAINT_CATEGORIES', 'areas');
        clsHelper::addSubmenu('COM_CLS_SUBMENU_CONTRACTS', 'contracts');
        clsHelper::addSubmenu('COM_CLS_SUBMENU_SECTIONS', 'sections');
        clsHelper::addSubmenu('COM_CLS_SUBMENU_SUPPORT_GROUPS', 'supportgroups');
        clsHelper::addSubmenu('COM_CLS_SUBMENU_STATISTICS', 'reports');
        clsHelper::addSubmenu('COM_CLS_SUBMENU_ACTIVITY_LOG', 'notifications');

        parent::display();

        return $this;
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

    function notifyInPersonAcknowledge() {
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

        clsLog('Acknowledgment made in person', "Acknowledgment made in person for complaint #$complaint->message_id");

        $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('In person acknowledgement successfully recorded'));
    }

    function notifyPhoneAcknowledge() {
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

        clsLog('Acknowledgment made by phone', "Acknowledgment made by phone for complaint #$complaint->message_id");

        $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('Phone acknowledgement successfully recorded'));
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

    function notifyInPersonResolve() {
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

        clsLog('Resolution acknowledgment made in person', "Resolution acknowledgment made in person for complaint #$complaint->message_id");

        $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('In person resolution acknowledgement successfully recorded'));
    }

    function notifyPhoneResolve() {
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

        clsLog('Resolution acknowledgment made by phone', "Resolution acknowledgment made by phone for complaint #$complaint->message_id");

        $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('Phone resolution acknowledgement successfully recorded'));
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

    function reopenComplaint() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $id   = JRequest::getInt('id', 0);

        $user_type = $user->getParam('role', 'Guest');
        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Supervisor' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $db->setQuery('select message_id, resolution from #__complaints where id = ' . $id);
        $complaint = $db->loadObject();

        $db->setQuery('update #__complaints set resolver_id = 0, resolution = "", date_resolved = null, confirmed_closed = "N" where id = ' . $id);
        $db->query();

        clsLog('Complaint action taken', 'Complaint #' . $complaint->message_id . " has been reopened with reason message: \n" . JRequest::getVar('reopen_reason') . "\n\nOld resolution was: \n" . $complaint->resolution);

        // send reopen notification to "interested" parties
        $config =& JComponentHelper::getParams('com_cls');

        $query = array();
        // Send notification to Supervisors
        $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and params like '%\"role\":\"Supervisor\"%')";

        // Send notification to Level 1 and System Administrator
        $query[] = "(select email, name, params from #__users where params like '%\"receive_notifications\":\"1\"%' and (params like '%\"role\":\"System Administrator\"%' or params like '%\"role\":\"Level 1\"%'))";

        $query = implode(' UNION ALL ', $query);

        $db->setQuery($query);
        $rows = $db->loadRowList();

        jimport('joomla.mail.mail');
        $mail = new JMail();
        $mail->From = $config->get('complaints_email');
        $mail->FromName = 'Complaint Logging System';
        $mail->Subject = 'Complaint Reopened: #' . $complaint->message_id;
        $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
        $mail->msgHTML('<p>A complaint has been Re-Opened. Login to '.JURI::base().'administrator/index.php?option=com_cls to resolve it.</p>' . JRequest::getVar('reopen_reason'));
        $mail->AddReplyTo('no_reply@'.$_SERVER['HTTP_HOST']);
        foreach($rows as $row) {
            $params = json_decode($row[2]);
            if($params->receive_by_email == "1") { // send email notification
                $mail->AddAddress($row[0]);
                clsLog('Reopened notification sent', 'Complaint #' . $complaint->message_id . ' reopened notification sent to ' . $row[1]);
            }

            if($params->receive_by_sms == "1") { // send sms notification
                $telephone = $params->telephone;
                if(!empty($telephone)) {
                    $db->setQuery("insert into #__complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($id, 'CLS', '$telephone', 'Complaint #$complaint->message_id has been reopened, please login to the system to resolve it.', now(), 'Notification')");
                    $db->query();

                    clsLog('Reopened notification sent', 'Complaint #' . $complaint->message_id . ' reopened notification sent to ' . $telephone);
                }
            }
        }
        $mail->Send();

        $this->setRedirect('index.php?option=com_cls&task=complaint.edit&id='.$id, JText::_('Complaint has been Reopened'));
    }

    function downloadReport() {
        $db   = JFactory::getDBO();
        $user = JFactory::getUser();
        $doc  = JFactory::getDocument();

        $user_type = $user->getParam('role', 'Guest');

        // guest cannot see this list
        if($user_type == 'Guest' or $user_type == 'Level 2') {
            $this->setRedirect('index.php?option=com_cls&view=reports', JText::_("You don't have permission"));
            return;
        }

        $session = JFactory::getSession();
        $config = JComponentHelper::getParams('com_cls');

        $statistics_period = (int) $config->get('statistics_period', 20);
        $startdate = JRequest::getCmd('startdate', $session->get('startdate', date('Y-m-d', strtotime("-$statistics_period days")), 'com_cls'));
        $session->set('startdate', $startdate, 'com_cls');
        $enddate = JRequest::getCmd('enddate', $session->get('enddate', date('Y-m-d'), 'com_cls'));
        $session->set('enddate', $enddate, 'com_cls');

        $period = JRequest::getVar('period', 'all');

        $query = 'select c.*, e.name as editor, r.name as resolver, a.area as complaint_area from #__complaints as c left join #__complaint_areas as a on (c.complaint_area_id = a.id) left join #__users as e on (c.editor_id = e.id) left join #__users as r on (c.resolver_id = r.id)';

        switch($period) {
            case 'period': $query .= " where date_received >= '$startdate 00:00:00' and date_received <= '$enddate 23:59:59'"; break;
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
}
