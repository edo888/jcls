<?php
// extension=php_win32service.dll is required

define('MYSQL_HOST', '127.0.0.1');
define('MYSQL_USER', 'user');
define('MYSQL_PASS', 'pass');
define('MYSQL_DB', 'db');
define('IMAP_MAILBOX', '{mail.test.com:110/pop3/novalidate-cert}INBOX');
define('EMAIL_HOST', 'mail.test.com');
define('COMPLAINTS_EMAIL', 'complaints@test.com');
define('EMAIL_PASS', 'password');
define('NO_REPLY', 'no_reply@test.com');
define('OUTGOING_PATH', 'C:\cygwin\var\spool\sms\outgoing');

if($argv[1] == 'install') {
    $a = win32_create_service(array(
        'service' => 'COMPLAINTS',                 # the name of your service
        'display' => 'Receive email complaints and direct them to the website', # description
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
            $body = imap_body($mbox, $i);

            $from_name = mysql_real_escape_string($header->from[0]->personal);
            $from      = mysql_real_escape_string($header->from[0]->mailbox.'@'.$header->from[0]->host);
            $subject   = mysql_real_escape_string($header->subject);
            $msg       = mysql_real_escape_string($body);

            // generating message_id
            $date = date('Y-m-d');
            $query = "select count(*) from jos_complaints where date_received >= '$date 00:00:00' and date_received <= '$date 23:59:59'";
            $res = mysql_query($query);
            $count = mysql_result($res, 0, 0);
            if($count == 0) { // reset the counter for current day
                mysql_query('delete from jos_complaint_message_ids');
                mysql_query('alter table jos_complaint_message_ids auto_increment = 0');
            }
            mysql_query('insert into jos_complaint_message_ids value(null)');
            $id = mysql_insert_id();
            $message_id = $date.'-'.str_pad($id, 4, '0', STR_PAD_LEFT);
            #echo 'Message received: ', $message_id, "\n";

            // generating raw message
            $msg = 'Subject: ' . $subject . "\n\n" . $msg;

            $query = "insert into jos_complaints (message_id, name, email, raw_message, message_source, date_received) value('$message_id', '$from_name', '$from', '$msg', 'Email', now())";
            mysql_query($query);
            $complaint_id = mysql_insert_id();

            // log
            $query = "insert into jos_complaint_notifications values(null, 0, 'New email complaint', now(), 'New email complaint #{$message_id} arrived')";
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

                // TODO: save file in an appropriate place
                file_put_contents($fileName, $attachment['attachment']);
            }

            // TODO: send complaint to members
            $res = mysql_query("select email, name, rand() as r from jos_users where params like '%receive_raw_messages=1%' order by r limit 3");
            //$res = mysql_query("select email, rand() as r from jos_users where params like '%receive_raw_messages=1%' and id = 63 order by r limit 3");
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
                $mail->AddAddress($row[0]);

                // log
                $query = "insert into jos_complaint_notifications values(null, 0, 'New email complaint notification', now(), 'New complaint #{$message_id} notification has been sent to $row[1]')";
                mysql_query($query);
            }
            $mail->Send();

            imap_delete($mbox, $i);
        }

        // check sms queue
        $res = mysql_query("select q.*, c.message_id from jos_complaint_message_queue as q left join jos_complaints as c on (q.complaint_id = c.id) where q.status = 'Pending'");
        while($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
            $sms_body = "From: $row[msg_from]\n";
            $sms_body .= "To: $row[msg_to]\n";
            $sms_body .= "MsgID: $row[id]\n";
            $sms_body .= "Alphabet: ISO\n\n";
            $sms_body .= $row['msg'];
            file_put_contents(OUTGOING_PATH.'\out_'.$row['id'].'.txt', $sms_body);
            mysql_query("update jos_complaint_message_queue set status = 'Outgoing' where id = " . $row['id']);

            // log
            $query = "insert into jos_complaint_notifications values(null, 0, 'SMS notification status changed', now(), 'Complaint #{$row[message_id]} SMS notification status changed to Outgoing')";
            mysql_query($query);
        }

        if($num == 0) {
            #echo 'No messages received', "\n";
        } else {
            imap_expunge($mbox);
        }

        imap_close($mbox);
        mysql_close($lnk);
        sleep(10);
    }
}