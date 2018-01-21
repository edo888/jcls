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

// months list
$months[] = array('key' => date('Y-m', strtotime('-1 month', time())), 'value' => date('M', strtotime('-1 month', time())));
$months[] = array('key' => date('Y-m', strtotime('-2 month', time())), 'value' => date('M', strtotime('-2 month', time())));
$months[] = array('key' => date('Y-m', strtotime('-3 month', time())), 'value' => date('M', strtotime('-3 month', time())));
$months[] = array('key' => date('Y-m', strtotime('-4 month', time())), 'value' => date('M', strtotime('-4 month', time())));
$months[] = array('key' => date('Y-m', strtotime('-5 month', time())), 'value' => date('M', strtotime('-5 month', time())));
$months[] = array('key' => date('Y-m', strtotime('-6 month', time())), 'value' => date('M', strtotime('-6 month', time())));
$lists['months'] = JHTML::_('select.genericlist', $months, 'report_month', null, 'key', 'value', date('Y-m', strtotime('-1 month', time())));

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
<h2><?php echo JText::_('CLS_OHS_SUPERVISION_REPORTING_FORM_HEAD') ?></h2>
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
.camps_extra,
.children_extra,
#approved_and_disclosed_extra,
#serious_ohs_issues_reported_extra,
#serious_ohs_issues_reported_extra1,
#trained_first_aid_officer_available_extra,
#first_aid_kits_available_extra,
#transport_for_injured_personnel_available_extra,
#emergency_transport_directions_available_extra,
#plan_updated_extra,
.plan_updated_extra,
#plan_updated_extra2,
#plan_reviewed_submitted_approved_extra,
.plan_reviewed_submitted_approved_extra,
#facilities_in_compliance_with_local_laws_and_esmp_extra,
#appropriate_living_and_recreational_space_for_workers_extra,
#proper_sanitation_facility_extra {display:none;}
</style>

<form name="report_form" action="index.php" method="post" enctype="multipart/form-data" onsubmit="return validate();">
    <table>
        <tr>
            <td><?php echo JText::_('CLS_OHS_SELECT_MONTH') ?>:</td>
            <td><?php echo $lists['months']; ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_SELECT_CONTRACT') ?>:</td>
            <td><?php echo $lists['contract']; ?></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_PLAN_ADHERENCE'); ?></th></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_SERIOUS_OHS_ISSUES_DURING_THE_MONTH') ?>:</td>
            <td><input type="text" size="3" maxlength="5" onchange="toggleOptions()" name="number_of_serious_ohs_issues_during_the_month" id="number_of_serious_ohs_issues_during_the_month" value="<?php echo $this->session->get('cls_number_of_serious_ohs_issues_during_the_month', 0); ?>" /></td>
        </tr>
        <tr id="serious_ohs_issues_reported_extra1">
            <td><?php echo JText::_('CLS_OHS_SERIOUS_OHS_ISSUES_REPORTED') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="serious_ohs_issues_reported" id="serious_ohs_issues_reported" value="1" <?php if($this->session->get('cls_serious_ohs_issues_reported') == '1') echo 'checked '; ?>/> <label for="serious_ohs_issues_reported"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="serious_ohs_issues_reported" id="serious_ohs_issues_not_reported" value="0" <?php if($this->session->get('cls_serious_ohs_issues_reported') == '0') echo 'checked '; ?>/> <label for="serious_ohs_issues_not_reported"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr id="serious_ohs_issues_reported_extra">
            <td colspan="2"><textarea name="serious_ohs_issues_reported_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_serious_ohs_issues_reported_comment'); ?></textarea></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_SAFETY_OFFICER_ACTIVITIES'); ?></th></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_DAYS_WORKED') ?>:</td>
            <td><input type="text" name="number_of_days_worked" size="3" maxlength="5" value="<?php echo $this->session->get('cls_number_of_days_worked'); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_FULL_INSPECTIONS') ?>:</td>
            <td><input type="text" name="number_of_full_inspections" size="3" maxlength="5" value="<?php echo $this->session->get('cls_number_of_full_inspections'); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_PARTIAL_INSPECTIONS') ?>:</td>
            <td><input type="text" name="number_of_partial_inspections" size="3" maxlength="5" value="<?php echo $this->session->get('cls_number_of_partial_inspections'); ?>" /></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_MONTHTLY_SAFETY_OFFICER_REPORT') ?>:</td>
            <td>
                <input type="file" name="monthtly_safety_officer_report" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_OTHER_SAFETY_RELATED_DOCUMENTS') ?>:</td>
            <td>
                <input type="file" name="other_safety_related_documents" />
            </td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_TRAINED_FIRST_AID_OFFICER_AVAILABLE') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="trained_first_aid_officer_available" id="trained_first_aid_officer_available" value="1" <?php if($this->session->get('cls_trained_first_aid_officer_available') == '1') echo 'checked '; ?>/> <label for="trained_first_aid_officer_available"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="trained_first_aid_officer_available" id="trained_first_aid_officer_not_available" value="0" <?php if($this->session->get('cls_trained_first_aid_officer_available') == '0') echo 'checked '; ?>/> <label for="trained_first_aid_officer_not_available"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr id="trained_first_aid_officer_available_extra">
            <td colspan="2"><textarea name="trained_first_aid_officer_available_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_trained_first_aid_officer_available_comment'); ?></textarea></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_FIRST_AID_KITS_AVAILABLE') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="first_aid_kits_available" id="first_aid_kits_available" value="1" <?php if($this->session->get('cls_first_aid_kits_available') == '1') echo 'checked '; ?>/> <label for="first_aid_kits_available"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="first_aid_kits_available" id="first_aid_kits_not_available" value="0" <?php if($this->session->get('cls_first_aid_kits_available') == '0') echo 'checked '; ?>/> <label for="first_aid_kits_not_available"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr id="first_aid_kits_available_extra">
            <td colspan="2"><textarea name="first_aid_kits_available_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_first_aid_kits_available_comment'); ?></textarea></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_TRANSPORT_FOR_INJURED_PERSONNEL_AVAILABLE') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="transport_for_injured_personnel_available" id="transport_for_injured_personnel_available" value="1" <?php if($this->session->get('cls_transport_for_injured_personnel_available') == '1') echo 'checked '; ?>/> <label for="transport_for_injured_personnel_available"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="transport_for_injured_personnel_available" id="transport_for_injured_personnel_not_available" value="0" <?php if($this->session->get('cls_transport_for_injured_personnel_available') == '0') echo 'checked '; ?>/> <label for="transport_for_injured_personnel_not_available"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr id="transport_for_injured_personnel_available_extra">
            <td colspan="2"><textarea name="transport_for_injured_personnel_available_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_transport_for_injured_personnel_available_available_comment'); ?></textarea></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_EMERGENCY_TRANSPORT_DIRECTIONS_AVAILABLE') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="emergency_transport_directions_available" id="emergency_transport_directions_available" value="1" <?php if($this->session->get('cls_emergency_transport_directions_available') == '1') echo 'checked '; ?>/> <label for="emergency_transport_directions_available"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="emergency_transport_directions_available" id="emergency_transport_directions_not_available" value="0" <?php if($this->session->get('cls_emergency_transport_directions_available') == '0') echo 'checked '; ?>/> <label for="emergency_transport_directions_not_available"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr id="emergency_transport_directions_available_extra">
            <td colspan="2"><textarea name="emergency_transport_directions_available_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_emergency_transport_directions_available_comment'); ?></textarea></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_UPDATED_OHS_PLAN'); ?></th></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_PLAN_UPDATED') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="plan_updated" id="plan_updated" value="1" <?php if($this->session->get('cls_plan_updated') == '1') echo 'checked '; ?>/> <label for="plan_updated"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="plan_updated" id="plan_not_updated" value="0" <?php if($this->session->get('cls_plan_updated') == '0') echo 'checked '; ?>/> <label for="plan_not_updated"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr id="plan_updated_extra">
            <td colspan="2"><textarea name="plan_updated_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_plan_updated_comment'); ?></textarea></td>
        </tr>
        <tr id="plan_updated_extra2">
            <td><?php echo JText::_('CLS_OHS_PLAN_REVIEWED_SUBMITTED_APPROVED') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="plan_reviewed_submitted_approved" id="plan_reviewed_submitted_approved" value="1" <?php if($this->session->get('cls_plan_reviewed_submitted_approved') == '1') echo 'checked '; ?>/> <label for="plan_reviewed_submitted_approved"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="plan_reviewed_submitted_approved" id="plan_not_reviewed_submitted_approved" value="0" <?php if($this->session->get('cls_plan_reviewed_submitted_approved') == '0') echo 'checked '; ?>/> <label for="plan_not_reviewed_submitted_approved"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr class="plan_reviewed_submitted_approved_extra" id="plan_reviewed_submitted_approved_extra">
            <td colspan="2"><textarea name="plan_reviewed_submitted_approved_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_plan_reviewed_submitted_approved_comment'); ?></textarea></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_WORKERS'); ?></th></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS') ?>:</td>
            <td>
                <?php echo JText::_('CLS_MALE'); ?>: <input type="text" size="3" maxlength="5" name="number_of_workers_male" value="<?php echo $this->session->get('cls_number_of_workers_male'); ?>" />
                <?php echo JText::_('CLS_FEMALE'); ?>: <input type="text" size="3" maxlength="5" name="number_of_workers_female" value="<?php echo $this->session->get('cls_number_of_workers_female'); ?>" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_TOTAL_HOURS_WORKED_DURING_MONTH') ?>:</td>
            <td>
                <?php echo JText::_('CLS_MALE'); ?>: <input type="text" size="3" maxlength="5" name="total_hours_worked_during_month_male" value="<?php echo $this->session->get('cls_total_hours_worked_during_month_male'); ?>" />
                <?php echo JText::_('CLS_FEMALE'); ?>: <input type="text" size="3" maxlength="5" name="total_hours_worked_during_month_female" value="<?php echo $this->session->get('cls_total_hours_worked_during_month_female'); ?>" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_PERCENTAGE_OF_WORKERS_WITH_FULL_PPE') ?>:</td>
            <td>
                <?php echo JText::_('CLS_MALE'); ?>: <input type="text" size="3" maxlength="3" name="percentage_of_workers_with_full_ppe_male" value="<?php echo $this->session->get('cls_percentage_of_workers_with_full_ppe_male'); ?>" /> %
                <?php echo JText::_('CLS_FEMALE'); ?>: <input type="text" size="3" maxlength="3" name="percentage_of_workers_with_full_ppe_female" value="<?php echo $this->session->get('cls_percentage_of_workers_with_full_ppe_female'); ?>" /> %
            </td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_NUMBER_OF_VIOLATIONS_AND_WARNINGS_GIVEN'); ?></th></tr>
        <tr>
            <td colspan="2">
                <table>
                    <tr><th colspan="2">&nbsp;</th><th colspan="2">Violations</th><th colspan="2">Warnings</th><th colspan="2">Repeat Warnings</th></tr>
                    <tr><th colspan="2">Type</th><td><?php echo JText::_('CLS_MALE'); ?></td><td><?php echo JText::_('CLS_FEMALE'); ?></td><td><?php echo JText::_('CLS_MALE'); ?></td><td><?php echo JText::_('CLS_FEMALE'); ?></td><td><?php echo JText::_('CLS_MALE'); ?></td><td><?php echo JText::_('CLS_FEMALE'); ?></td></tr>
                    <tr>
                        <td>PPE</td>
                        <td>&nbsp</td>
                        <td><input type="text" size="1" maxlength="5" name="violations_ppe_male" value="<?php echo $this->session->get('cls_violations_ppe_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="violations_ppe_female" value="<?php echo $this->session->get('cls_violations_ppe_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_ppe_male" value="<?php echo $this->session->get('cls_warnings_ppe_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_ppe_female" value="<?php echo $this->session->get('cls_warnings_ppe_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_ppe_male" value="<?php echo $this->session->get('cls_repeat_warnings_ppe_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_ppe_female" value="<?php echo $this->session->get('cls_repeat_warnings_ppe_female', 0); ?>" /></td>
                    </tr>
                    <tr>
                        <td>Driving</td>
                        <td>&nbsp</td>
                        <td><input type="text" size="1" maxlength="5" name="violations_driving_male" value="<?php echo $this->session->get('cls_violations_driving_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="violations_driving_female" value="<?php echo $this->session->get('cls_violations_driving_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_driving_male" value="<?php echo $this->session->get('cls_warnings_driving_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_driving_female" value="<?php echo $this->session->get('cls_warnings_driving_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_driving_male" value="<?php echo $this->session->get('cls_repeat_warnings_driving_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_driving_female" value="<?php echo $this->session->get('cls_repeat_warnings_driving_female', 0); ?>" /></td>
                    </tr>
                    <tr>
                        <td>Traffic management</td>
                        <td>&nbsp</td>
                        <td><input type="text" size="1" maxlength="5" name="violations_traffic_management_male" value="<?php echo $this->session->get('cls_violations_traffic_management_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="violations_traffic_management_female" value="<?php echo $this->session->get('cls_violations_traffic_management_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_traffic_management_male" value="<?php echo $this->session->get('cls_warnings_traffic_management_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_traffic_management_female" value="<?php echo $this->session->get('cls_warnings_traffic_management_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_traffic_management_male" value="<?php echo $this->session->get('cls_repeat_warnings_traffic_management_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_traffic_management_female" value="<?php echo $this->session->get('cls_repeat_warnings_traffic_management_female', 0); ?>" /></td>
                    </tr>
                    <tr>
                        <td>Work practice</td>
                        <td>&nbsp</td>
                        <td><input type="text" size="1" maxlength="5" name="violations_work_practice_male" value="<?php echo $this->session->get('cls_violations_work_practice_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="violations_work_practice_female" value="<?php echo $this->session->get('cls_violations_work_practice_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_work_practice_male" value="<?php echo $this->session->get('cls_warnings_work_practice_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_work_practice_female" value="<?php echo $this->session->get('cls_warnings_work_practice_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_work_practice_male" value="<?php echo $this->session->get('cls_repeat_warnings_work_practice_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_work_practice_female" value="<?php echo $this->session->get('cls_repeat_warnings_work_practice_female', 0); ?>" /></td>
                    </tr>
                    <tr>
                        <td>Others</td>
                        <td>&nbsp</td>
                        <td><input type="text" size="1" maxlength="5" name="violations_others_male" value="<?php echo $this->session->get('cls_violations_others_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="violations_others_female" value="<?php echo $this->session->get('cls_violations_others_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_others_male" value="<?php echo $this->session->get('cls_warnings_others_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="warnings_others_female" value="<?php echo $this->session->get('cls_warnings_others_female', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_others_male" value="<?php echo $this->session->get('cls_repeat_warnings_others_male', 0); ?>" /></td>
                        <td><input type="text" size="1" maxlength="5" name="repeat_warnings_others_female" value="<?php echo $this->session->get('cls_repeat_warnings_others_female', 0); ?>" /></td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_CONFIRMATION_THAT_NO_CHILDREN_ARE_WORKING_ON_THE_PROJECT') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="no_children_are_working_on_the_project" id="no_children_are_working_on_the_project" value="1" <?php if($this->session->get('cls_no_children_are_working_on_the_project') == '1') echo 'checked '; ?>/> <label for="no_children_are_working_on_the_project"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="no_children_are_working_on_the_project" id="children_are_working_on_the_project" value="0" <?php if($this->session->get('cls_no_children_are_working_on_the_project') == '0') echo 'checked '; ?>/> <label for="children_are_working_on_the_project"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr class="children_extra">
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_CHILDREN_FOR_THE_MONTH') ?>:</td>
            <td><input type="text" size="3" maxlength="5" name="number_of_children_for_the_month" value="<?php echo $this->session->get('cls_number_of_children_for_the_month', 0); ?>" /></td>
        </tr>
        <tr class="children_extra">
            <td colspan="2"><textarea name="children_are_working_on_the_project_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_children_are_working_on_the_project_comment'); ?></textarea></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_WORKERS_CAMPS'); ?></th></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_ARE_WORKERS_LIVING_IN_CAMPS') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="workers_living_in_camps" id="workers_living_in_camps" value="1" <?php if($this->session->get('cls_workers_living_in_camps') == '1') echo 'checked '; ?>/> <label for="workers_living_in_camps"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="workers_living_in_camps" id="workers_not_living_in_camps" value="0" <?php if($this->session->get('cls_workers_living_in_camps') == '0') echo 'checked '; ?>/> <label for="workers_not_living_in_camps"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr class="camps_extra">
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS') ?>:</td>
            <td>
                <?php echo JText::_('CLS_OHS_EXPATRIATES'); ?>: <input type="text" size="3" maxlength="5" name="number_of_expatriates_workers_in_camps" value="<?php echo $this->session->get('cls_number_of_expatriates_workers_in_camps'); ?>" />
                <?php echo JText::_('CLS_OHS_LOCAL'); ?>: <input type="text" size="3" maxlength="5" name="number_of_local_workers_in_camps" value="<?php echo $this->session->get('cls_number_of_local_workers_in_camps'); ?>" />
            </td>
        </tr>
        <tr class="camps_extra">
            <td><?php echo JText::_('CLS_OHS_DATE_OF_LAST_INSPECTION') ?>:</td>
            <td><?php echo JHTML::calendar($this->session->get('cls_date_of_last_inspection', date("Y-m-d", strtotime("-1 month", time()))), 'date_of_last_inspection', 'date', '%Y-%m-%d',array('size'=>'8','maxlength'=>'10','class'=>' validate[\'required\']',)); ?></td>
        </tr>
        <tr class="camps_extra">
            <td><?php echo JText::_('CLS_OHS_FACILITIES_IN_COMPLIANCE_WITH_LOCAL_LAWS_AND_ESMP') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="facilities_in_compliance_with_local_laws_and_esmp" id="facilities_in_compliance_with_local_laws_and_esmp" value="1" <?php if($this->session->get('cls_facilities_in_compliance_with_local_laws_and_esmp') == '1') echo 'checked '; ?>/> <label for="facilities_in_compliance_with_local_laws_and_esmp"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="facilities_in_compliance_with_local_laws_and_esmp" id="facilities_in_non_compliance_with_local_laws_and_esmp" value="0" <?php if($this->session->get('cls_facilities_in_compliance_with_local_laws_and_esmp') == '0') echo 'checked '; ?>/> <label for="facilities_in_non_compliance_with_local_laws_and_esmp"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr class="camps_extra" id="facilities_in_compliance_with_local_laws_and_esmp_extra">
            <td colspan="2"><textarea name="facilities_in_compliance_with_local_laws_and_esmp_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_facilities_in_compliance_with_local_laws_and_esmp_comment'); ?></textarea></td>
        </tr>
        <tr class="camps_extra">
            <td><?php echo JText::_('CLS_OHS_PROPER_SANITATION_FACILITY') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="proper_sanitation_facility" id="proper_sanitation_facility" value="1" <?php if($this->session->get('cls_proper_sanitation_facility') == '1') echo 'checked '; ?>/> <label for="proper_sanitation_facility"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="proper_sanitation_facility" id="no_proper_sanitation_facility" value="0" <?php if($this->session->get('cls_proper_sanitation_facility') == '0') echo 'checked '; ?>/> <label for="no_proper_sanitation_facility"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr class="camps_extra" id="proper_sanitation_facility_extra">
            <td colspan="2"><textarea name="proper_sanitation_facility_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_proper_sanitation_facility_comment'); ?></textarea></td>
        </tr>
        <tr class="camps_extra">
            <td><?php echo JText::_('CLS_OHS_APPROPRIATE_LIVING_AND_RECREATIONAL_SPACE_FOR_WORKERS') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="appropriate_living_and_recreational_space_for_workers" id="appropriate_living_and_recreational_space_for_workers" value="1" <?php if($this->session->get('cls_appropriate_living_and_recreational_space_for_workers') == '1') echo 'checked '; ?>/> <label for="appropriate_living_and_recreational_space_for_workers"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="appropriate_living_and_recreational_space_for_workers" id="non_appropriate_living_and_recreational_space_for_workers" value="0" <?php if($this->session->get('cls_appropriate_living_and_recreational_space_for_workers') == '0') echo 'checked '; ?>/> <label for="non_appropriate_living_and_recreational_space_for_workers"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr class="camps_extra" id="appropriate_living_and_recreational_space_for_workers_extra">
            <td colspan="2"><textarea name="appropriate_living_and_recreational_space_for_workers_comment" placeholder="<?php echo JText::_('CLS_OHS_EXPLAIN_WHY'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_appropriate_living_and_recreational_space_for_workers_comment'); ?></textarea></td>
        </tr>
        <tr class="camps_extra">
            <td colspan="2"><textarea name="recommendations_to_improve_living_conditions" placeholder="<?php echo JText::_('CLS_OHS_RECOMMENDATIONS_TO_IMPROVE_LIVING_CONDITIONS'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_recommendations_to_improve_living_conditions'); ?></textarea></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_CONDITION_OF_VEHICLES_EQUPMENT'); ?></th></tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_VEHICLES_OR_EQUIPMENT_UNSAFE_OR_IMPROPERLY_MAINTAINED') ?>:</td>
            <td><input type="text" size="3" maxlength="5" name="number_of_vehicles_or_equipment_unsafe_or_improperly_maintained" value="<?php echo $this->session->get('cls_number_of_vehicles_or_equipment_unsafe_or_improperly_maintained', 0); ?>" /></td>
        </tr>
        <tr>
            <td colspan="2"><textarea name="recommendations_to_improve_vehicles_equipment" placeholder="<?php echo JText::_('CLS_OHS_RECOMMENDATIONS_TO_IMPROVE_VEHICLES_EQUIPMENT'); ?>" cols="50" rows="8"><?php echo $this->session->get('cls_recommendations_to_improve_vehicles_equipment'); ?></textarea></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_RECOMMENDATIONS_AND_GUIDANCE_GIVEN_TO_CONTRACTOR'); ?></th></tr>
        <tr>
            <td colspan="2"><textarea name="recommendations_and_guidance_given_to_contractor" cols="50" rows="8"><?php echo $this->session->get('cls_recommendations_and_guidance_given_to_contractor'); ?></textarea></td>
        </tr>
        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_ACTIONS_TO_BE_FOLLOWED_UP_ON_NEXT_MONTH'); ?></th></tr>
        <tr>
            <td colspan="2"><textarea name="actions_to_be_followed_up_on_next_month" cols="50" rows="8"><?php echo $this->session->get('cls_actions_to_be_followed_up_on_next_month'); ?></textarea></td>
        </tr>
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

    if(document.getElementById('serious_ohs_issues_not_reported').checked) {
        document.getElementById('serious_ohs_issues_reported_extra').style.display="table-row";
    } else {
        document.getElementById('serious_ohs_issues_reported_extra').style.display="none";
    }

    if(document.getElementById('number_of_serious_ohs_issues_during_the_month').value != '0') {
        document.getElementById('serious_ohs_issues_reported_extra1').style.display="table-row";
    } else {
        document.getElementById('serious_ohs_issues_reported_extra1').style.display="none";
        document.getElementById('serious_ohs_issues_reported_extra').style.display="none";
    }

    if(document.getElementById('trained_first_aid_officer_not_available').checked) {
        document.getElementById('trained_first_aid_officer_available_extra').style.display="table-row";
    } else {
        document.getElementById('trained_first_aid_officer_available_extra').style.display="none";
    }

    if(document.getElementById('first_aid_kits_not_available').checked) {
        document.getElementById('first_aid_kits_available_extra').style.display="table-row";
    } else {
        document.getElementById('first_aid_kits_available_extra').style.display="none";
    }

    if(document.getElementById('transport_for_injured_personnel_not_available').checked) {
        document.getElementById('transport_for_injured_personnel_available_extra').style.display="table-row";
    } else {
        document.getElementById('transport_for_injured_personnel_available_extra').style.display="none";
    }

    if(document.getElementById('emergency_transport_directions_not_available').checked) {
        document.getElementById('emergency_transport_directions_available_extra').style.display="table-row";
    } else {
        document.getElementById('emergency_transport_directions_available_extra').style.display="none";
    }
/*
    #plan_updated_reviewed_extra,
.plan_updated_reviewed_extra,
#plan_updated_submitted_extra,
.plan_updated_submitted_extra,
#plan_updated_approved_extra */

    if(document.getElementById('plan_not_updated').checked) {
        document.getElementById('plan_updated_extra').style.display="table-row";
        document.getElementById('plan_updated_extra2').style.display="none";

        var fields = document.getElementsByClassName('plan_reviewed_submitted_approved_extra');
        for(var i = 0; i < fields.length; i++)
            fields[i].style.display="none";
    } else {
        document.getElementById('plan_updated_extra').style.display="none";
        document.getElementById('plan_updated_extra2').style.display="table-row";

        if(document.getElementById('plan_not_reviewed_submitted_approved').checked) {
            document.getElementById('plan_reviewed_submitted_approved_extra').style.display="table-row";
        } else {
            document.getElementById('plan_reviewed_submitted_approved_extra').style.display="none";
        }
    }

    if(document.getElementById('children_are_working_on_the_project').checked) {
        var fields = document.getElementsByClassName('children_extra');
            for(var i = 0; i < fields.length; i++)
                fields[i].style.display="table-row";
    } else {
        var fields = document.getElementsByClassName('children_extra');
            for(var i = 0; i < fields.length; i++)
                fields[i].style.display="none";
    }

    var fields = document.getElementsByClassName('camps_extra');
    if(document.getElementById('workers_living_in_camps').checked) {
        for(var i = 0; i < fields.length; i++)
            fields[i].style.display="table-row";

        if(document.getElementById('facilities_in_non_compliance_with_local_laws_and_esmp').checked) {
            document.getElementById('facilities_in_compliance_with_local_laws_and_esmp_extra').style.display="table-row";
        } else {
            document.getElementById('facilities_in_compliance_with_local_laws_and_esmp_extra').style.display="none";
        }

        if(document.getElementById('non_appropriate_living_and_recreational_space_for_workers').checked) {
            document.getElementById('appropriate_living_and_recreational_space_for_workers_extra').style.display="table-row";
        } else {
            document.getElementById('appropriate_living_and_recreational_space_for_workers_extra').style.display="none";
        }

        if(document.getElementById('no_proper_sanitation_facility').checked) {
            document.getElementById('proper_sanitation_facility_extra').style.display="table-row";
        } else {
            document.getElementById('proper_sanitation_facility_extra').style.display="none";
        }

    } else {
        for(var i = 0; i < fields.length; i++)
            fields[i].style.display="none";
    }

}

toggleOptions();
</script>