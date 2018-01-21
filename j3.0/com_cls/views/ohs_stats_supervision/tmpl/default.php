<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010-2017 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

showOHSSupervisionReports();

function showOHSSupervisionReports() {

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
    $query->select('*')->from($db->quoteName('#__ohs_supervision_reporting'))->where($db->quoteName('report_month')." = ".$db->quote($report_month.'-01'))->where($db->quoteName('contract_id')." = ".$db->quote($contract_id));
    $db->setQuery($query);
    $report_data = array_pop($db->loadObjectList());

    $query = $db->getQuery(true);
    $fields = array(
        'sum(number_of_serious_ohs_issues_during_the_month) as number_of_serious_ohs_issues_during_the_month',
        'sum(violations_ppe_male) as violations_ppe_male',
        'sum(violations_driving_male) as violations_driving_male',
        'sum(violations_traffic_management_male) as violations_traffic_management_male',
        'sum(violations_work_practice_male) as violations_work_practice_male',
        'sum(violations_others_male) as violations_others_male',
        'sum(violations_ppe_female) as violations_ppe_female',
        'sum(violations_driving_female) as violations_driving_female',
        'sum(violations_traffic_management_female) as violations_traffic_management_female',
        'sum(violations_work_practice_female) as violations_work_practice_female',
        'sum(violations_others_female) as violations_others_female',
    );
    $query->select($fields)->from($db->quoteName('#__ohs_supervision_reporting'))->where($db->quoteName('report_month')." >= ".$db->quote($report_month_last_six.'-01'))->where($db->quoteName('contract_id')." = ".$db->quote($contract_id));
    $db->setQuery($query);
    $report_data_last_six_sum = array_pop($db->loadObjectList());

    $query = $db->getQuery(true);
    $query->select('*')->from($db->quoteName('#__ohs_supervision_reporting'))->where($db->quoteName('report_month')." >= ".$db->quote($report_month_last_six.'-01'))->where($db->quoteName('contract_id')." = ".$db->quote($contract_id))->order($db->quoteName('report_month') . ' asc');
    $db->setQuery($query);
    $report_data_last_six = $db->loadObjectList();

    $document = JFactory::getDocument();
    JHtml::_('jquery.framework');
    $document->addScript('//code.highcharts.com/highcharts.js');

    // violations and warnings given male
    $violations_and_warnings_male = $report_data->violations_ppe_male + $report_data->violations_driving_male + $report_data->violations_traffic_management_male + $report_data->violations_work_practice_male + $report_data->violations_others_male;
    $violations_and_warnings_male += $report_data->warnings_ppe_male + $report_data->warnings_driving_male + $report_data->warnings_traffic_management_male + $report_data->warnings_work_practice_male + $report_data->warnings_others_male;
    // violations and warnings given female
    $violations_and_warnings_female = $report_data->violations_ppe_female + $report_data->violations_driving_female + $report_data->violations_traffic_management_female + $report_data->violations_work_practice_female + $report_data->violations_others_female;
    $violations_and_warnings_female += $report_data->warnings_ppe_female + $report_data->warnings_driving_female + $report_data->warnings_traffic_management_female + $report_data->warnings_work_practice_female + $report_data->warnings_others_female;

    ?>
    <h2><?php echo JText::_('CLS_OHS_SUPERVISION_REPORTING_STATISTICS_HEADER'); ?></h2>
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
    <input type="hidden" name="view" value="ohs_stats_supervision" />
    <input type="hidden" name="Itemid" value="<?php echo JRequest::getInt('Itemid') ?>" />
    </form>

    <br>

    <div><strong><?php echo JText::_('CLS_OHS_NUMBER_OF_SERIOUS_OHS_ISSUES_DURING_THE_MONTH') ?>:</strong> <?php echo $report_data->number_of_serious_ohs_issues_during_the_month; ?></div>
    <br>
    <div><strong><?php echo JText::_('CLS_OHS_NUMBER_OF_SERIOUS_OHS_ISSUES_DURING_LAST_SIX_MONTHS') ?>:</strong> <?php echo $report_data_last_six_sum->number_of_serious_ohs_issues_during_the_month; ?></div>

    <br>

    <table style="border:3px dashed #ccc;padding:8px;" cellpadding="4">
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_DAYS_WORKED'); ?></td><td nowrap style="color:green;"><?php echo $report_data->number_of_days_worked; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_LINK_TO_MONTHTLY_SAFETY_OFFICER_REPORT'); ?></td><td><a href="<?php echo JURI::root().'administrator/components/com_cls/uploads/'.$report_data->monthtly_safety_officer_report; ?>"><?php echo JText::_('CLS_OHS_OPEN'); ?></a></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_FULL_INSPECTIONS'); ?></td><td nowrap style="color:green;"><?php echo $report_data->number_of_full_inspections; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_LINK_TO_OTHER_SAFETY_RELATED_DOCUMENTS'); ?></td><td><a href="<?php echo JURI::root().'administrator/components/com_cls/uploads/'.$report_data->other_safety_related_documents; ?>"><?php echo JText::_('CLS_OHS_OPEN'); ?></a></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_PARTIAL_INSPECTIONS'); ?></td><td nowrap style="color:green;"><?php echo $report_data->number_of_partial_inspections; ?></td><td>&nbsp;</td>
        </tr>

        <tr>
            <td><?php echo JText::_('CLS_OHS_UPDATED_PLAN_RECEIVED'); ?></td><td nowrap style="color:green;"><?php echo $report_data->plan_updated; ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_PLAN_REVIEWED_SUBMITTED_APPROVED'); ?></td><td nowrap style="color:green;"><?php echo $report_data->plan_reviewed_submitted_approved; ?></td>
        </tr>

        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS'); ?></td><td nowrap style="color:green;"><?php echo "M: {$report_data->number_of_workers_male} F: {$report_data->number_of_workers_female}"; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_FULL_PPE_PERCENT_OF_WORKERS'); ?></td><td nowrap style="color:green;"><?php echo "M: {$report_data->percentage_of_workers_with_full_ppe_male} F: {$report_data->percentage_of_workers_with_full_ppe_female}"; ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_HOURS'); ?></td><td nowrap style="color:green;"><?php echo "M: {$report_data->total_hours_worked_during_month_male}h F: {$report_data->total_hours_worked_during_month_female}h"; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_VIOLATIONS_AND_WARNINGS_GIVEN'); ?></td><td nowrap style="color:green;"><?php echo "M: {$violations_and_warnings_male} F: {$violations_and_warnings_female}"; ?></td>
        </tr>
    </table>

    <br>

    <h3><?php echo JText::_('CLS_OHS_VIOLATIONS_SIX_MONTHS'); ?></h3>
    <?php
        $months = array_reverse($months);
        foreach($months as $i => $month) {
            $x_axis[$i] = $month['key'];

            foreach($report_data_last_six as $data) {
                if($data->report_month == $month['key'].'-01') {
                    $y_ppe_violations[$i] = $data->violations_ppe_male + $data->violations_ppe_female;
                    $y_driving_violations[$i] = $data->violations_driving_male + $data->violations_driving_female;
                    $y_traffic_management_violations[$i] = $data->violations_traffic_management_male + $data->violations_traffic_management_female;
                    $y_work_practice_violations[$i] = $data->violations_work_practice_male + $data->violations_work_practice_female;
                    $y_others_violations[$i] = $data->violations_others_male + $data->violations_others_male;
                }
            }

            if(!isset($y_others_violations[$i])) {
                $y_ppe_violations[$i] = $y_driving_violations[$i] = $y_traffic_management_violations[$i] = $y_work_practice_violations[$i] = $y_others_violations[$i] = 0;
            }
        }

        $x_axis = json_encode($x_axis);

        $y_ppe_violations = json_encode($y_ppe_violations);
        $y_driving_violations = json_encode($y_driving_violations);
        $y_traffic_management_violations = json_encode($y_traffic_management_violations);
        $y_work_practice_violations = json_encode($y_work_practice_violations);
        $y_others_violations = json_encode($y_others_violations);
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
            name: 'PPE',
            data: $y_ppe_violations
        }, {
            name: 'Driving',
            data: $y_driving_violations
        }, {
            name: 'Traffic Management',
            data: $y_traffic_management_violations
        }, {
            name: 'Work Practice',
            data: $y_work_practice_violations
        }, {
            name: 'Other',
            data: $y_others_violations
        }]
    });
});
EOT;

    $document->addScriptDeclaration($chart_js);
    ?>
    <div id="chart_container" style="width:740px;height:400px;"></div>

    <br>

    <h3><?php echo JText::_('CLS_OHS_VIOLATIONS_STATISTICS_BY_GENDER'); ?></h3>
    <table class="ohs_table">
        <tr><th><?php echo JText::_('CLS_OHS_CATEGORY'); ?></th><th><?php echo JText::_('CLS_MALE'); ?></th><th><?php echo JText::_('CLS_OHS_CUMULATIVE_SIX_MONTHS'); ?></th><th><?php echo JText::_('CLS_FEMALE'); ?></th><th><?php echo JText::_('CLS_OHS_CUMULATIVE_SIX_MONTHS'); ?></th></tr>
        <tr><td>PPE                         </td><td><?php echo $report_data->violations_ppe_male; ?></td><td><?php echo $report_data_last_six_sum->violations_ppe_male; ?></td><td><?php echo $report_data->violations_ppe_female; ?></td><td><?php echo $report_data_last_six_sum->violations_ppe_female; ?></td></tr>
        <tr><td>Driving                     </td><td><?php echo $report_data->violations_driving_male; ?></td><td><?php echo $report_data_last_six_sum->violations_driving_male; ?></td><td><?php echo $report_data->violations_driving_female; ?></td><td><?php echo $report_data_last_six_sum->violations_driving_female; ?></td></tr>
        <tr><td>Traffic management          </td><td><?php echo $report_data->violations_traffic_management_male; ?></td><td><?php echo $report_data_last_six_sum->violations_traffic_management_male; ?></td><td><?php echo $report_data->violations_traffic_management_female; ?></td><td><?php echo $report_data_last_six_sum->violations_traffic_management_female; ?></td></tr>
        <tr><td>Work practice               </td><td><?php echo $report_data->violations_work_practice_male; ?></td><td><?php echo $report_data_last_six_sum->violations_work_practice_male; ?></td><td><?php echo $report_data->violations_work_practice_female; ?></td><td><?php echo $report_data_last_six_sum->violations_work_practice_female; ?></td></tr>
        <tr><td>Others                      </td><td><?php echo $report_data->violations_others_male; ?></td><td><?php echo $report_data_last_six_sum->violations_others_male; ?></td><td><?php echo $report_data->violations_others_female; ?></td><td><?php echo $report_data_last_six_sum->violations_others_female; ?></td></tr>
    </table>
    <?php if($report_data->workers_living_in_camps == 'Y'): ?>
    <br>

    <table style="border:3px dashed #ccc;padding:8px;" cellpadding="4">
        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_WORKERS') ?></td><td nowrap style="color:green;"><?php echo JText::_('CLS_OHS_EXPATRIATES'); ?>: <?php echo $report_data->number_of_expatriates_workers_in_camps; ?> <?php echo JText::_('CLS_OHS_LOCAL'); ?>: <?php echo $report_data->number_of_local_workers_in_camps; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_PROPER_SANITATION_FACILITY'); ?></td><td nowrap style="color:green;"><?php echo $report_data->proper_sanitation_facility; ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_DATE_OF_LAST_INSPECTION') ?></td><td nowrap style="color:green;"><?php echo $report_data->date_of_last_inspection; ?></td><td>&nbsp;</td><td><?php echo JText::_('CLS_OHS_APPROPRIATE_LIVING_AND_RECREATIONAL_SPACE_FOR_WORKERS'); ?></td><td nowrap style="color:green;"><?php echo $report_data->appropriate_living_and_recreational_space_for_workers; ?></td>
        </tr>
        <tr>
            <td><?php echo JText::_('CLS_OHS_FACILITIES_IN_COMPLIANCE_WITH_LOCAL_LAWS_AND_ESMP'); ?></td><td nowrap style="color:green;"><?php echo $report_data->facilities_in_compliance_with_local_laws_and_esmp; ?></td>
        </tr>

        <tr>
            <td><?php echo JText::_('CLS_OHS_NUMBER_OF_VEHICLES_OR_EQUIPMENT_UNSAFE_OR_IMPROPERLY_MAINTAINED'); ?></td><td nowrap style="color:green;"><?php echo $report_data->number_of_vehicles_or_equipment_unsafe_or_improperly_maintained; ?></td>
        </tr>
    </table>
    <?php endif; ?>

    <style type="text/css">
    .ohs_table th {background-color:#0e4b78;color:#fff;padding:8px;}
    .ohs_table td {padding:5px;}
    .ohs_table tr:nth-child(even) td {background-color:#e9f4ff;}
    .ohs_table tr:nth-child(odd) td {background-color:#e0a4ee;}
    </style>

<?php } ?>