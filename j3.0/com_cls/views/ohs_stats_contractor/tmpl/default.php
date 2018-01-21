<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010-2017 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

showOHSContractorReports();

function showOHSContractorReports() {

    $db = JFactory::getDBO();
    $user = JFactory::getUser();
    $session = JFactory::getSession();
    $config = JComponentHelper::getParams('com_cls');
    $params = JFactory::getApplication()->getMenu()->getActive()->params;

    // months list
    $months[] = array('key' => date('Y-m', strtotime('-1 month', time())), 'value' => date('M', strtotime('-1 month', time())));
    $months[] = array('key' => date('Y-m', strtotime('-2 month', time())), 'value' => date('M', strtotime('-2 month', time())));
    $months[] = array('key' => date('Y-m', strtotime('-3 month', time())), 'value' => date('M', strtotime('-3 month', time())));
    $months[] = array('key' => date('Y-m', strtotime('-4 month', time())), 'value' => date('M', strtotime('-4 month', time())));
    $months[] = array('key' => date('Y-m', strtotime('-5 month', time())), 'value' => date('M', strtotime('-5 month', time())));
    $months[] = array('key' => date('Y-m', strtotime('-6 month', time())), 'value' => date('M', strtotime('-6 month', time())));
    $report_month = JRequest::getVar('report_month', date('Y-m', strtotime('-1 month', time())));
    $report_month_last_six = date('Y-m', strtotime('-6 month', time()));
    $lists['months'] = JHTML::_('select.genericlist', $months, 'report_month', array('onchange' => "document.report_form.submit()"), 'key', 'value', $report_month);

    // contract_id list
    $query = 'select * from #__complaint_contracts';
    $db->setQuery($query);
    $contracts = $db->loadObjectList();
    $contract[] = array('key' => '', 'value' => '- Select Contract -');
    foreach($contracts as $a)
        $contract[] = array('key' => $a->id, 'value' => $a->name);
    $contract_id = JRequest::getInt('contract_id', $contracts[0]->id);
    $lists['contract'] = JHTML::_('select.genericlist', $contract, 'contract_id', array('onchange' => "document.report_form.submit()"), 'key', 'value', $contract_id);

    $query = $db->getQuery(true);
    $query->select('*')->from($db->quoteName('#__ohs_contractor_reporting'))->where($db->quoteName('report_month')." = ".$db->quote($report_month.'-01'))->where($db->quoteName('contract_id')." = ".$db->quote($contract_id));
    $db->setQuery($query);
    $report_data = array_pop($db->loadObjectList());

    $query = $db->getQuery(true);
    $fields = array(
        'sum(number_of_workers_trained) as number_of_workers_trained',
        'sum(number_of_competency_assessments) as number_of_competency_assessments',
        'sum(number_of_new_skill_training_sessions) as number_of_new_skill_training_sessions',
        'sum(number_of_ohs_training) as number_of_ohs_training',
        'sum(number_of_hiv_aids_training) as number_of_hiv_aids_training',
        'sum(number_of_gbv_vac_training) as number_of_gbv_vac_training',
        'sum(checks_site_health_and_safety_audits) as checks_site_health_and_safety_audits',
        'sum(checks_safety_briefings) as checks_safety_briefings',
        'sum(number_of_near_misses) as number_of_near_misses',
        'sum(number_of_stop_work_actions) as number_of_stop_work_actions',
        'sum(number_of_traffic_management_inspections) as number_of_traffic_management_inspections',
        'sum(number_of_completed_investigations) as number_of_completed_investigations',
        'sum(number_of_new_risks_identified) as number_of_new_risks_identified',
        'sum(number_of_suggestions_for_improvement_identified) as number_of_suggestions_for_improvement_identified',
        'sum(checks_drugs) as checks_drugs',
        'sum(checks_alcohol) as checks_alcohol',
        'sum(checks_hiv) as checks_hiv',
        'sum(checks_drugs_positive) as checks_drugs_positive',
        'sum(checks_alcohol_positive) as checks_alcohol_positive',
        'sum(checks_hiv_positive) as checks_hiv_positive',
        'sum(fatal_injuries) as fatal_injuries',
        'sum(notifiable_injuries_or_incidents) as notifiable_injuries_or_incidents',
        'sum(lost_time_injuries_or_illnesses) as lost_time_injuries_or_illnesses',
        'sum(medically_treated_injuries_or_illnesses) as medically_treated_injuries_or_illnesses',
        'sum(first_aid_injuries) as first_aid_injuries',
        'sum(injury_with_no_treatment) as injury_with_no_treatment',
        'sum(traffic_accidents_involving_project_vehicles_equipment) as traffic_accidents_involving_project_vehicles_equipment',
        'sum(accidents_involving_non_project_vehicles_or_property) as accidents_involving_non_project_vehicles_or_property',
        'sum(environmental_incident) as environmental_incident',
        'sum(escape_of_a_substance_into_the_atmosphere) as escape_of_a_substance_into_the_atmosphere',
        'sum(utility_or_service_strike) as utility_or_service_strike',
        'sum(damage_to_public_property_or_equipment) as damage_to_public_property_or_equipment',
        'sum(damage_to_contractors_equipment) as damage_to_contractors_equipment',
        'sum(worker_leaving_site_due_to_safety_concerns) as worker_leaving_site_due_to_safety_concerns',
        'sum(staff_on_reduced_alternate_duties) as staff_on_reduced_alternate_duties'
    );
    $query->select($fields)->from($db->quoteName('#__ohs_contractor_reporting'))->where($db->quoteName('report_month')." >= ".$db->quote($report_month_last_six.'-01'))->where($db->quoteName('contract_id')." = ".$db->quote($contract_id));
    $db->setQuery($query);
    $report_data_last_six_sum = array_pop($db->loadObjectList());

    $query = $db->getQuery(true);
    $query->select('*')->from($db->quoteName('#__ohs_contractor_reporting'))->where($db->quoteName('report_month')." >= ".$db->quote($report_month_last_six.'-01'))->where($db->quoteName('contract_id')." = ".$db->quote($contract_id))->order($db->quoteName('report_month') . ' asc');
    $db->setQuery($query);
    $report_data_last_six = $db->loadObjectList();

    $document = JFactory::getDocument();
    JHtml::_('jquery.framework');
    $document->addScript('//code.highcharts.com/highcharts.js');

    ?>
    <h2><?php echo JText::_('CLS_OHS_CONTRACTOR_REPORTING_STATISTICS_HEADER'); ?></h2>
    <form name="report_form" action="index.php" method="post">
    <table>
        <tr>
            <th><?php echo JText::_('CLS_OHS_SELECT_MONTH') ?>:</th>
            <td><?php echo $lists['months']; ?></td>
            <td>&nbsp;</td>
            <th><?php echo JText::_('CLS_OHS_SELECT_CONTRACT') ?>:</th>
            <td><?php echo $lists['contract']; ?></td>
        </tr>
    </table>
    <input type="hidden" name="option" value="com_cls" />
    <input type="hidden" name="view" value="ohs_stats_contractor" />
    <input type="hidden" name="Itemid" value="<?php echo JRequest::getInt('Itemid') ?>" />
    </form>

    <br>

    <table style="border:3px dashed #ccc;padding:8px;" cellpadding="4">
        <tr>
            <td><?php echo JText::_('CLS_OHS_CONTRACTOR_REPRESENTATIVE'); ?></td><td nowrap colspan="4" style="color:green;"><?php echo JFactory::getUser($report_data->user_id)->get('name'); ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_HOURS'); ?></td><td nowrap style="color:green;"><?php echo "M: {$report_data->number_of_hours_worked_this_month_male}h F: {$report_data->number_of_hours_worked_this_month_female}h"; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_LINK_TO_OHS_PLAN'); ?></td><td><a href="<?php echo JURI::root().'administrator/components/com_cls/uploads/'.$report_data->safety_officers_monthly_report; ?>"><?php echo JText::_('CLS_OHS_OPEN'); ?></a></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS'); ?></td><td nowrap style="color:green;"><?php echo "M: {$report_data->number_of_workers_male} F: {$report_data->number_of_workers_female}"; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_LINK_TO_SAFETY_OFFICER_PLAN'); ?></td><td><a href="<?php echo JURI::root().'administrator/components/com_cls/uploads/'.$report_data->ohsmp_updates_or_changes; ?>"><?php echo JText::_('CLS_OHS_OPEN'); ?></a></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_UPDATES_ON_OHS_PLAN'); ?></td><td nowrap style="color:green;"><?php echo $report_data->update_to_the_ohs_safety_plan; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_LINK_TO_OTHER_DOCUMENTS'); ?></td><td><a href="<?php echo JURI::root().'administrator/components/com_cls/uploads/'.$report_data->other_safety_related_documents; ?>"><?php echo JText::_('CLS_OHS_OPEN'); ?></a></td>
        </tr>
    </table>

    <br>

    <table class="ohs_table">
        <tr><th><?php echo JText::_('CLS_OHS_CATEGORY'); ?></th><th><?php echo JText::_('CLS_OHS_NUMBER'); ?></th><th><?php echo JText::_('CLS_OHS_CUMULATIVE_SIX_MONTHS'); ?></th></tr>
        <tr><td><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS_TRAINED'); ?>             </td><td><?php echo $report_data->number_of_workers_trained; ?></td><td><?php echo $report_data_last_six_sum->number_of_workers_trained; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_COMPETENCY_ASSESSMENTS'); ?>                </td><td><?php echo $report_data->number_of_competency_assessments; ?></td><td><?php echo $report_data_last_six_sum->number_of_competency_assessments; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_NEW_SKILL_TRAINING_SESSIONS'); ?>           </td><td><?php echo $report_data->number_of_new_skill_training_sessions; ?></td><td><?php echo $report_data_last_six_sum->number_of_new_skill_training_sessions; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_OHS_TRAINING_WORKER_HOURS'); ?>             </td><td><?php echo $report_data->number_of_ohs_training; ?></td><td><?php echo $report_data_last_six_sum->number_of_ohs_training; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_HIV_AIDS_TRAINING_WORKER_HOURS'); ?>        </td><td><?php echo $report_data->number_of_hiv_aids_training; ?></td><td><?php echo $report_data_last_six_sum->number_of_hiv_aids_training; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_GBV_VAC_TRAINING_WORKER_HOURS'); ?>         </td><td><?php echo $report_data->number_of_gbv_vac_training; ?></td><td><?php echo $report_data_last_six_sum->number_of_gbv_vac_training; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_SITE_HEALTH_AND_SAFETY_AUDITS'); ?>         </td><td><?php echo $report_data->checks_site_health_and_safety_audits; ?></td><td><?php echo $report_data_last_six_sum->checks_site_health_and_safety_audits; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_SAFETY_BRIEFINGS'); ?>                      </td><td><?php echo $report_data->checks_safety_briefings; ?></td><td><?php echo $report_data_last_six_sum->checks_safety_briefings; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_NEAR_MISSES'); ?>                           </td><td><?php echo $report_data->number_of_near_misses; ?></td><td><?php echo $report_data_last_six_sum->number_of_near_misses; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_STOP_WORK_ACTIONS'); ?>                     </td><td><?php echo $report_data->number_of_stop_work_actions; ?></td><td><?php echo $report_data_last_six_sum->number_of_stop_work_actions; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_TRAFFIC_MANAGEMENT_INSPECTIONS'); ?>        </td><td><?php echo $report_data->number_of_traffic_management_inspections; ?></td><td><?php echo $report_data_last_six_sum->number_of_traffic_management_inspections; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_COMPLETED_INVESTIGATIONS'); ?>              </td><td><?php echo $report_data->number_of_completed_investigations; ?></td><td><?php echo $report_data_last_six_sum->number_of_completed_investigations; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_NEW_RISKS_IDENTIFIED'); ?>                  </td><td><?php echo $report_data->number_of_new_risks_identified; ?></td><td><?php echo $report_data_last_six_sum->number_of_new_risks_identified; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_SUGGESTIONS_FOR_IMPROVEMENT_IDENTIFIED'); ?></td><td><?php echo $report_data->number_of_suggestions_for_improvement_identified; ?></td><td><?php echo $report_data_last_six_sum->number_of_suggestions_for_improvement_identified; ?></td></tr>
    </table>

    <br>

    <table class="ohs_table">
        <tr><th><?php echo JText::_('CLS_OHS_CATEGORY'); ?></th><th><?php echo JText::_('CLS_OHS_NUMBER'); ?></th><th><?php echo JText::_('CLS_OHS_CUMULATIVE_SIX_MONTHS'); ?></th><th><?php echo JText::_('CLS_OHS_PERCENT_POSITIVE'); ?></th><th><?php echo JText::_('CLS_OHS_PERCENT_POSITIVE_IN_LAST_SIX_MONTHS'); ?></th></tr>
        <tr><td><?php echo JText::_('CLS_OHS_DRUG_CHECKS'); ?></td><td><?php echo $report_data->checks_drugs; ?></td><td><?php echo $report_data_last_six_sum->checks_drugs; ?></td><td><?php echo ($report_data->checks_drugs > 0 ? round(100*$report_data->checks_drugs_positive/$report_data->checks_drugs, 2) : 0) . '%'; ?></td><td><?php echo ($report_data_last_six_sum->checks_drugs > 0 ? round(100*$report_data_last_six_sum->checks_drugs_positive/$report_data_last_six_sum->checks_drugs, 2) : 0) . '%'; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_ALCOHOL_CHECKS'); ?></td><td><?php echo $report_data->checks_alcohol; ?></td><td><?php echo $report_data_last_six_sum->checks_alcohol; ?></td><td><?php echo ($report_data->checks_alcohol > 0 ? round(100*$report_data->checks_alcohol_positive/$report_data->checks_alcohol, 2) : 0) . '%'; ?></td><td><?php echo ($report_data_last_six_sum->checks_alcohol > 0 ? round(100*$report_data_last_six_sum->checks_alcohol_positive/$report_data_last_six_sum->checks_alcohol, 2) : 0) . '%'; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_HIV_CHECKS'); ?></td><td><?php echo $report_data->checks_hiv; ?></td><td><?php echo $report_data_last_six_sum->checks_hiv; ?></td><td><?php echo ($report_data->checks_hiv > 0 ? round(100*$report_data->checks_hiv_positive/$report_data->checks_hiv, 2) : 0) . '%'; ?></td><td><?php echo ($report_data_last_six_sum->checks_hiv > 0 ? round(100*$report_data_last_six_sum->checks_hiv_positive/$report_data_last_six_sum->checks_hiv, 2) : 0) . '%'; ?></td></tr>
    </table>

    <br>


    <h3><?php echo JText::_('CLS_OHS_ACCIDENTS_AND_INCIDENTS_SIX_MONTHS'); ?></h3>
    <?php
        $months = array_reverse($months);
        foreach($months as $i => $month) {
            $x_axis[$i] = $month['key'];

            foreach($report_data_last_six as $data) {
                if($data->report_month == $month['key'].'-01') {
                    $y_first_aid_injuries[$i] = intval($data->first_aid_injuries);
                    $y_fatal_injuries[$i] = intval($data->fatal_injuries);
                    $y_environmental_incident[$i] = intval($data->environmental_incident);
                    // sum of other lagging indicators
                    $y_other[$i] = $data->staff_on_reduced_alternate_duties + $data->worker_leaving_site_due_to_safety_concerns + $data->damage_to_contractors_equipment + $data->damage_to_public_property_or_equipment + $data->utility_or_service_strike + $data->escape_of_a_substance_into_the_atmosphere + $data->accidents_involving_non_project_vehicles_or_property + $data->traffic_accidents_involving_project_vehicles_equipment + $data->injury_with_no_treatment + $data->medically_treated_injuries_or_illnesses + $data->lost_time_injuries_or_illnesses + $data->notifiable_injuries_or_incidents;
                }
            }

            if(!isset($y_other[$i])) {
                $y_first_aid_injuries[$i] = $y_fatal_injuries[$i] = $y_environmental_incident[$i] = $y_other[$i] = 0;
            }
        }

        $x_axis = json_encode($x_axis);

        $y_first_aid_injuries = json_encode($y_first_aid_injuries);
        $y_fatal_injuries = json_encode($y_fatal_injuries);
        $y_environmental_incident = json_encode($y_environmental_incident);
        $y_other = json_encode($y_other);
        $chart_js = <<< EOT
jQuery.noConflict();
var chart;
jQuery(document).ready(function() {
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'chart_container',
            type: 'line',
            marginRight: 130,
            marginBottom: 35
        },
        title: {
            text: '',
            style: {
                display: 'none'
            }
        },
        xAxis: {
            categories: $x_axis
        },
        yAxis: {
            title: {
                text: 'Count'
            },
            allowDecimals: false,
            min: 0,
            plotLines: [{
                value: 0,
                width: 1,
                color: '#808080'
            }]
        },
        tooltip: {
            formatter: function() {
                    return '<b>'+ this.series.name +'</b><br/>'+
                    this.x +': '+ this.y;
            }
        },
        legend: {
            layout: 'vertical',
            align: 'right',
            verticalAlign: 'top',
            x: 10,
            y: 150,
            borderWidth: 0
        },
        series: [{
            name: 'First Aid',
            data: $y_first_aid_injuries
        }, {
            name: 'Fatality',
            data: $y_fatal_injuries
        }, {
            name: 'Environmental Incident',
            data: $y_environmental_incident
        }, {
            name: 'Other',
            data: $y_other
        }]
    });
});
EOT;

    $document->addScriptDeclaration($chart_js);
    ?>
    <div id="chart_container" style="width:740px;height:400px;"></div>

    <br>

    <table class="ohs_table">
        <tr><th><?php echo JText::_('CLS_OHS_CATEGORY'); ?></th><th><?php echo JText::_('CLS_OHS_NUMBER'); ?></th><th><?php echo JText::_('INJURY_RATE'); ?></th><th><?php echo JText::_('CLS_OHS_CUMULATIVE_SIX_MONTHS'); ?></th></tr>
        <tr><td><?php echo JText::_('CLS_OHS_FATAL_INJURIES'); ?>                                        </td><td><?php echo $report_data->fatal_injuries; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->fatal_injuries; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_NOTIFIABLE_INJURIES_OR_INCIDENTS'); ?>                      </td><td><?php echo $report_data->notifiable_injuries_or_incidents; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->notifiable_injuries_or_incidents; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_LOST_TIME_INJURIES_OR_ILLNESSES'); ?>                       </td><td><?php echo $report_data->lost_time_injuries_or_illnesses; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->lost_time_injuries_or_illnesses; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_MEDICALLY_TREATED_INJURIES_OR_ILLNESSES'); ?>               </td><td><?php echo $report_data->medically_treated_injuries_or_illnesses; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->medically_treated_injuries_or_illnesses; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_FIRST_AID_INJURIES'); ?>                                    </td><td><?php echo $report_data->first_aid_injuries; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->first_aid_injuries; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_INJURY_WITH_NO_TREATMENT'); ?>                              </td><td><?php echo $report_data->injury_with_no_treatment; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->injury_with_no_treatment; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_TRAFFIC_ACCIDENTS_INVOLVING_PROJECT_VEHICLES_EQUIPMENT'); ?></td><td><?php echo $report_data->traffic_accidents_involving_project_vehicles_equipment; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->traffic_accidents_involving_project_vehicles_equipment; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_ACCIDENTS_INVOLVING_NON_PROJECT_VEHICLES_OR_PROPERTY'); ?>  </td><td><?php echo $report_data->accidents_involving_non_project_vehicles_or_property; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->accidents_involving_non_project_vehicles_or_property; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_ENVIRONMENTAL_INCIDENT'); ?>                                </td><td><?php echo $report_data->environmental_incident; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->environmental_incident; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_ESCAPE_OF_A_SUBSTANCE_INTO_THE_ATMOSPHERE'); ?>             </td><td><?php echo $report_data->escape_of_a_substance_into_the_atmosphere; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->escape_of_a_substance_into_the_atmosphere; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_UTILITY_OR_SERVICE_STRIKE'); ?>                             </td><td><?php echo $report_data->utility_or_service_strike; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->utility_or_service_strike; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_DAMAGE_TO_PUBLIC_PROPERTY_OR_EQUIPMENT'); ?>                </td><td><?php echo $report_data->damage_to_public_property_or_equipment; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->damage_to_public_property_or_equipment; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_DAMAGE_TO_CONTRACTORS_EQUIPMENT'); ?>                       </td><td><?php echo $report_data->damage_to_contractors_equipment; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->damage_to_contractors_equipment; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_WORKER_LEAVING_SITE_DUE_TO_SAFETY_CONCERNS'); ?>            </td><td><?php echo $report_data->worker_leaving_site_due_to_safety_concerns; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->worker_leaving_site_due_to_safety_concerns; ?></td></tr>
        <tr><td><?php echo JText::_('CLS_OHS_STAFF_ON_REDUCED_ALTERNATE_DUTIES'); ?>                     </td><td><?php echo $report_data->staff_on_reduced_alternate_duties; ?></td><td>? %</td><td><?php echo $report_data_last_six_sum->staff_on_reduced_alternate_duties; ?></td></tr>
    </table>

    <style type="text/css">
    .ohs_table th {background-color:#0e4b78;color:#fff;padding:8px;}
    .ohs_table td {padding:5px;}
    .ohs_table tr:nth-child(even) td {background-color:#f0f0f0;}
    .ohs_table tr:nth-child(odd) td {background-color:#e0e0e0;}
    </style>

<?php } ?>