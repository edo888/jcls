<?php
/**
* @version   $Id$
* @package   GCLS
* @copyright Copyright (C) 2010-2017 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

$config =& JComponentHelper::getParams('com_cls');
$db = JFactory::getDBO();

JHTML::_('behavior.calendar');
JHTML::_('behavior.modal');

// contract_id list
$query = 'select * from #__complaint_contracts';
$db->setQuery($query);
$contracts = $db->loadObjectList();
$contract[] = array('key' => '', 'value' => '- Select Contract -');
foreach($contracts as $a)
    $contract[] = array('key' => $a->id, 'value' => $a->name);
$lists['contract'] = JHTML::_('select.genericlist', $contract, 'contract_id', null, 'key', 'value', null);

$doc = JFactory::getDocument();
$doc->addStyleSheet($this->baseurl.'/media/jui/css/icomoon.css');
?>
<h2><?php echo JText::_('CLS_OHS_INCIDENT_REPORTING_FORM_HEAD') ?></h2>
<script type="text/javascript">
function validate() {
    // message
    /*
    if(document.report_form.msg.value == '') {
        alert('You need to enter a complaint message.');
        return false;
    }
    */

    return true;
}

</script>

<style type="text/css">
.camps_extra, #children_extra, #approved_and_disclosed_extra {display:none;}
</style>

<form name="report_form" action="index.php" method="post" enctype="multipart/form-data" onsubmit="return validate();">
    <table>
        <tr>
            <td><?php echo JText::_('CLS_OHS_DATE_OF_INCIDENT') ?>:</td>
            <td><?php echo JHTML::_('calendar', $this->session->get('date_of_incident', ''), 'date_of_incident', 'date_of_incident', '%Y-%m-%d'); ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_SELECT_CONTRACT') ?>:</td>
            <td><?php echo $lists['contract']; ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_LOCATION_OF_INCIDENT') ?>:</td>
            <td><input type="hidden" name="location_of_incident" id="location" value="<?php echo $this->session->get('location_of_incident', ''); ?>" /><a href="<?php echo JURI::base(); ?>index.php?option=com_cls&view=editlocation" class="modal" rel="{handler:'iframe',size:{x:screen.availWidth-250, y:screen.availHeight-250}}"><?php echo JText::_('CLS_ADD_LOCATION'); ?></a></td>
        </tr>

        <tr>
            <td><?php echo JText::_('CLS_OHS_INJURY_TYPE') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="injury_type" id="injury_type_fatality" value="0" <?php if($this->session->get('cls_injury_type') == '0') echo 'checked '; ?>/> <label for="injury_type_fatality"><?php echo JText::_('CLS_OHS_INJURY_TYPE_FATALITY') ?></label><br />
                <input type="radio" onclick="toggleOptions()" name="injury_type" id="injury_type_notifiable_injury" value="1" <?php if($this->session->get('cls_injury_type') == '1') echo 'checked '; ?>/> <label for="injury_type_notifiable_injury"><?php echo JText::_('CLS_OHS_INJURY_TYPE_NOTIFIABLE_INJURY') ?></label><br />
                <input type="radio" onclick="toggleOptions()" name="injury_type" id="injury_type_lost_time_injury" value="2" <?php if($this->session->get('cls_injury_type') == '2') echo 'checked '; ?>/> <label for="injury_type_lost_time_injury"><?php echo JText::_('CLS_OHS_INJURY_TYPE_LOST_TIME_INJURY') ?></label><br />
                <input type="radio" onclick="toggleOptions()" name="injury_type" id="injury_type_medical_treatment" value="3" <?php if($this->session->get('cls_injury_type') == '3') echo 'checked '; ?>/> <label for="injury_type_medical_treatment"><?php echo JText::_('CLS_OHS_INJURY_TYPE_MEDICAL_TREATMENT') ?></label><br />
            </td>
        </tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_SUMMARY_OF_EVENTS'); ?></th></tr>
        <tr><td colspan="2"><textarea name="summary_of_events" cols="50" rows="8"><?php echo $this->session->get('cls_summary_of_events'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_PERSONS_INVOLVED_IN_THE_INCIDENT'); ?></th></tr>
        <tr><td colspan="2"><textarea name="persons_involved" cols="50" rows="8"><?php echo $this->session->get('cls_persons_involved'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_IMMEDIATE_CAUSE_OF_INCIDENT'); ?></th></tr>
        <tr><td colspan="2"><textarea name="immediate_cause_of_incident" cols="50" rows="8"><?php echo $this->session->get('cls_immediate_cause_of_incident'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_UNDERLYING_CAUSE_OF_INCIDENT'); ?></th></tr>
        <tr><td colspan="2"><textarea name="underlying_cause_of_incident" cols="50" rows="8"><?php echo $this->session->get('cls_underlying_cause_of_incident'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_ROOT_CAUSE_OF_INCIDENT'); ?></th></tr>
        <tr><td colspan="2"><textarea name="root_cause_of_incident" cols="50" rows="8"><?php echo $this->session->get('cls_root_cause_of_incident'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_IMMEDIATE_ACTION_TAKEN'); ?></th></tr>
        <tr><td colspan="2"><textarea name="immediate_action_taken" cols="50" rows="8"><?php echo $this->session->get('cls_immediate_action_taken'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_HUMAN_FACTORS'); ?></th></tr>
        <tr><td colspan="2"><textarea name="human_factors" cols="50" rows="8"><?php echo $this->session->get('cls_human_factors'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_OUTCOME_OF_INCIDENT'); ?></th></tr>
        <tr><td colspan="2"><textarea name="outcome_of_incident" cols="50" rows="8"><?php echo $this->session->get('cls_outcome_of_incident'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_CORRECTIVE_ACTIONS'); ?></th></tr>
        <tr><td colspan="2"><textarea name="corrective_actions" cols="50" rows="8"><?php echo $this->session->get('cls_corrective_actions'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_SUPPORT_PROVIDED_TO_THE_INJURED_PERSONS'); ?></th></tr>
        <tr><td colspan="2"><textarea name="support_provided" cols="50" rows="8"><?php echo $this->session->get('cls_support_provided'); ?></textarea></td></tr>

        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_RECOMMENDATIONS_FOR_FURTHER_IMPROVEMENT'); ?></th></tr>
        <tr><td colspan="2"><textarea name="recommendations_for_further_improvement" cols="50" rows="8"><?php echo $this->session->get('cls_recommendations_for_further_improvement'); ?></textarea></td></tr>

        <tr>
            <td>&nbsp;</td>
            <td><input type="submit" value="<?php echo JText::_('CLS_SUBMIT') ?>"></td>
        </tr>
    </table>
    <input type="hidden" name="option" value="com_cls" />
    <input type="hidden" name="task" value="submit_supervision_report" />
    <input type="hidden" name="Itemid" value="<?php echo JRequest::getInt('Itemid') ?>" />
</form>

<script type="text/javascript">
function toggleOptions() {
    //.camps_extra, #children_extra, #approved_and_disclosed_extra
    if(document.getElementById('not_approved_and_disclosed').checked) {
        document.getElementById('approved_and_disclosed_extra').style.display="table-row";
    } else {
        document.getElementById('approved_and_disclosed_extra').style.display="none";
    }

    if(document.getElementById('children_are_working_on_the_project').checked) {
        document.getElementById('children_extra').style.display="table-row";
    } else {
        document.getElementById('children_extra').style.display="none";
    }

    var fields = document.getElementsByClassName('camps_extra');
    if(document.getElementById('workers_living_in_camps').checked) {
        for(var i = 0; i < fields.length; i++)
            fields[i].style.display="table-row";
    } else {
        for(var i = 0; i < fields.length; i++)
            fields[i].style.display="none";
    }
}

toggleOptions();
</script>