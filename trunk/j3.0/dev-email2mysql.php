<?php
// extension=php_win32service.dll is required

define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_USER', 'user');
define('MYSQL_PASS', 'pass');
define('MYSQL_DB', 'db');
define('MYSQL_DB_PREFIX', 'jos_');
define('IMAP_MAILBOX', '{mail.test.com:110/pop3/novalidate-cert}INBOX');
define('EMAIL_HOST', 'mail.test.com');
define('COMPLAINTS_EMAIL', 'complaints@test.com');
define('ACKNOWLEDGMENT_TEXT', 'Thank you, your complaint #%s is received. You will get further details soon. CLS'); # replace CLS with your site name
define('EMAIL_PASS', 'password');
define('NO_REPLY', 'no_reply@test.com');
define('OUTGOING_PATH', 'C:\cygwin\var\spool\sms\outgoing');
define('FTP_HOST', 'ftp.test.com');
define('FTP_PORT', '21');
define('FTP_USER', 'user');
define('FTP_PASS', 'pass');
define('FTP_ROOT', '/httpdocs/administrator/components/com_cls/pictures');
define('NOTIFY_USER_COUNT', '3');
define('SLEEP_TIME', 10); // seconds

define('SITENAME', 'CLS');
define('ACKNOWLEDGMENT_SMS', 'Yes');
define('ACKNOWLEDGMENT_TEXT', 'Thank you, your complaint #%s is received. You will get further details soon. ' . SITENAME);

define('INCOMING_DIR', 'C:\cygwin\var\spool\sms\incoming');
define('CONFIRMED_DIR', 'C:\cygwin\var\spool\sms\confirmed');

if($argv[1] == 'install') {
    $a = win32_create_service(array(
        'service' => 'COMPLAINTS',                 # the name of your service
        'display' => 'Receive email and sms complaints and direct them to the website', # description
        'params' => '"' . __FILE__ . '"' . ' run', # path to the script and parameters
    ));
    echo $a,': ', system('net helpmsg ' . $a);

} elseif($argv[1] == 'uninstall') {
    $a = win32_delete_service('COMPLAINTS');
    echo $a,': ', system('net helpmsg ' . $a);

} elseif($argv[1] == 'start') {
    $a = win32_start_service('COMPLAINTS');
    echo $a,': ', system('net helpmsg ' . $a);

} elseif($argv[1] == 'stop') {
    $a = win32_stop_service('COMPLAINTS');
    echo $a,': ', system('net helpmsg ' . $a);

} elseif($argv[1] == 'run') {
    win32_start_service_ctrl_dispatcher('COMPLAINTS') or die("I'm probably not running under the service control manager");
    win32_set_service_status(WIN32_SERVICE_RUNNING);

    require_once 'class.phpmailer.php';

    while(1) {
        switch(win32_get_last_control_message()) {
            case 0: win32_set_service_status(WIN32_SERVICE_RUNNING); break;
            case WIN32_SERVICE_CONTROL_CONTINUE: break; # Continue server routine
            case WIN32_SERVICE_CONTROL_INTERROGATE: win32_set_service_status(WIN32_SERVICE_RUNNING); break; # Respond with status
            case WIN32_SERVICE_CONTROL_STOP: win32_set_service_status(WIN32_SERVICE_STOPPED); exit; # Terminate script
            default: win32_set_service_status(WIN32_ERROR_CALL_NOT_IMPLEMENTED); # Add more cases to handle other service calls
        }

        // connect to database
        $lnk = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS);
        mysql_select_db(MYSQL_DB);

        // check email
        $mbox = imap_open(IMAP_MAILBOX, COMPLAINTS_EMAIL, EMAIL_PASS);
        $num = imap_num_msg($mbox);

        for($i = 1; $i <= $num; $i++) {
            $header = imap_header($mbox, $i);
            $body = imap_fetchbody($mbox, $i, '1.2');
            if(!strlen($body) > 0)
                $body = imap_fetchbody($mbox, $i, 1);

            $from_name = mysql_real_escape_string($header->from[0]->personal);
            $from      = mysql_real_escape_string($header->from[0]->mailbox.'@'.$header->from[0]->host);
            $subject   = mysql_real_escape_string($header->subject);
            $msg       = mysql_real_escape_string($body);

            // generating message_id
            $date = date('Y-m-d');
            $query = "select count(*) from ".MYSQL_DB_PREFIX."complaints where date_received >= '$date 00:00:00' and date_received <= '$date 23:59:59'";
            $res = mysql_query($query);
            $count = mysql_result($res, 0, 0);
            if($count == 0) { // reset the counter for current day
                mysql_query('delete from '.MYSQL_DB_PREFIX.'complaint_message_ids');
                mysql_query('alter table '.MYSQL_DB_PREFIX.'complaint_message_ids auto_increment = 0');
            }
            mysql_query('insert into '.MYSQL_DB_PREFIX.'complaint_message_ids value(null)');
            $id = mysql_insert_id();
            $message_id = $date.'-'.str_pad($id, 4, '0', STR_PAD_LEFT);
            #echo 'Message received: ', $message_id, "\n";

            // generating raw message
            $msg = 'Subject: ' . $subject . "\n\n" . $msg;

            $query = "insert into ".MYSQL_DB_PREFIX."complaints (message_id, name, email, raw_message, message_source, date_received) value('$message_id', '$from_name', '$from', '$msg', 'Email', now())";
            mysql_query($query);
            $complaint_id = mysql_insert_id();

            // log
            $query = "insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'New email complaint', now(), 'New email complaint #{$message_id} arrived')";
            mysql_query($query);

            // fetch attachements
            $structure = imap_fetchstructure($mbox, $i);
            $attachments = array();
            if(isset($structure->parts) && count($structure->parts)) {
                for($j = 0; $j < count($structure->parts); $j++) {
                    $attachments[$j] = array(
                        'is_attachment' => false,
                        'filename' => '',
                        'name' => '',
                        'attachment' => ''
                    );

                    if($structure->parts[$j]->ifdparameters) {
                        foreach($structure->parts[$j]->dparameters as $object) {
                            if(strtolower($object->attribute) == 'filename') {
                                $attachments[$j]['is_attachment'] = true;
                                $attachments[$j]['filename'] = $object->value;
                            }
                        }
                    }

                    if($structure->parts[$j]->ifparameters) {
                        foreach($structure->parts[$j]->parameters as $object) {
                            if(strtolower($object->attribute) == 'name') {
                                $attachments[$j]['is_attachment'] = true;
                                $attachments[$j]['name'] = $object->value;
                            }
                        }
                    }

                    if($attachments[$j]['is_attachment']) {
                        $attachments[$j]['attachment'] = imap_fetchbody($mbox, $i, $j+1);
                        if($structure->parts[$j]->encoding == 3) { // 3 = BASE64
                            $attachments[$j]['attachment'] = base64_decode($attachments[$j]['attachment']);
                        }
                        elseif($structure->parts[$j]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                            $attachments[$j]['attachment'] = quoted_printable_decode($attachments[$j]['attachment']);
                        }
                    }

                    if(!$attachments[$j]['is_attachment'])
                        unset($attachments[$j]);
                }
            }

            foreach($attachments as $attachment) {
                $fileName = $attachment['filename'];

                //lose any special characters in the filename
                $fileName = ereg_replace("[^A-Za-z0-9.]", "-", $fileName);
                // generate random filename
                $fileName = uniqid($complaint_id.'_') . '-' . $fileName;

                // save file
                file_put_contents(dirname(__FILE__).'/'.$fileName, $attachment['attachment']);

                // check file mime type and upload file to the server

                //check the file extension is ok
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
                    // delete file
                    unlink(dirname(__FILE__).'/'.$fileName);
                    continue;
                }

                //for security purposes, we will also do a getimagesize on the temp file (before we have moved it
                //to the folder) to check the MIME type of the file, and whether it has a width and height
                $imageinfo = getimagesize(dirname(__FILE__).'/'.$fileName);

                //we are going to define what file extensions/MIMEs are ok, and only let these ones in (whitelisting), rather than try to scan for bad
                //types, where we might miss one (whitelisting is always better than blacklisting)
                $okMIMETypes = 'image/jpeg,image/pjpeg,image/png,image/x-png,image/gif';
                $validFileTypes = explode(",", $okMIMETypes);

                //if the temp file does not have a width or a height, or it has a non ok MIME, return
                if(!is_int($imageinfo[0]) or !is_int($imageinfo[1]) or  !in_array($imageinfo['mime'], $validFileTypes)) {
                    // delete file
                    unlink(dirname(__FILE__).'/'.$fileName);
                    continue;
                }

                $ftp_id = ftp_connect(FTP_HOST, FTP_PORT);
                ftp_login($ftp_id, FTP_USER, FTP_PASS);
                ftp_pasv($ftp_id, true);
                if(ftp_put($ftp_id, FTP_ROOT.'/'.$fileName, dirname(__FILE__).'/'.$fileName, FTP_BINARY)) {
                    // inserting picture into database
                    $query = "insert into ".MYSQL_DB_PREFIX."complaint_pictures value (null, $complaint_id, 'components/com_cls/pictures/$fileName')";
                    mysql_query($query);
                }
                ftp_close($ftp_id);

                // delete file
                unlink(dirname(__FILE__).'/'.$fileName);
            }

            // send new complaint acknowlegment to complainer
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = EMAIL_HOST;
            $mail->Port = 25;
            $mail->Username = COMPLAINTS_EMAIL;
            $mail->Password = EMAIL_PASS;
            $mail->From = COMPLAINTS_EMAIL;
            $mail->FromName = 'Complaint Logging System';
            $mail->Subject = 'Complaint Received: #' . $message_id;
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->msgHTML('<p>'.sprintf(ACKNOWLEDGMENT_TEXT, $message_id).'</p>');
            $mail->AddReplyTo(NO_REPLY);
            $mail->AddAddress($from);
            $mail->Send();

            // log
            $query = "insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'Email acknowledgment sent', now(), 'Email acknowledgment has been sent to $from for complaint #$message_id')";
            mysql_query($query);

            // send new complaint notification to members
            $res = mysql_query("select email, name, params from ".MYSQL_DB_PREFIX."users where params like '%\"receive_notifications\":\"1\"%' and (params like '%\"role\":\"Supervisor\"%' or params like '%\"role\":\"Level 1\"%')");
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = EMAIL_HOST;
            $mail->Port = 25;
            $mail->Username = COMPLAINTS_EMAIL;
            $mail->Password = EMAIL_PASS;
            $mail->From = COMPLAINTS_EMAIL;
            $mail->FromName = 'Complaint Logging System';
            $mail->Subject = 'New Email Complaint: #' . $message_id;
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->msgHTML('<p>New complaint received from ' . htmlspecialchars($header->fromaddress) . '. Login to http://www.test.com/administrator/index.php?option=com_cls to process it.</p>' . $body);
            $mail->AddReplyTo(NO_REPLY);
            while($row = mysql_fetch_array($res, MYSQL_NUM)) {
                $params = json_decode($row[2]);
                if($params->receive_by_email == "1") { // send email notification
                    $mail->AddAddress($row[0]);

                    // log
                    $query = "insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'New email complaint notification', now(), 'New complaint #{$message_id} notification has been sent to $row[0]')";
                    mysql_query($query);
                }

                if($params->receive_by_sms == "1") { // send sms notification
                    $telephone = $params->telephone;
                    if(!empty($telephone)) {
                        $query = "insert into ".MYSQL_DB_PREFIX."complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) value($complaint_id, 'CLS', '$telephone', 'New complaint received, please login to the system to process it.', now(), 'Notification')";
                        mysql_query($query);

                        // log
                        $query = "insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'New SMS complaint notification', now(), 'New complaint #{$message_id} notification has been sent to $telephone')";
                        mysql_query($query);
                    }
                }
            }
            $mail->Send();

            imap_delete($mbox, $i);
        }

        // check new sms
        $ds = scandir(INCOMING_DIR);
        foreach($ds as $file) {
            if(!is_file(INCOMING_DIR.'/'.$file))
                continue;

            $contents = file_get_contents(INCOMING_DIR.'/'.$file);
            preg_match('/FROM: (.*?)\n/i', $contents, $matches);
            $from = mysql_real_escape_string(trim($matches[1]));
            unset($matches);

            preg_match('/\n\n(.*)/i', $contents, $matches);
            $msg = mysql_real_escape_string(trim($matches[1]));
            unset($matches);

            if(empty($from) or empty($msg))
                continue;

            // generating message_id
            $date = date('Y-m-d');
            $query = "select count(*) from ".MYSQL_DB_PREFIX."complaints where date_received >= '$date 00:00:00' and date_received <= '$date 23:59:59'";
            $res = mysql_query($query);
            $count = mysql_result($res, 0, 0);
            if($count == 0) { // reset the counter for current day
                mysql_query('delete from '.MYSQL_DB_PREFIX.'complaint_message_ids');
                mysql_query('alter table '.MYSQL_DB_PREFIX.'complaint_message_ids auto_increment = 0');
            }
            mysql_query('insert into '.MYSQL_DB_PREFIX.'complaint_message_ids value(null)');
            $id = mysql_insert_id();
            $message_id = $date.'-'.str_pad($id, 4, '0', STR_PAD_LEFT);

            mysql_query("insert into ".MYSQL_DB_PREFIX."complaints (message_id, phone, raw_message, message_source, date_received) value('$message_id', '$from', '$msg', 'SMS', now())");
            // log
            mysql_query("insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'New SMS complaint', now(), 'New SMS complaint #{$message_id} arrived')");

            // acknowledge
            if(ACKNOWLEDGMENT_SMS == 'Yes') {
                $acknowledgment_text = sprintf(ACKNOWLEDGMENT_TEXT, $message_id);
                mysql_query("insert into ".MYSQL_DB_PREFIX."complaint_message_queue (complaint_id, msg_from, msg_to, msg, date_created, msg_type) values('$id', '".SITENAME."', '$from', '$acknowledgment_text', now(), 'Acknowledgment')");
                // log
                mysql_query("insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'SMS acknowledgment queued', now(), 'SMS acknowledgment queued to be sent to $from for complaint #{$message_id}')");
            }

            // email new complaint notification to members
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth = true;
            $mail->Host = EMAIL_HOST;
            $mail->Port = 25;
            $mail->Username = COMPLAINTS_EMAIL;
            $mail->Password = EMAIL_PASS;
            $mail->From = COMPLAINTS_EMAIL;
            $mail->FromName = 'Complaint Logging System';
            $mail->Subject = 'New SMS Complaint: #' . $message_id;
            $mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
            $mail->msgHTML('<p>New complaint received from ' . $from . '. Login to http://www.test.com/administrator/index.php?option=com_cls to process it.</p>' . $msg);
            $mail->AddReplyTo(NO_REPLY);
            $res = mysql_query("select email from ".MYSQL_DB_PREFIX."users where params like '%\"receive_notifications\":\"1\"%' and (params like '%\"role\":\"Level 1\"%' or params like '%System Administrator%')");
            $members = array();
            while($row = mysql_fetch_object($res)) {
                $mail->AddAddress($row->email);
                $members[] = $row->email;
            }
            $mail->Send();
            $members = implode(', ', $members);

            // log
            mysql_query("insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'New SMS complaint notification', now(), 'New SMS complaint #{$message_id} notification has been sent to $members')");

            // moving file to confirmed directory
            rename(INCOMING_DIR.'/'.$file, CONFIRMED_DIR.'/'.$file);
        }

        // check sms queue
        $res = mysql_query("select q.*, c.message_id from ".MYSQL_DB_PREFIX."complaint_message_queue as q left join ".MYSQL_DB_PREFIX."complaints as c on (q.complaint_id = c.id) where q.status = 'Pending'");
        while($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
            $sms_body = "From: $row[msg_from]\n";
            $sms_body .= "To: $row[msg_to]\n";
            $sms_body .= "MsgID: $row[id]\n";
            $sms_body .= "Alphabet: ISO\n\n";
            $sms_body .= $row['msg'];
            file_put_contents(OUTGOING_PATH.'\out_'.$row['id'].'.txt', $sms_body);
            mysql_query("update ".MYSQL_DB_PREFIX."complaint_message_queue set status = 'Outgoing' where id = " . $row['id']);

            // log
            $query = "insert into ".MYSQL_DB_PREFIX."complaint_notifications values(null, 0, 'SMS notification status changed', now(), 'Complaint #{$row[message_id]} SMS notification status changed to Outgoing')";
            mysql_query($query);
        }

        if($num == 0) {
            #echo 'No messages received', "\n";
        } else {
            imap_expunge($mbox);
        }

        imap_close($mbox);
        mysql_close($lnk);
        sleep(SLEEP_TIME);
    }
}