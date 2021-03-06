<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

$config =& JComponentHelper::getParams('com_cls');
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
    var tel = /^([0-9]{4,15}){0,1}$/;
    if(tel.test(document.complaint_form.tel.value) == false) {
        alert('Invalid telephone number.');
        return false;
    }

    <?php if($config->get('show_gender') and $config->get('gender_required')): ?>
    // gender
    if(document.complaint_form.gender.value == '') {
        alert('You need to specify your gender');
        return false;
    }
    <?php endif; ?>

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

function toggleGBV() {
    if(document.getElementById('gbv_yes').checked) {
        document.getElementById('gbv_type_field').style.display="table-row";
        document.getElementById('gbv_related_field').style.display="table-row";
    } else {
        document.getElementById('gbv_type_field').style.display="none";
        document.getElementById('gbv_related_field').style.display="none";
    }
}
</script>
<form name="complaint_form" action="index.php" method="post" enctype="multipart/form-data" onsubmit="return validate();">
    <table>
        <tr>
            <td><?php echo JText::_('CLS_NAME') ?>:</td>
            <td><input type="text" style="width:250px;" name="name" size="30" maxlength="100" value="<?php echo $this->session->get('cls_name'); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_E-MAIL') ?>:</td>
            <td><input type="text" style="width:250px;" name="email" size="30" maxlength="100" value="<?php echo $this->session->get('cls_email'); ?>"/></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_TELEPHONE') ?>:</td>
            <td><input type="text" style="width:250px;" name="tel" size="30" maxlength="100" value="<?php echo $this->session->get('cls_tel'); ?>"/>  ex. 37491123456</td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_ADDRESS') ?>:</td>
            <td><input type="text" style="width:320px;" name="address" placeholder="<?php echo JText::_('CLS_ADDRESS_PLACEHOLDER') ?>" size="45" maxlength="250" value="<?php echo $this->session->get('cls_address'); ?>"/></td>
        </tr>
        <?php if($config->get('show_gender')): ?>
        <tr>
            <td><?php echo JText::_('CLS_GENDER') ?>:</td>
            <td><input type="radio" name="gender" id="gender_male" value="Male" <?php if($this->session->get('cls_gender') == 'Male') echo 'checked '; ?>/> <label for="gender_male"><?php echo JText::_('CLS_MALE') ?></label> <input type="radio" name="gender" id="gender_female" value="Female" <?php if($this->session->get('cls_gender') == 'Female') echo 'checked '; ?>/> <label for="gender_female"><?php echo JText::_('CLS_FEMALE') ?></label></td>
        </tr>
        <?php endif; ?>
        <?php if($config->get('show_gbv')): ?>
        <tr>
            <td><?php echo JText::_('CLS_GBV_FIELD_NAME'); ?>:</td>
            <td><input type="radio" onclick="toggleGBV()" name="gbv" id="gbv_yes" value="1" <?php if($this->session->get('cls_gbv') == '1') echo 'checked '; ?>/> <label for="gbv_yes"><?php echo JText::_('JYES') ?></label> <input type="radio" onclick="toggleGBV()" name="gbv" id="gbv_no" value="0" <?php if($this->session->get('cls_gbv') != '1') echo 'checked '; ?>/> <label for="gbv_no"><?php echo JText::_('JNO') ?></label></td>
        </tr>
        <tr id="gbv_type_field" style="<?php if($this->session->get('cls_gbv') != '1') echo 'display:none;'; ?>">
            <td><?php echo JText::_('CLS_GBV_FIELD_TYPE'); ?>:</td>
            <td>
                <select name="gbv_type">
                    <option value="" <?php if($this->session->get('cls_gbv_type') == '') echo 'selected '; ?>> - <?php echo JText::_('CLS_SELECT_GBV_TYPE'); ?> - </option>
                    <option value="rape" <?php if($this->session->get('cls_gbv_type') == 'rape') echo 'selected '; ?>><?php echo JText::_('CLS_RAPE'); ?></option>
                    <option value="sexual_assault" <?php if($this->session->get('cls_gbv_type') == 'sexual_assault') echo 'selected '; ?>><?php echo JText::_('CLS_SEXUAL_ASSAULT'); ?></option>
                    <option value="physical_assault" <?php if($this->session->get('cls_gbv_type') == 'physical_assault') echo 'selected '; ?>><?php echo JText::_('CLS_PHYSICAL_ASSAULT'); ?></option>
                    <option value="forced_marriage" <?php if($this->session->get('cls_gbv_type') == 'forced_marriage') echo 'selected '; ?>><?php echo JText::_('CLS_FORCED_MARRIAGE'); ?></option>
                    <option value="denial_of_resources" <?php if($this->session->get('cls_gbv_type') == 'denial_of_resources') echo 'selected '; ?>><?php echo JText::_('CLS_DENIAL_OF_RESOURCES'); ?></option>
                    <option value="psychological_emotional_abuse" <?php if($this->session->get('cls_gbv_type') == 'psychological_emotional_abuse') echo 'selected '; ?>><?php echo JText::_('CLS_PSYCHOLOGICAL_EMOTIONAL_ABUSE'); ?></option>
                </select>
            </td>
        </tr>
        <tr id="gbv_related_field" style="<?php if($this->session->get('cls_gbv') != '1') echo 'display:none;'; ?>">
            <td><?php echo JText::_('CLS_GBV_FIELD_RELATION'); ?>:</td>
            <td>
                <input type="radio" name="gbv_relation" id="gbv_relation_yes" value="1" <?php if($this->session->get('cls_gbv_relation') == '1') echo 'checked '; ?>/> <label for="gbv_relation_yes"><?php echo JText::_('JYES') ?></label>
                <input type="radio" name="gbv_relation" id="gbv_relation_no" value="0" <?php if($this->session->get('cls_gbv_relation') == '0') echo 'checked '; ?>/> <label for="gbv_relation_no"><?php echo JText::_('JNO') ?></label>
                <input type="radio" name="gbv_relation" id="gbv_relation_unknown" value="unknown" <?php if($this->session->get('cls_gbv_relation', 'unknown') == 'unknown') echo 'checked '; ?>/> <label for="gbv_relation_unknown"><?php echo JText::_('CLS_UNKNOWN') ?></label>
            </td>
        </tr>
        <?php endif; ?>
        <?php if($config->get('show_location')): ?>
        <?php JHTML::_('behavior.modal'); ?>
        <tr>
            <td><?php echo JText::_('CLS_LOCATION') ?>:</td>
            <td><input type="hidden" name="location" id="location" value="" /><a href="<?php echo JURI::base(); ?>index.php?option=com_cls&view=editlocation" class="modal" rel="{handler:'iframe',size:{x:screen.availWidth-250, y:screen.availHeight-250}}"><?php echo JText::_('CLS_ADD_LOCATION'); ?></a></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td><?php echo JText::_('CLS_MESSAGE') ?>:</td>
            <td><textarea name="msg" cols="50" rows="8"><?php echo $this->session->get('cls_msg'); ?></textarea></td>
        </tr>
        <?php if($config->get('show_upload')): ?>
        <tr>
            <td><?php echo JText::_('CLS_PICTURES') ?>:</td>
            <td>
                <input type="file" name="pictures[]" /><br />
                <input type="file" name="pictures[]" /><br />
                <input type="file" name="pictures[]" />
            </td>
        </tr>
        <?php endif; ?>
        <tr>
            <td><?php echo JText::_('CLS_PREFERRED_CONTACT_QUESTION') ?>:</td>
            <td>
                <select name="preferred_contact">
                    <option value="">Select contact method</option>
                    <option value="Email">Email</option>
                    <option value="SMS">SMS</option>
                    <option value="Telephone Call">Telephone Call</option>
                </select>
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

<div style="margin:15px 0;"><img src="<?php echo JURI::base(true); ?>/components/com_cls/ACP-EU_NDRR-Logo-EN.png" width="100%" height="100%" border="0" alt="The GCLS was developed with the support of ACP-EU" title="The GCLS was developed with the support of ACP-EU" /></div>