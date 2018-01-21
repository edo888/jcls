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
<h2><?php echo JText::_('CLS_OHS_CONTRACTOR_REPORTING_FORM_HEAD') ?></h2>
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
.ohs_plan_extra {display:none;}
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
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_HOURS_WORKED_THIS_MONTH') ?>:</td>
            <td>
                <?php echo JText::_('CLS_MALE'); ?>: <input type="text" onchange="recalculateRates()" name="number_of_hours_worked_this_month_male" size="3" maxlength="5" value="<?php echo $this->session->get('cls_number_of_hours_worked_this_month_male', 0); ?>" />
                <?php echo JText::_('CLS_FEMALE'); ?>: <input type="text" onchange="recalculateRates()" name="number_of_hours_worked_this_month_female" size="3" maxlength="5" value="<?php echo $this->session->get('cls_number_of_hours_worked_this_month_female', 0); ?>" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS') ?>:</td>
            <td>
                <?php echo JText::_('CLS_MALE'); ?>: <input type="text" onchange="recalculateRates()" size="3" maxlength="5" name="number_of_workers_male" value="<?php echo $this->session->get('cls_number_of_workers_male', 0); ?>" />
                <?php echo JText::_('CLS_FEMALE'); ?>: <input type="text" onchange="recalculateRates()" size="3" maxlength="5" name="number_of_workers_female" value="<?php echo $this->session->get('cls_number_of_workers_female', 0); ?>" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_ANY_UPDATE_TO_THE_OHS_SAFETY_PLAN') ?>:</td>
            <td>
                <input type="radio" onclick="toggleOptions()" name="update_to_the_ohs_safety_plan" id="update_to_the_ohs_safety_plan" value="1" <?php if($this->session->get('cls_update_to_the_ohs_safety_plan') == '1') echo 'checked '; ?>/> <label for="update_to_the_ohs_safety_plan"><?php echo JText::_('JYES') ?></label>
                <input type="radio" onclick="toggleOptions()" name="update_to_the_ohs_safety_plan" id="no_update_to_the_ohs_safety_plan" value="0" <?php if($this->session->get('cls_update_to_the_ohs_safety_plan') == '0') echo 'checked '; ?>/> <label for="no_update_to_the_ohs_safety_plan"><?php echo JText::_('JNO') ?></label>
            </td>
        </tr>
        <tr class="ohs_plan_extra">
            <td><?php echo JText::_('CLS_OHS_ATTACH_OHSMP_UPDATES_OR_CHANGES') ?>:</td>
            <td>
                <input type="file" name="ohsmp_updates_or_changes" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_ATTACH_THE_ESSS_MONTHLY_REPORT') ?>:</td>
            <td>
                <input type="file" name="esss_monthly_report" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_ATTACH_THE_SAFETY_OFFICERS_MONTHLY_REPORT') ?>:</td>
            <td>
                <input type="file" name="safety_officers_monthly_report" />
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_ATTACH_THE_OTHER_SAFETY_DOCUMENTS') ?>:</td>
            <td>
                <input type="file" name="other_safety_related_documents" />
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_LEADING_INDICATORS'); ?></th></tr>
        <tr>
            <td colspan="2">
                <table cellspacing="0" cellpadding="0">
                    <tr><td style="border-right:3px solid black;">&nbsp;</td><td style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;"><?php echo JText::_('CLS_OHS_NUMBER'); ?></td><td style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;"><?php echo JText::_('CLS_OHS_RATE'); ?></td><td style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;"><?php echo JText::_('CLS_OHS_POSITIVE'); ?></td><td style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;border-right:3px solid black;"><?php echo JText::_('CLS_OHS_PERCENT_POSITIVE'); ?></td></tr>

                    <tr><td style="border-right:3px solid black;"><b><?php echo JText::_('CLS_OHS_TRAINING'); ?></b></td><td></td><td></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS_TRAINED'); ?>             </td><td><input type="text" onchange="recalculateRates()" name="number_of_workers_trained" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_workers_trained', 0); ?>" /></td><td id="rate_number_of_workers_trained"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_COMPETENCY_ASSESSMENTS'); ?>                </td><td><input type="text" onchange="recalculateRates()" name="number_of_competency_assessments" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_competency_assessments', 0); ?>" /></td><td id="rate_number_of_competency_assessments"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_NEW_SKILL_TRAINING_SESSIONS'); ?>           </td><td><input type="text" onchange="recalculateRates()" name="number_of_new_skill_training_sessions" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_new_skill_training_sessions', 0); ?>" /></td><td id="rate_number_of_new_skill_training_sessions"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_OHS_TRAINING_WORKER_HOURS'); ?>             </td><td><input type="text" onchange="recalculateRates()" name="number_of_ohs_training" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_ohs_training', 0); ?>" /></td><td id="rate_number_of_ohs_training"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_HIV_AIDS_TRAINING_WORKER_HOURS'); ?>        </td><td><input type="text" onchange="recalculateRates()" name="number_of_hiv_aids_training" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_hiv_aids_training', 0); ?>" /></td><td id="rate_number_of_hiv_aids_training"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_GBV_VAC_TRAINING_WORKER_HOURS'); ?>         </td><td><input type="text" onchange="recalculateRates()" name="number_of_gbv_vac_training" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_gbv_vac_training', 0); ?>" /></td><td id="rate_number_of_gbv_vac_training"></td><td></td><td style="border-right:3px solid black;"></td></tr>

                    <tr><td style="border-right:3px solid black;"><b><?php echo JText::_('CLS_OHS_HEALTH_CHECKS'); ?></b></td><td></td><td></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_SITE_HEALTH_AND_SAFETY_AUDITS'); ?>         </td><td><input type="text" onchange="recalculateRates()" name="checks_site_health_and_safety_audits" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_site_health_and_safety_audits', 0); ?>" /></td><td id="rate_checks_site_health_and_safety_audits"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_SAFETY_BRIEFINGS'); ?>                      </td><td><input type="text" onchange="recalculateRates()" name="checks_safety_briefings" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_safety_briefings', 0); ?>" /></td><td id="rate_checks_safety_briefings"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_DRUG_CHECKS'); ?>                           </td><td><input type="text" onchange="recalculateRates()" name="checks_drugs" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_drugs', 0); ?>" /></td><td id="rate_checks_drugs"></td><td><input type="text" onchange="recalculateRates()" name="checks_drugs_positive" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_drugs_positive', 0); ?>" /></td><td id="percent_checks_drugs_positive" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_ALCOHOL_CHECKS'); ?>                        </td><td><input type="text" onchange="recalculateRates()" name="checks_alcohol" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_alcohol', 0); ?>" /></td><td id="rate_checks_alcohol"></td><td><input type="text" onchange="recalculateRates()" name="checks_alcohol_positive" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_alcohol_positive', 0); ?>" /></td><td id="percent_checks_alcohol_positive" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_HIV_CHECKS'); ?>                            </td><td><input type="text" onchange="recalculateRates()" name="checks_hiv" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_hiv', 0); ?>" /></td><td id="rate_checks_hiv"></td><td><input type="text" onchange="recalculateRates()" name="checks_hiv_positive" size="1" maxlength="5" value="<?php echo $this->session->get('cls_checks_hiv_positive', 0); ?>" /></td><td id="percent_checks_hiv_positive" style="border-right:3px solid black;"></td></tr>

                    <tr><td style="border-right:3px solid black;"><b><?php echo JText::_('CLS_OHS_RISK_IDENTIFICATION'); ?></b></td><td></td><td></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_NEAR_MISSES'); ?>                           </td><td><input type="text" onchange="recalculateRates()" name="number_of_near_misses" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_near_misses', 0); ?>" /></td><td id="rate_number_of_near_misses"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_STOP_WORK_ACTIONS'); ?>                     </td><td><input type="text" onchange="recalculateRates()" name="number_of_stop_work_actions" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_stop_work_actions', 0); ?>" /></td><td id="rate_number_of_stop_work_actions"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_TRAFFIC_MANAGEMENT_INSPECTIONS'); ?>        </td><td><input type="text" onchange="recalculateRates()" name="number_of_traffic_management_inspections" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_traffic_management_inspections', 0); ?>" /></td><td id="rate_number_of_traffic_management_inspections"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_COMPLETED_INVESTIGATIONS'); ?>              </td><td><input type="text" onchange="recalculateRates()" name="number_of_completed_investigations" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_completed_investigations', 0); ?>" /></td><td id="rate_number_of_completed_investigations"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_NEW_RISKS_IDENTIFIED'); ?>                  </td><td><input type="text" onchange="recalculateRates()" name="number_of_new_risks_identified" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_new_risks_identified', 0); ?>" /></td><td id="rate_number_of_new_risks_identified"></td><td></td><td style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_SUGGESTIONS_FOR_IMPROVEMENT_IDENTIFIED'); ?></td><td><input type="text" onchange="recalculateRates()" name="number_of_suggestions_for_improvement_identified" size="1" maxlength="5" value="<?php echo $this->session->get('cls_number_of_suggestions_for_improvement_identified', 0); ?>" /></td><td id="rate_number_of_suggestions_for_improvement_identified"></td><td></td><td style="border-right:3px solid black;"></td></tr>

                    <tr><th style="border-right:3px solid black;"><?php echo JText::_('CLS_OHS_TOTAL'); ?>                                 </th><td id="total_number" style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;"></td><td id="total_rate" style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;"></td><td style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;"></td><td style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;border-right:3px solid black;"></td></tr>
                  </table>
            </td>
        </tr>

        <tr><td colspan="2">&nbsp;</td></tr>
        <tr><th colspan="2"><?php echo JText::_('CLS_OHS_LAGGING_INDICATORS'); ?></th></tr>
        <tr>
            <td colspan="2">
                <table cellspacing="0" cellpadding="0">
                    <tr><td style="border:3px solid black;">&nbsp;</td><td style="padding:5px;border-top:3px solid black;border-bottom:3px solid black;"><?php echo JText::_('CLS_OHS_NUMBER'); ?></td><td style="padding:5px;border-top:3px solid black;border-right:3px solid black;border-bottom:3px solid black;"><?php echo JText::_('CLS_OHS_INJURY_RATE'); ?></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_FATAL_INJURIES'); ?>                                        </td><td><input type="text" onchange="recalculateRates()" name="fatal_injuries" size="1" maxlength="5" value="<?php echo $this->session->get('cls_fatal_injuries', 0); ?>" /></td><td id="rate_fatal_injuries" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_NOTIFIABLE_INJURIES_OR_INCIDENTS'); ?>                      </td><td><input type="text" onchange="recalculateRates()" name="notifiable_injuries_or_incidents" size="1" maxlength="5" value="<?php echo $this->session->get('cls_notifiable_injuries_or_incidents', 0); ?>" /></td><td id="rate_notifiable_injuries_or_incidents" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_LOST_TIME_INJURIES_OR_ILLNESSES'); ?>                       </td><td><input type="text" onchange="recalculateRates()" name="lost_time_injuries_or_illnesses" size="1" maxlength="5" value="<?php echo $this->session->get('cls_lost_time_injuries_or_illnesses', 0); ?>" /></td><td id="rate_lost_time_injuries_or_illnesses" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_MEDICALLY_TREATED_INJURIES_OR_ILLNESSES'); ?>               </td><td><input type="text" onchange="recalculateRates()" name="medically_treated_injuries_or_illnesses" size="1" maxlength="5" value="<?php echo $this->session->get('cls_medically_treated_injuries_or_illnesses', 0); ?>" /></td><td id="rate_medically_treated_injuries_or_illnesses" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_FIRST_AID_INJURIES'); ?>                                    </td><td><input type="text" onchange="recalculateRates()" name="first_aid_injuries" size="1" maxlength="5" value="<?php echo $this->session->get('cls_first_aid_injuries', 0); ?>" /></td><td id="rate_first_aid_injuries" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_INJURY_WITH_NO_TREATMENT'); ?>                              </td><td><input type="text" onchange="recalculateRates()" name="injury_with_no_treatment" size="1" maxlength="5" value="<?php echo $this->session->get('cls_injury_with_no_treatment', 0); ?>" /></td><td id="rate_injury_with_no_treatment" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_TRAFFIC_ACCIDENTS_INVOLVING_PROJECT_VEHICLES_EQUIPMENT'); ?></td><td><input type="text" onchange="recalculateRates()" name="traffic_accidents_involving_project_vehicles_equipment" size="1" maxlength="5" value="<?php echo $this->session->get('cls_traffic_accidents_involving_project_vehicles_equipment', 0); ?>" /></td><td id="rate_traffic_accidents_involving_project_vehicles_equipment" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_ACCIDENTS_INVOLVING_NON_PROJECT_VEHICLES_OR_PROPERTY'); ?>  </td><td><input type="text" onchange="recalculateRates()" name="accidents_involving_non_project_vehicles_or_property" size="1" maxlength="5" value="<?php echo $this->session->get('cls_accidents_involving_non_project_vehicles_or_property', 0); ?>" /></td><td id="rate_accidents_involving_non_project_vehicles_or_property" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_ENVIRONMENTAL_INCIDENT'); ?>                                </td><td><input type="text" onchange="recalculateRates()" name="environmental_incident" size="1" maxlength="5" value="<?php echo $this->session->get('cls_environmental_incident', 0); ?>" /></td><td id="rate_environmental_incident" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_ESCAPE_OF_A_SUBSTANCE_INTO_THE_ATMOSPHERE'); ?>             </td><td><input type="text" onchange="recalculateRates()" name="escape_of_a_substance_into_the_atmosphere" size="1" maxlength="5" value="<?php echo $this->session->get('cls_escape_of_a_substance_into_the_atmosphere', 0); ?>" /></td><td id="rate_escape_of_a_substance_into_the_atmosphere" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_UTILITY_OR_SERVICE_STRIKE'); ?>                             </td><td><input type="text" onchange="recalculateRates()" name="utility_or_service_strike" size="1" maxlength="5" value="<?php echo $this->session->get('cls_utility_or_service_strike', 0); ?>" /></td><td id="rate_utility_or_service_strike" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_DAMAGE_TO_PUBLIC_PROPERTY_OR_EQUIPMENT'); ?>                </td><td><input type="text" onchange="recalculateRates()" name="damage_to_public_property_or_equipment" size="1" maxlength="5" value="<?php echo $this->session->get('cls_damage_to_public_property_or_equipment', 0); ?>" /></td><td id="rate_damage_to_public_property_or_equipment" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_DAMAGE_TO_CONTRACTORS_EQUIPMENT'); ?>                       </td><td><input type="text" onchange="recalculateRates()" name="damage_to_contractors_equipment" size="1" maxlength="5" value="<?php echo $this->session->get('cls_damage_to_contractors_equipment', 0); ?>" /></td><td id="rate_damage_to_contractors_equipment" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_WORKER_LEAVING_SITE_DUE_TO_SAFETY_CONCERNS'); ?>            </td><td><input type="text" onchange="recalculateRates()" name="worker_leaving_site_due_to_safety_concerns" size="1" maxlength="5" value="<?php echo $this->session->get('cls_worker_leaving_site_due_to_safety_concerns', 0); ?>" /></td><td id="rate_worker_leaving_site_due_to_safety_concerns" style="border-right:3px solid black;"></td></tr>
                    <tr><td style="border-left:3px solid black;"><?php echo JText::_('CLS_OHS_STAFF_ON_REDUCED_ALTERNATE_DUTIES'); ?>                     </td><td><input type="text" onchange="recalculateRates()" name="staff_on_reduced_alternate_duties" size="1" maxlength="5" value="<?php echo $this->session->get('cls_staff_on_reduced_alternate_duties', 0); ?>" /></td><td id="rate_staff_on_reduced_alternate_duties" style="border-right:3px solid black;"></td></tr>
                    <tr><th style="border-left:3px solid black;border-top:3px solid black;"><?php echo JText::_('CLS_OHS_TOTAL_FATALITIES_AND_INJURIES'); ?>                         </th><td id="total_fatalities_and_injuries" style="padding:5px;border-top:3px solid black;"></td><td id="rate_total_fatalities_and_injuries" style="padding:5px;border-top:3px solid black;border-right:3px solid black;"></td></tr>
                    <tr><th style="border-left:3px solid black;border-bottom:3px solid black;"><?php echo JText::_('CLS_OHS_OVERALL_TOTAL'); ?>                                         </th><td id="total_overall" style="border-bottom:3px solid black;padding:5px;"></td><td id="rate_total_overall" style="border-right:3px solid black;border-bottom:3px solid black;"></td></tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><input type="submit" value="<?php echo JText::_('CLS_SUBMIT') ?>"></td>
        </tr>
    </table>
    <input type="hidden" name="option" value="com_cls" />
    <input type="hidden" name="task" value="submit_contractor_report" />
    <input type="hidden" name="Itemid" value="<?php echo JRequest::getInt('Itemid') ?>" />
</form>

<script type="text/javascript">
function recalculateRates() {
    var hours = +document.report_form.number_of_hours_worked_this_month_male.value + +document.report_form.number_of_hours_worked_this_month_female.value;
    var workers = +document.report_form.number_of_workers_male.value + +document.report_form.number_of_workers_female.value;

    // leading indicators
    document.getElementById('rate_checks_drugs').innerText = (+document.report_form.checks_drugs.value / hours * 40).toFixed(2);
    document.getElementById('rate_checks_alcohol').innerText = (+document.report_form.checks_alcohol.value / hours * 40).toFixed(2);
    document.getElementById('rate_checks_hiv').innerText = (+document.report_form.checks_hiv.value / hours * 40).toFixed(2);
    document.getElementById('rate_checks_site_health_and_safety_audits').innerText = (+document.report_form.checks_site_health_and_safety_audits.value / hours * 40).toFixed(2);
    document.getElementById('rate_checks_safety_briefings').innerText = (+document.report_form.checks_safety_briefings.value / hours * 40).toFixed(2);

    document.getElementById('rate_number_of_workers_trained').innerText = (+document.report_form.number_of_workers_trained.value / workers * 40).toFixed(2);

    document.getElementById('rate_number_of_ohs_training').innerText = (+document.report_form.number_of_ohs_training.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_hiv_aids_training').innerText = (+document.report_form.number_of_hiv_aids_training.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_gbv_vac_training').innerText = (+document.report_form.number_of_gbv_vac_training.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_near_misses').innerText = (+document.report_form.number_of_near_misses.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_stop_work_actions').innerText = (+document.report_form.number_of_stop_work_actions.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_traffic_management_inspections').innerText = (+document.report_form.number_of_traffic_management_inspections.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_new_skill_training_sessions').innerText = (+document.report_form.number_of_new_skill_training_sessions.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_competency_assessments').innerText = (+document.report_form.number_of_competency_assessments.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_completed_investigations').innerText = (+document.report_form.number_of_completed_investigations.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_new_risks_identified').innerText = (+document.report_form.number_of_new_risks_identified.value / hours * 40).toFixed(2);
    document.getElementById('rate_number_of_suggestions_for_improvement_identified').innerText = (+document.report_form.number_of_suggestions_for_improvement_identified.value / hours * 40).toFixed(2);

    document.getElementById('percent_checks_drugs_positive').innerText = Math.round(+document.report_form.checks_drugs_positive.value / +document.report_form.checks_drugs.value * 100);
    document.getElementById('percent_checks_alcohol_positive').innerText = Math.round(+document.report_form.checks_alcohol_positive.value / +document.report_form.checks_alcohol.value * 100);
    document.getElementById('percent_checks_hiv_positive').innerText = Math.round(+document.report_form.checks_hiv_positive.value / +document.report_form.checks_hiv.value * 100);

    document.getElementById('total_number').innerText = +document.report_form.checks_drugs.value + +document.report_form.checks_alcohol.value + +document.report_form.checks_hiv.value + +document.report_form.checks_site_health_and_safety_audits.value + +document.report_form.checks_safety_briefings.value + +document.report_form.number_of_workers_trained.value + +document.report_form.number_of_ohs_training.value + +document.report_form.number_of_hiv_aids_training.value + +document.report_form.number_of_gbv_vac_training.value + +document.report_form.number_of_near_misses.value + +document.report_form.number_of_stop_work_actions.value + +document.report_form.number_of_traffic_management_inspections.value + +document.report_form.number_of_new_skill_training_sessions.value + +document.report_form.number_of_competency_assessments.value + +document.report_form.number_of_completed_investigations.value + +document.report_form.number_of_new_risks_identified.value + +document.report_form.number_of_suggestions_for_improvement_identified.value + +document.report_form.checks_drugs_positive.value + +document.report_form.checks_alcohol_positive.value + +document.report_form.checks_hiv_positive.value;
    document.getElementById('total_rate').innerText = (+document.getElementById('total_number').innerText / hours * 40).toFixed(2);

    // lagging indicators
    document.getElementById('rate_fatal_injuries').innerText =                                              (+document.report_form.fatal_injuries.value / hours * 1000000).toFixed();
    document.getElementById('rate_notifiable_injuries_or_incidents').innerText =                            (+document.report_form.notifiable_injuries_or_incidents.value / hours * 1000000).toFixed();
    document.getElementById('rate_lost_time_injuries_or_illnesses').innerText =                             (+document.report_form.lost_time_injuries_or_illnesses.value / hours * 1000000).toFixed();
    document.getElementById('rate_medically_treated_injuries_or_illnesses').innerText =                     (+document.report_form.medically_treated_injuries_or_illnesses.value / hours * 1000000).toFixed();
    document.getElementById('rate_first_aid_injuries').innerText =                                          (+document.report_form.first_aid_injuries.value / hours * 1000000).toFixed();
    document.getElementById('rate_injury_with_no_treatment').innerText =                                    (+document.report_form.injury_with_no_treatment.value / hours * 1000000).toFixed();


    document.getElementById('rate_traffic_accidents_involving_project_vehicles_equipment').innerText = (+document.report_form.traffic_accidents_involving_project_vehicles_equipment.value / hours * 1000000).toFixed();
    document.getElementById('rate_accidents_involving_non_project_vehicles_or_property').innerText = (+document.report_form.accidents_involving_non_project_vehicles_or_property.value / hours * 1000000).toFixed();
    document.getElementById('rate_environmental_incident').innerText = (+document.report_form.environmental_incident.value / hours * 1000000).toFixed();
    document.getElementById('rate_escape_of_a_substance_into_the_atmosphere').innerText = (+document.report_form.escape_of_a_substance_into_the_atmosphere.value / hours * 1000000).toFixed();
    document.getElementById('rate_utility_or_service_strike').innerText = (+document.report_form.utility_or_service_strike.value / hours * 1000000).toFixed();
    document.getElementById('rate_damage_to_public_property_or_equipment').innerText = (+document.report_form.damage_to_public_property_or_equipment.value / hours * 1000000).toFixed();
    document.getElementById('rate_damage_to_contractors_equipment').innerText = (+document.report_form.damage_to_contractors_equipment.value / hours * 1000000).toFixed();
    document.getElementById('rate_worker_leaving_site_due_to_safety_concerns').innerText = (+document.report_form.worker_leaving_site_due_to_safety_concerns.value / hours * 1000000).toFixed();
    document.getElementById('rate_staff_on_reduced_alternate_duties').innerText = (+document.report_form.staff_on_reduced_alternate_duties.value / hours * 1000000).toFixed();

    document.getElementById('total_fatalities_and_injuries').innerText = +document.report_form.fatal_injuries.value + +document.report_form.notifiable_injuries_or_incidents.value + +document.report_form.lost_time_injuries_or_illnesses.value + +document.report_form.medically_treated_injuries_or_illnesses.value + +document.report_form.first_aid_injuries.value + +document.report_form.injury_with_no_treatment.value;
    document.getElementById('rate_total_fatalities_and_injuries').innerText = (+document.getElementById('total_fatalities_and_injuries').innerText / hours * 1000000).toFixed();
    document.getElementById('total_overall').innerText = +document.getElementById('total_fatalities_and_injuries').innerText + +document.report_form.traffic_accidents_involving_project_vehicles_equipment.value + +document.report_form.accidents_involving_non_project_vehicles_or_property.value + +document.report_form.environmental_incident.value + +document.report_form.escape_of_a_substance_into_the_atmosphere.value + +document.report_form.utility_or_service_strike.value + +document.report_form.damage_to_public_property_or_equipment.value + +document.report_form.damage_to_contractors_equipment.value + +document.report_form.worker_leaving_site_due_to_safety_concerns.value + +document.report_form.staff_on_reduced_alternate_duties.value + +document.report_form.traffic_accidents_involving_project_vehicles_equipment.value + +document.report_form.accidents_involving_non_project_vehicles_or_property.value + +document.report_form.environmental_incident.value + +document.report_form.escape_of_a_substance_into_the_atmosphere.value + +document.report_form.utility_or_service_strike.value + +document.report_form.damage_to_public_property_or_equipment.value + +document.report_form.damage_to_contractors_equipment.value + +document.report_form.worker_leaving_site_due_to_safety_concerns.value + +document.report_form.staff_on_reduced_alternate_duties.value;
    document.getElementById('rate_total_overall').innerText = (+document.getElementById('total_overall').innerText / hours * 1000000).toFixed();;
}
function toggleOptions() {
    var fields = document.getElementsByClassName('ohs_plan_extra');
    if(document.getElementById('update_to_the_ohs_safety_plan').checked) {
        for(var i = 0; i < fields.length; i++)
            fields[i].style.display="table-row";
    } else {
        for(var i = 0; i < fields.length; i++)
            fields[i].style.display="none";
    }
}

toggleOptions();
recalculateRates();
</script>