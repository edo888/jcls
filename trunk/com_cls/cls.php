<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

defined("_JEXEC") or die("Restricted access");

jimport('joomla.application.component.controller');

class CLSController extends JController {
    function __construct($default = array()) {
        parent::__construct($default);
        $this->registerTask('submit', 'submitComplaint');
    }

    function display() {
        $session =& JFactory::getSession();
    ?>
    <h2><?php echo JText::_('COMPLAINT_FORM_HEAD') ?></h2>
    <script type="text/javascript">
    function validate() {
        // email
        var email = /^([a-zA-Z0-9._\-]+@[a-zA-Z0-9._\-]+\.[a-zA-Z]{2,4}){0,1}$/;
        if(email.test(document.complaint_form.email.value) == false) {
            alert('Invalid email.');
            return false;
        }

        // telephone
        var tel = /^([0-9]{11}){0,1}$/;
        if(tel.test(document.complaint_form.tel.value) == false) {
            alert('Invalid telephone number.');
            return false;
        }

        // message
        if(document.complaint_form.msg.value == '') {
            alert('You need to enter a complaint message.');
            return false;
        }

        // captcha
        if(document.complaint_form.captcha.value == '') {
            alert('You need to enter the captcha.');
            return false;
        }

        return true;
    }
    </script>
    <form name="complaint_form" action="index.php" method="post" onsubmit="return validate();">
        <table>
            <tr>
                <td><?php echo JText::_('CLS_NAME') ?>:</td>
                <td><input type="text" style="width:250px;" name="name" size="30" maxlength="150" value="<?php echo $session->get('cls_name'); ?>" /></td>
            </tr>
            <tr>
                <td><?php echo JText::_('CLS_E-MAIL') ?>:</td>
                <td><input type="text" style="width:250px;" name="email" size="30" maxlength="150" value="<?php echo $session->get('cls_email'); ?>"/> ex. username@netsys.am</td>
            </tr>
            <tr>
                <td><?php echo JText::_('CLS_TELEPHONE') ?>:</td>
                <td><input type="text" style="width:250px;" name="tel" size="30" maxlength="150" value="<?php echo $session->get('cls_tel'); ?>"/>  ex. 37491123456</td>
            </tr>
            <tr>
                <td><?php echo JText::_('CLS_MESSAGE') ?>:</td>
                <td><textarea name="msg" cols="50" rows="8"><?php echo $session->get('cls_msg'); ?></textarea></td>
            </tr>
            <tr>
                <td><?php echo JText::_('CLS_CAPTCHA') ?>:</td>
                <td><input type="text" name="captcha" size="8" maxlength="5" /> <img src="<?php echo JURI::base(true); /*data:*/ ?>/components/com_cls/base64.php?image/gif;base64,<?php self::createCaptcha() ?>" alt="captcha" border="0" /></td>
            </tr>

            <tr>
                <td>&nbsp;</td>
                <td><input type="submit" value="<?php echo JText::_('CLS_SUBMIT') ?>"></td>
            </tr>
        </table>
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="task" value="submit" />
        <input type="hidden" name="Itemid" value="<?php echo JRequest::getInt('Itemid') ?>" />
    </form>
    <?php
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

    function createCaptcha() {
        $secret = strtoupper(substr(sha1(mt_rand(0, 500).microtime()), 2, 5));
        $session =& JFactory::getSession();
        $session->set('cls_captcha', $secret);

        $img = imagecreatefromgif('components/com_cls/captcha.gif');
        $width  = imagesx($img);
        $height = imagesy($img);

        for($i = 0; $i < 5; $i++) {
            $color = imagecolorallocate($img, rand(0, 200), rand(0, 200), rand(0, 200));
            imagettftext($img, 12, mt_rand(-30, 30), 15 + $i * 12, 15, $color, 'components/com_cls/lsans.ttf', $secret{$i});
        }

        ob_start();
        imagegif($img);
        $img = ob_get_contents();
        ob_end_clean();

        echo base64_encode($img);
    }
}

$controller = new CLSController(array('default_task' => 'display'));

$task = JRequest::getVar('task');
$controller->execute(JRequest::getVar('task'));
$controller->redirect();