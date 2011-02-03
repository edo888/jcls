<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');
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
<form name="complaint_form" action="index.php" method="post" enctype="multipart/form-data" onsubmit="return validate();">
    <table>
        <tr>
            <td><?php echo JText::_('CLS_NAME') ?>:</td>
            <td><input type="text" style="width:250px;" name="name" size="30" maxlength="150" value="<?php echo $this->session->get('cls_name'); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_E-MAIL') ?>:</td>
            <td><input type="text" style="width:250px;" name="email" size="30" maxlength="150" value="<?php echo $this->session->get('cls_email'); ?>"/> ex. username@netsys.am</td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_TELEPHONE') ?>:</td>
            <td><input type="text" style="width:250px;" name="tel" size="30" maxlength="150" value="<?php echo $this->session->get('cls_tel'); ?>"/>  ex. 37491123456</td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_MESSAGE') ?>:</td>
            <td><textarea name="msg" cols="50" rows="8"><?php echo $this->session->get('cls_msg'); ?></textarea></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_PICTURES') ?>:</td>
            <td>
                <input type="file" name="pictures[]" /><br />
                <input type="file" name="pictures[]" /><br />
                <input type="file" name="pictures[]" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_CAPTCHA') ?>:</td>
            <td><input type="text" name="captcha" size="8" maxlength="5" /> <img src="<?php echo JURI::base(true); ?>/components/com_cls/base64.php?image/gif;base64,<?php echo $this->captcha; ?>" alt="captcha" border="0" /></td>
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