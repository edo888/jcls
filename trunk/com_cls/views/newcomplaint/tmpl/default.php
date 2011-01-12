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
<h3>New Complaint</h3>

<script language="javascript" type="text/javascript">
function validate() {
    form = document.adminForm;
    if(form.message_source && form.message_source.value == "") {
        alert('Message Source is required');
        return false;
    } else if(form.name.value == "" && form.email.value == "" && form.phone.value == "" && form.address.value == "") {
        alert('Sender is required');
        return false;
    } else if(form.raw_message && form.raw_message.value == "") {
        alert('Raw message is required');
        return false;
    }

    return true;
}
</script>
<form action="<?php echo JURI::base(); ?>index.php?option=com_cls" method="post" name="adminForm" onsubmit="return validate();">

<table class="admintable">
    <tr>
        <td class="key" nowrap>
            <label for="path">
                <?php echo JText::_( 'Message Source' ); ?>
            </label>
        </td>
        <td>
            <?php echo $this->lists['source']; ?>
        </td>
    </tr>
    <tr>
        <td width="200" class="key">
            <label for="alias">
                <?php echo JText::_( 'Name' ); ?>
            </label>
        </td>
        <td>
            <input class="inputbox" type="text" name="name" id="name" size="30" value="" />
        </td>
    </tr>
    <tr>
        <td width="200" class="key">
            <label for="alias">
                <?php echo JText::_( 'Email' ); ?>
            </label>
        </td>
        <td>
            <input class="inputbox" type="text" name="email" id="email" size="30" value="" />
        </td>
    </tr>
    <tr>
        <td width="200" class="key">
            <label for="alias">
                <?php echo JText::_( 'Phone' ); ?>
            </label>
        </td>
        <td>
            <input class="inputbox" type="text" name="phone" id="phone" size="30" value="" />
        </td>
    </tr>
    <tr>
        <td width="200" class="key">
            <label for="alias">
                <?php echo JText::_( 'Address' ); ?>
            </label>
        </td>
        <td>
            <input class="inputbox" type="text" name="address" id="address" size="50" value="" />
        </td>
    </tr>
    <tr>
        <td class="key" valign="top">
            <label for="path">
                <?php echo JText::_( 'Raw Message' ); ?>
            </label>
        </td>
        <td>
            <textarea name="raw_message" id="raw_message" cols="60" rows="5"></textarea>
        </td>
    </tr>

    <tr>
        <td>&nbsp;</td>
        <td><input type="submit" value="<?php echo JText::_('CLS_SUBMIT') ?>"></td>
    </tr>
</table>

<input type="hidden" name="task" value="newcomplaint" />
<input type="hidden" name="option" value="com_cls" />
</form>