<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

showReports();

function showReports() {

    JHTML::_('behavior.calendar');

    $db = JFactory::getDBO();
    $user = JFactory::getUser();
    $session = JFactory::getSession();
    $config = JComponentHelper::getParams('com_cls');
    $params = JFactory::getApplication()->getMenu()->getActive()->params;
    $center_map = $config->get('center_map');
    $map_api_key = $config->get('map_api_key');
    $zoom_level = $config->get('zoom_level');
    $statistics_period = (int) $session->get('statistics_period', $config->get('statistics_period', 20));
    //$statistics_period_compare = (int) $config->get('statistics_period_compare', 5);
    //$delayed_resolution_period = (int) $config->get('delayed_resolution_period', 30);

    // set separate warning periods for low, medium, high priorities
    $action_period_low = (int) $config->get('action_period_low', 30);
    $action_period_medium = (int) $config->get('action_period_medium', 10);
    $action_period_high = (int) $config->get('action_period_high', 5);

    $startdate = JRequest::getCmd('startdate', $session->get('startdate', date('Y-m-d', strtotime("-$statistics_period days")), 'com_cls'));
    $session->set('startdate', $startdate, 'com_cls');
    $enddate = JRequest::getCmd('enddate', $session->get('enddate', date('Y-m-d'), 'com_cls'));
    $session->set('enddate', $enddate, 'com_cls');

    $statistics_period = (int) ((strtotime($enddate) - strtotime($startdate)) / 86400);

    /*
    ?>
    <h3>Statistics Period</h3>
    <form action="" method="get">
    <table>
        <tr>
            <td><?php echo JText::_('Start Date'); ?></td>
            <td>
            <?php echo JHTML::_('calendar', $startdate, "startdate" , "startdate", '%Y-%m-%d');?>
            </td>
        </tr>
        <tr>
            <td><?php echo JText::_('End Date'); ?></td>
            <td>
            <?php echo JHTML::_('calendar', $enddate, "enddate" , "enddate", '%Y-%m-%d');?>
            </td>
        </tr>
    </table>
    <br />
    <input type="submit" value="Submit" />
    <input type="hidden" name="option" value="com_cls" />
    <input type="hidden" name="task" value="reports.show" />
    <?php echo JHTML::_( 'form.token' ); ?>
    </form>

    <?php
    $user_type = JFactory::getUser()->getParam('role', 'Guest');

    // guest cannot see this list
    if($user_type != 'Guest' and $user_type != 'Level 2') {
        # -- Complaint Downloads --
        echo '<h3>Complaint Downloads</h3>';
        echo '<a href="index.php?option=com_cls&amp;task=download_report&period=period">Download Selected Period</a><br />';
        echo '<a href="index.php?option=com_cls&amp;task=download_report&period=current_month">Download Current Month</a><br />';
        echo '<a href="index.php?option=com_cls&amp;task=download_report&period=prev_month">Download Previous Month</a><br />';
        echo '<a href="index.php?option=com_cls&amp;task=download_report&period=all">Download All</a>';
        # -- End Complaint Downloads --
    }

    */

    # -- Complaint Averages --
    $db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD('$enddate', interval -$statistics_period day)");
    $complaints_received = $db->loadResult();
    $complaints_received_per_day = round($complaints_received/$statistics_period, 2);
    $db->setQuery("select count(*) from #__complaints where date_processed >= DATE_ADD('$enddate', interval -$statistics_period day)");
    $complaints_processed = $db->loadResult();
    $complaints_processed_per_day = round($complaints_processed/$statistics_period, 2);
    $db->setQuery("select count(*) from #__complaints where date_resolved >= DATE_ADD('$enddate', interval -$statistics_period day)");
    $complaints_resolved = $db->loadResult();
    $complaints_resolved_per_day = round($complaints_resolved/$statistics_period, 2);
    //$db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N' and date_processed >= DATE_ADD('$enddate', interval -$statistics_period day) and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= '$enddate 23:59:59'");
    $db->setQuery("select * from #__complaints where confirmed_closed = 'N' and date_received <= '$enddate 23:59:59'");
    $complaints_not_resolved = $db->loadObjectList();
    $complaints_delayed = 0;
    foreach($complaints_not_resolved as $complaint) {
        if($complaint->message_priority == '')
            $complaint->message_priority = 'Low';

        switch($complaint->message_priority) {
            case 'Low': if($action_period_low*24*60*60 < strtotime("$enddate 23:59:59") - strtotime($complaint->date_received)) $complaints_delayed++; break;
            case 'Medium': if($action_period_medium*24*60*60 < strtotime("$enddate 23:59:59") - strtotime($complaint->date_received)) $complaints_delayed++; break;
            case 'High': if($action_period_high*24*60*60 < strtotime("$enddate 23:59:59") - strtotime($complaint->date_received)) $complaints_delayed++; break;
            default: break;
        }
    }

    /*
    $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_received >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_received <= DATE_ADD(now(), interval -$statistics_period_compare day)");
    $complaints_received_per_day2 = $db->loadResult();
    $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_processed >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_processed <= DATE_ADD(now(), interval -$statistics_period_compare day)");
    $complaints_processed_per_day2 = $db->loadResult();
    $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_resolved >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_resolved <= DATE_ADD(now(), interval -$statistics_period_compare day)");
    $complaints_resolved_per_day2 = $db->loadResult();

    @$complaints_received_growth = ($complaints_received_per_day >= $complaints_received_per_day2 ? '+' : '-') . round(abs($complaints_received_per_day - $complaints_received_per_day2)/$complaints_received_per_day*100, 2) . '%';
    @$complaints_processed_growth = ($complaints_processed_per_day >= $complaints_processed_per_day2 ? '+' : '-') . round(abs($complaints_processed_per_day - $complaints_processed_per_day2)/$complaints_processed_per_day*100, 2) . '%';
    @$complaints_resolved_growth = ($complaints_resolved_per_day >= $complaints_resolved_per_day2 ? '+' : '-') . round(abs($complaints_resolved_per_day - $complaints_resolved_per_day2)/$complaints_resolved_per_day*100, 2) . '%';
    */

    $db->setQuery("select * from #__complaints where date_received <= '$enddate 23:59:59'");
    $all_complaints_received_till_date = $db->loadObjectList();

    $res_within_standards = $res_within_standards_low = $res_within_standards_medium = $res_within_standards_high = 0;
    foreach($all_complaints_received_till_date as $complaint) {
        if($complaint->message_priority == '')
            $complaint->message_priority = 'Low';

        switch($complaint->message_priority) {
            case 'Low': $action_period = $action_period_low; break;
            case 'Medium': $action_period = $action_period_medium; break;
            case 'High': $action_period = $action_period_high; break;
            default: break;
        }

        if(!empty($complaint->date_resolved) and $action_period*24*60*60 >= (strtotime($complaint->date_resolved) - strtotime($complaint->date_received))) {
            if($complaint->message_priority == 'Low')
                $res_within_standards_low++;
            elseif($complaint->message_priority == 'Medium')
                $res_within_standards_medium++;
            elseif($complaint->message_priority == 'High')
                $res_within_standards_high++;
        }
    }

    $db->setQuery("select * from #__complaints where related_to_pb = 1 and date_received <= '$enddate 23:59:59'");
    $all_complaints_received_till_date2 = $db->loadObjectList();

    $res_within_standards2 = $res_within_standards_low2 = $res_within_standards_medium2 = $res_within_standards_high2 = 0;
    foreach($all_complaints_received_till_date2 as $complaint) {
        if($complaint->message_priority == '')
            $complaint->message_priority = 'Low';

        switch($complaint->message_priority) {
            case 'Low': $action_period = $action_period_low; break;
            case 'Medium': $action_period = $action_period_medium; break;
            case 'High': $action_period = $action_period_high; break;
            default: break;
        }

        if(!empty($complaint->date_resolved) and $action_period*24*60*60 >= (strtotime($complaint->date_resolved) - strtotime($complaint->date_received))) {
            if($complaint->message_priority == 'Low')
                $res_within_standards_low2++;
            elseif($complaint->message_priority == 'Medium')
                $res_within_standards_medium2++;
            elseif($complaint->message_priority == 'High')
                $res_within_standards_high2++;
        }
    }

    $db->setQuery("select * from #__complaints where related_to_pb = 1 and gender = 'Female' and date_received <= '$enddate 23:59:59'");
    $all_complaints_received_till_date3 = $db->loadObjectList();

    $res_within_standards3 = $res_within_standards_low3 = $res_within_standards_medium3 = $res_within_standards_high3 = 0;
    foreach($all_complaints_received_till_date3 as $complaint) {
        if($complaint->message_priority == '')
            $complaint->message_priority = 'Low';

        switch($complaint->message_priority) {
            case 'Low': $action_period = $action_period_low; break;
            case 'Medium': $action_period = $action_period_medium; break;
            case 'High': $action_period = $action_period_high; break;
            default: break;
        }

        if(!empty($complaint->date_resolved) and $action_period*24*60*60 >= (strtotime($complaint->date_resolved) - strtotime($complaint->date_received))) {
            if($complaint->message_priority == 'Low')
                $res_within_standards_low3++;
            elseif($complaint->message_priority == 'Medium')
                $res_within_standards_medium3++;
            elseif($complaint->message_priority == 'High')
                $res_within_standards_high3++;
        }
    }

    $res_within_standards = $res_within_standards_low + $res_within_standards_medium + $res_within_standards_high;
    $res_within_standards2 = $res_within_standards_low2 + $res_within_standards_medium2 + $res_within_standards_high2;
    $res_within_standards3 = $res_within_standards_low3 + $res_within_standards_medium3 + $res_within_standards_high3;

    $db->setQuery("select count(*) from #__complaints where date_received <= '$enddate 23:59:59'");
    $complaints_received_till_date = $db->loadResult();

    $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and date_received <= '$enddate 23:59:59'");
    $all_complaints_related_to_pb = $db->loadResult();

    $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and date_received <= '$enddate 23:59:59'");
    $all_complaints_related_to_pb_and_females = $db->loadResult();

    $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and related_to_pb = 1 and date_received <= '$enddate 23:59:59'");
    $complaints_resolved_related_to_pb = $db->loadResult();

    $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and related_to_pb = 1 and gender = 'Female' and date_received <= '$enddate 23:59:59'");
    $complaints_resolved_related_to_pb_and_females = $db->loadResult();

    $total_res_within_standards = $res_within_standards;
    $res_within_standards = ($complaints_received_till_date > 0 ? round($res_within_standards/$complaints_received_till_date * 100, 1) . ' %' : '0 %');
    $rel_pb_addressed = ($all_complaints_related_to_pb > 0 ? round($complaints_resolved_related_to_pb/$all_complaints_related_to_pb * 100, 1) . ' %' : '0 %');

    $total_res_within_standards2 = $res_within_standards2;
    $total_res_within_standards3 = $res_within_standards3;

    if($params->get('show_summary', '1') == '1') {
        echo '<h3>Summary of Complaints</h3>';
        echo '<i>Complaints Received Per Day:</i> ' . $complaints_received_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_received_growth . '</small><br />';
        echo '<i>Complaints Processed Per Day:</i> ' . $complaints_processed_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_processed_growth . '</small><br />';
        echo '<i>Complaints Resolved Per Day:</i> ' . $complaints_resolved_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_resolved_growth . '</small><br />';
        echo '<i>Number of Complaints Received:</i> ' . $complaints_received . ' <br />';
        echo '<i>Number of Complaints Resolved:</i> ' . $complaints_resolved . ' <br />';
        echo '<i>Number of Complaints Outstanding:</i> ' . ($complaints_received - $complaints_resolved < 0 ? 0 : $complaints_received - $complaints_resolved) . ' <br />';
        echo '<i>Number of Complaints with Delayed Resolution:</i> ' . $complaints_delayed . ' <br />';
        echo '<i>Grievances responded to and/or resolved within the stipulated service standards:</i> ' . $res_within_standards . '<br />';
        echo '<i>Grievances registered related to delivery of project benefits that are actually addressed:</i> ' . $rel_pb_addressed . '<br />';

        echo '<br /><small><i>The averages are based on ' . $statistics_period . ' days period data.</i></small>';
    }
    # -- End Complaint Averages --

    # -- Complaint Statistics --
    for($i = 0, $time = strtotime($startdate); $time < strtotime($enddate) + 86400; $i++, $time = strtotime("$startdate +$i days"))
        $dates[date('M j', $time)] = 0;
    //echo '<pre>', print_r($dates, true), '</pre>';

    $db->setQuery("select count(*) as count, date_format(date_received, '%b %e') as date from #__complaints where date_received >= DATE_ADD('$enddate', interval -$statistics_period day) group by date order by date_received");
    $received = $db->loadObjectList();
    $complaints_received = $dates;
    foreach($received as $complaint)
        $complaints_received[$complaint->date] = (int) $complaint->count;
    //echo '<pre>', print_r($complaints_received, true), '</pre>';

    $db->setQuery("select count(*) as count, date_format(date_processed, '%b %e') as date from #__complaints where date_processed >= DATE_ADD('$enddate', interval -$statistics_period day) group by date order by date_processed");
    $processed = $db->loadObjectList();
    $complaints_processed = $dates;
    foreach($processed as $complaint)
        $complaints_processed[$complaint->date] = (int) $complaint->count;
    //echo '<pre>', print_r($complaints_processed, true), '</pre>';

    $db->setQuery("select count(*) as count, date_format(date_resolved, '%b %e') as date from #__complaints where date_resolved >= DATE_ADD('$enddate', interval -$statistics_period day) group by date order by date_resolved");
    $resolved = $db->loadObjectList();
    $complaints_resolved = $dates;
    foreach($resolved as $complaint)
        $complaints_resolved[$complaint->date] = (int) $complaint->count;
    //echo '<pre>', print_r($complaints_resolved, true), '</pre>';

    for($i = 0, $time = strtotime($startdate); $time < strtotime($enddate) + 86400; $i++, $time = strtotime("$startdate +$i days")) {
        $date = date('Y-m-d', $time);
        $key = date('M j', $time);
        //$db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD('$date', interval -" . ($delayed_resolution_period + $statistics_period) . " day) and date_received <= '$date' and ((date_resolved is not null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= date_resolved) or (date_resolved is null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= '$date'))");

        $db->setQuery("select * from #__complaints where confirmed_closed = 'N' and date_received <= '$date 23:59:59'");
        $complaints_not_resolved = $db->loadObjectList();
        $delayed_resolution[$key] = 0;
        foreach($complaints_not_resolved as $complaint) {
            if($complaint->message_priority == '')
                $complaint->message_priority = 'Low';

            switch($complaint->message_priority) {
                case 'Low': if($action_period_low*24*60*60 < strtotime("$date 23:59:59") - strtotime($complaint->date_received)) $delayed_resolution[$key]++; break;
                case 'Medium': if($action_period_medium*24*60*60 < strtotime("$date 23:59:59") - strtotime($complaint->date_received)) $delayed_resolution[$key]++; break;
                case 'High': if($action_period_high*24*60*60 < strtotime("$date 23:59:59") - strtotime($complaint->date_received)) $delayed_resolution[$key]++; break;
                default: break;
            }
        }
    }
    //echo '<pre>', print_r($delayed_resolution, true), '</pre>';

    $max = max(max($complaints_received), max($complaints_processed), max($complaints_resolved), max($delayed_resolution));
    $max = ceil($max/5)*5;
    //echo 'Max: ', $max;

    //$x_axis  = implode('|', array_keys($dates));
    //$y_axis  = implode('|', range(0, $max, $max/5));

    $x_axis  = json_encode(array_keys($dates));
    //$y_axis  = json_encode(range(0, $max, $max/5));

    $y_complaints_received = json_encode(array_values($complaints_received));
    $y_complaints_processed = json_encode(array_values($complaints_processed));
    $y_complaints_resolved = json_encode(array_values($complaints_resolved));
    $y_complaints_delayed = json_encode(array_values($delayed_resolution));

    /*
    $complaints_per_day_link  = "http://chart.apis.google.com/chart?chs=900x330&amp;";
    $complaints_per_day_link .= "cht=lc&amp;";
    $complaints_per_day_link .= "chdl=Complaints Received|Complaints Processed|Complaints Resolved|Delayed Resolution&amp;";
    $complaints_per_day_link .= "chdlp=b&amp;";
    $complaints_per_day_link .= "chco=000080FF,008000FF,808000FF,808080FF&amp;";
    $complaints_per_day_link .= "chxt=x,y&amp;";
    $complaints_per_day_link .= "chxl=0:|".$x_axis."|1:|".$y_axis."&amp;";
    $complaints_per_day_link .= "chd=s:".self::simpleEncode($complaints_received, 0, $max).",".self::simpleEncode($complaints_processed, 0, $max).",".self::simpleEncode($complaints_resolved, 0, $max).",".self::simpleEncode($delayed_resolution, 0, $max);
    */

    if($params->get('show_chart', '1') == '1') {
        echo '<h3>Complaint Statistics</h3>';
        //echo '<img src="' . $complaints_per_day_link . '" alt="complaints statistics :: drawing failed, select shorter period" />';
        $document = JFactory::getDocument();
        //$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
        JHtml::_('jquery.framework');
        $document->addScript('//code.highcharts.com/highcharts.js');

        $complaints_js = <<< EOT
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
            text: 'Complaint Statistics',
            x: -20 //center
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
            x: -10,
            y: 100,
            borderWidth: 0
        },
        series: [{
            name: 'Received',
            data: $y_complaints_received
        }, {
            name: 'Processed',
            data: $y_complaints_processed
        }, {
            name: 'Resolved',
            data: $y_complaints_resolved
        }, {
            name: 'Delayed',
            data: $y_complaints_delayed
        }]
    });
});
EOT;

        $document->addScriptDeclaration($complaints_js);
        echo '<div id="chart_container" style="width:900px;height:500px;"></div>';
    }
    # -- End Complaint Statistics --

    # -- Complaint Map --
    if($params->get('show_map', '1') == '1') {
        echo '<h3>Complaint Map</h3>';
        $document->addStyleDeclaration("div#map img, div#map svg {max-width:none !important}");
        $document->addScript('//maps.googleapis.com/maps/api/js?key='.$map_api_key.'&sensor=false');
        $db->setQuery("select * from #__complaints where location != '' and date_received >= DATE_ADD('$enddate', interval -$statistics_period day)");
        $complaints = $db->loadObjectList();
        ?>
        <div id="map" style="width:900px;height:500px;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new google.maps.Map(
                document.getElementById("map"), {
                    center: new google.maps.LatLng(<?php echo $center_map; ?>),
                    zoom: <?php echo $zoom_level; ?>,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: true
                }
            );

            <?php
            foreach($complaints as $cid => $complaint) {
                echo 'var point'.$cid.' = new google.maps.LatLng('.$complaint->location.');';
                echo 'var marker'.$cid.' = new google.maps.Marker({position: point'.$cid.', map: map});';
                if($user->getParam('role', 'Guest') == 'System Administrator' or $user->getParam('role', 'Guest') == 'Level 1' or $user->getParam('role', 'Guest') == 'Supervisor') {
                    echo 'var contentString = \'<b>#'.$complaint->message_id.'</b><br/><i>Status:</i> ' . ($complaint->confirmed_closed == 'Y' ? 'Resolved' : 'Open') . '<p>'.addslashes($complaint->processed_message).'</p>\';';
                    echo 'var infowindow'.$cid.' = new google.maps.InfoWindow({content: contentString});';
                    echo 'google.maps.event.addListener(marker'.$cid.', \'click\', function() {infowindow'.$cid.'.open(map,marker'.$cid.');});';
                }
            }
            ?>
        //]]>
        </script>
    <?php
    }
    # -- End Complaint Map --

    if($params->get('show_summary_table', '1') == '1') {
    ?>

    <h3>Summary Table</h3>
    <div style="width:900px;">
        <table style="border:1px solid;" cellpadding="5">
            <tr><td style="border-right:1px solid;"></td><td style="border-right:1px solid;"></td><th colspan="7" style="border-bottom:1px solid;">Age of Grievances and Complaints</th></tr>
            <tr style="border-bottom:1px solid;"><td style="border-right:1px solid;"></td><th align="center" style="border-right:1px solid;">Total</th><th><= 7 days</th><th><= 14 days</th><th><= 21 days</th><th><= 28 days</th><th><= 56 days</th><th><= 84 days</th><th>>= 85 days</th></tr>
            <tr>
                <th align="left" style="border-right:1px solid;">Number</th>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints"); $total_count = $db->loadResult(); echo $total_count; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where date_received >= date_add(now(), interval -7 day)"); $total_count7 = $db->loadResult(); echo $total_count7; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where date_received >= date_add(now(), interval -14 day) and date_received < date_add(now(), interval -7 day)"); $total_count14 = $db->loadResult(); echo $total_count14; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where date_received >= date_add(now(), interval -21 day) and date_received < date_add(now(), interval -14 day)"); $total_count21 = $db->loadResult(); echo $total_count21; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where date_received >= date_add(now(), interval -28 day) and date_received < date_add(now(), interval -21 day)"); $total_count28 = $db->loadResult(); echo $total_count28; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where date_received >= date_add(now(), interval -56 day) and date_received < date_add(now(), interval -28 day)"); $total_count56 = $db->loadResult(); echo $total_count56; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where date_received >= date_add(now(), interval -84 day) and date_received < date_add(now(), interval -56 day)"); $total_count84 = $db->loadResult(); echo $total_count84; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where date_received < date_add(now(), interval -85 day)"); echo $db->loadResult(); ?></td>
            </tr>
            <tr>
                <th align="left" style="border-right:1px solid;">%</th>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center"><?php echo @round($total_count7/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @(100-round($total_count84/$total_count*100, 1)-round($total_count56/$total_count*100, 1)-round($total_count28/$total_count*100, 1)-round($total_count21/$total_count*100, 1)-round($total_count14/$total_count*100, 1)-round($total_count7/$total_count*100, 1)); ?>%</td>
            </tr>
        </table>
        <br />
        <br />
        <table style="border:1px solid" cellpadding="5">
            <tr><td style="border-right:1px solid;"></td><td style="border-right:1px solid;"></td><th rowspan="2" width="30" style="border-right:1px solid;">Within Service Standard</th><th colspan="7" style="border-bottom:1px solid;border-right:1px solid;">Time to Resolve Grievances and Complaints</th><td style="border-right:1px solid;"></td><td></td></tr>
            <tr style="border-bottom:1px solid;"><td style="border-right:1px solid;"></td><th align="center" style="border-right:1px solid;">Total</th><th><= 7 days</th><th><= 14 days</th><th><= 21 days</th><th><= 28 days</th><th><= 56 days</th><th><= 84 days</th><th style="border-right:1px solid;">>= 85 days</th><th style="border-right:1px solid;">Unresolved</th><th>Resolved</th></tr>
            <tr>
                <th align="left" style="border-right:1px solid;">Number</th>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints"); $total_count = $db->loadResult(); echo $total_count; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $total_res_within_standards; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7 = $db->loadResult(); echo $total_count7; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14 = $db->loadResult(); echo $total_count14; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21 = $db->loadResult(); echo $total_count21; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28 = $db->loadResult(); echo $total_count28; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56 = $db->loadResult(); echo $total_count56; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84 = $db->loadResult(); echo $total_count84; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85 = $db->loadResult(); echo $total_count85; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count - $unresolved); ?></td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <th align="left" style="border-right:1px solid;">%</th>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo str_replace(' ', '', $res_within_standards); ?></td>
                <td align="center"><?php echo @round($total_count7/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85/$total_count*100, 1)-round($total_count84/$total_count*100, 1)-round($total_count56/$total_count*100, 1)-round($total_count28/$total_count*100, 1)-round($total_count21/$total_count*100, 1)-round($total_count14/$total_count*100, 1)-round($total_count7/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">High Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where message_priority = 'High'"); $total_count_high = $db->loadResult(); echo $total_count_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_high = $db->loadResult(); echo $total_count7_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_high = $db->loadResult(); echo $total_count14_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_high = $db->loadResult(); echo $total_count21_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_high = $db->loadResult(); echo $total_count28_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_high = $db->loadResult(); echo $total_count56_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_high = $db->loadResult(); echo $total_count84_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'High' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_high = $db->loadResult(); echo $total_count85_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N' and message_priority = 'High'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_high - $unresolved); ?></td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">Medium Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where message_priority = 'Medium'"); $total_count_medium = $db->loadResult(); echo $total_count_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_medium = $db->loadResult(); echo $total_count7_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_medium = $db->loadResult(); echo $total_count14_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_medium = $db->loadResult(); echo $total_count21_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_medium = $db->loadResult(); echo $total_count28_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_medium = $db->loadResult(); echo $total_count56_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_medium = $db->loadResult(); echo $total_count84_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_medium = $db->loadResult(); echo $total_count85_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N' and message_priority = 'Medium'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_medium - $unresolved); ?></td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <td align="left" style="border-right:1px solid;">Low Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where message_priority = 'Low' or message_priority is null or message_priority = ''"); $total_count_low = $db->loadResult(); echo $total_count_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_low = $db->loadResult(); echo $total_count7_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_low = $db->loadResult(); echo $total_count14_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_low = $db->loadResult(); echo $total_count21_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_low = $db->loadResult(); echo $total_count28_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_low = $db->loadResult(); echo $total_count56_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_low = $db->loadResult(); echo $total_count84_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_low = $db->loadResult(); echo $total_count85_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N' and (message_priority = 'Low' or message_priority is null or message_priority = '')"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_low - $unresolved); ?></td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">High Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_high/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_high/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_high/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_high/$total_count*100, 1)-round($total_count84_high/$total_count*100, 1)-round($total_count56_high/$total_count*100, 1)-round($total_count28_high/$total_count*100, 1)-round($total_count21_high/$total_count*100, 1)-round($total_count14_high/$total_count*100, 1)-round($total_count7_high/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">Medium Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_medium/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_medium/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_medium/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_medium/$total_count*100, 1)-round($total_count84_medium/$total_count*100, 1)-round($total_count56_medium/$total_count*100, 1)-round($total_count28_medium/$total_count*100, 1)-round($total_count21_medium/$total_count*100, 1)-round($total_count14_medium/$total_count*100, 1)-round($total_count7_medium/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <td align="left" style="border-right:1px solid;">Low Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_low/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_low/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_low/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_low/$total_count*100, 1)-round($total_count84_low/$total_count*100, 1)-round($total_count56_low/$total_count*100, 1)-round($total_count28_low/$total_count*100, 1)-round($total_count21_low/$total_count*100, 1)-round($total_count14_low/$total_count*100, 1)-round($total_count7_low/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr style="border-bottom:1px solid;"><th align="left" style="border-right:1px solid;" colspan="12">Grievances and Complaints Related to Project Benefits</th></tr>
            <tr>
                <th align="left" style="border-right:1px solid;">Number</th>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1"); $total_count = $db->loadResult(); echo $total_count; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $total_res_within_standards2; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7 = $db->loadResult(); echo $total_count7; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14 = $db->loadResult(); echo $total_count14; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21 = $db->loadResult(); echo $total_count21; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28 = $db->loadResult(); echo $total_count28; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56 = $db->loadResult(); echo $total_count56; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84 = $db->loadResult(); echo $total_count84; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85 = $db->loadResult(); echo $total_count85; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'N'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count - $unresolved); ?></td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <th align="left" style="border-right:1px solid;">%</th>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo str_replace(' ', '', $res_within_standards2); ?></td>
                <td align="center"><?php echo @round($total_count7/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85/$total_count*100, 1)-round($total_count84/$total_count*100, 1)-round($total_count56/$total_count*100, 1)-round($total_count28/$total_count*100, 1)-round($total_count21/$total_count*100, 1)-round($total_count14/$total_count*100, 1)-round($total_count7/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">High Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and message_priority = 'High'"); $total_count_high = $db->loadResult(); echo $total_count_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_high2; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_high = $db->loadResult(); echo $total_count7_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_high = $db->loadResult(); echo $total_count14_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_high = $db->loadResult(); echo $total_count21_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_high = $db->loadResult(); echo $total_count28_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_high = $db->loadResult(); echo $total_count56_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_high = $db->loadResult(); echo $total_count84_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_high = $db->loadResult(); echo $total_count85_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'N' and message_priority = 'High'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_high - $unresolved); ?></td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">Medium Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and message_priority = 'Medium'"); $total_count_medium = $db->loadResult(); echo $total_count_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_medium2; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_medium = $db->loadResult(); echo $total_count7_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_medium = $db->loadResult(); echo $total_count14_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_medium = $db->loadResult(); echo $total_count21_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_medium = $db->loadResult(); echo $total_count28_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_medium = $db->loadResult(); echo $total_count56_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_medium = $db->loadResult(); echo $total_count84_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_medium = $db->loadResult(); echo $total_count85_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'N' and message_priority = 'Medium'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_medium - $unresolved); ?></td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <td align="left" style="border-right:1px solid;">Low Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and (message_priority = 'Low' or message_priority is null or message_priority = '')"); $total_count_low = $db->loadResult(); echo $total_count_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_low2; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_low = $db->loadResult(); echo $total_count7_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_low = $db->loadResult(); echo $total_count14_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_low = $db->loadResult(); echo $total_count21_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_low = $db->loadResult(); echo $total_count28_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_low = $db->loadResult(); echo $total_count56_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_low = $db->loadResult(); echo $total_count84_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_low = $db->loadResult(); echo $total_count85_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and confirmed_closed = 'N' and (message_priority = 'Low' or message_priority is null or message_priority = '')"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_low - $unresolved); ?></td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">High Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_high2/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_high/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_high/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_high/$total_count*100, 1)-round($total_count84_high/$total_count*100, 1)-round($total_count56_high/$total_count*100, 1)-round($total_count28_high/$total_count*100, 1)-round($total_count21_high/$total_count*100, 1)-round($total_count14_high/$total_count*100, 1)-round($total_count7_high/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">Medium Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_medium2/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_medium/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_medium/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_medium/$total_count*100, 1)-round($total_count84_medium/$total_count*100, 1)-round($total_count56_medium/$total_count*100, 1)-round($total_count28_medium/$total_count*100, 1)-round($total_count21_medium/$total_count*100, 1)-round($total_count14_medium/$total_count*100, 1)-round($total_count7_medium/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <td align="left" style="border-right:1px solid;">Low Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_low2/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_low/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_low/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_low/$total_count*100, 1)-round($total_count84_low/$total_count*100, 1)-round($total_count56_low/$total_count*100, 1)-round($total_count28_low/$total_count*100, 1)-round($total_count21_low/$total_count*100, 1)-round($total_count14_low/$total_count*100, 1)-round($total_count7_low/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr style="border-bottom:1px solid;"><th align="left" style="border-right:1px solid;" colspan="12">Grievances and Complaints Related to Project Benefits and Related to Females</th></tr>
            <tr>
                <th align="left" style="border-right:1px solid;">Number</th>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female'"); $total_count = $db->loadResult(); echo $total_count; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $total_res_within_standards3; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7 = $db->loadResult(); echo $total_count7; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14 = $db->loadResult(); echo $total_count14; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21 = $db->loadResult(); echo $total_count21; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28 = $db->loadResult(); echo $total_count28; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56 = $db->loadResult(); echo $total_count56; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84 = $db->loadResult(); echo $total_count84; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85 = $db->loadResult(); echo $total_count85; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'N'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count - $unresolved); ?></td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <th align="left" style="border-right:1px solid;">%</th>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo str_replace(' ', '', $res_within_standards3); ?></td>
                <td align="center"><?php echo @round($total_count7/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85/$total_count*100, 1)-round($total_count84/$total_count*100, 1)-round($total_count56/$total_count*100, 1)-round($total_count28/$total_count*100, 1)-round($total_count21/$total_count*100, 1)-round($total_count14/$total_count*100, 1)-round($total_count7/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">High Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and message_priority = 'High'"); $total_count_high = $db->loadResult(); echo $total_count_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_high3; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_high = $db->loadResult(); echo $total_count7_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_high = $db->loadResult(); echo $total_count14_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_high = $db->loadResult(); echo $total_count21_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_high = $db->loadResult(); echo $total_count28_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_high = $db->loadResult(); echo $total_count56_high; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_high = $db->loadResult(); echo $total_count84_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'High' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_high = $db->loadResult(); echo $total_count85_high; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'N' and message_priority = 'High'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_high - $unresolved); ?></td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">Medium Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and message_priority = 'Medium'"); $total_count_medium = $db->loadResult(); echo $total_count_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_medium3; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_medium = $db->loadResult(); echo $total_count7_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_medium = $db->loadResult(); echo $total_count14_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_medium = $db->loadResult(); echo $total_count21_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_medium = $db->loadResult(); echo $total_count28_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_medium = $db->loadResult(); echo $total_count56_medium; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_medium = $db->loadResult(); echo $total_count84_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Medium' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_medium = $db->loadResult(); echo $total_count85_medium; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'N' and message_priority = 'Medium'"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_medium - $unresolved); ?></td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <td align="left" style="border-right:1px solid;">Low Priority</td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and (message_priority = 'Low' or message_priority is null or message_priority = '')"); $total_count_low = $db->loadResult(); echo $total_count_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php echo $res_within_standards_low3; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 7 day)"); $total_count7_low = $db->loadResult(); echo $total_count7_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 14 day) and date_resolved > date_add(date_received, interval 7 day)"); $total_count14_low = $db->loadResult(); echo $total_count14_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 21 day) and date_resolved > date_add(date_received, interval 14 day)"); $total_count21_low = $db->loadResult(); echo $total_count21_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 28 day) and date_resolved > date_add(date_received, interval 21 day)"); $total_count28_low = $db->loadResult(); echo $total_count28_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 56 day) and date_resolved > date_add(date_received, interval 28 day)"); $total_count56_low = $db->loadResult(); echo $total_count56_low; ?></td>
                <td align="center"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved <= date_add(date_received, interval 84 day) and date_resolved > date_add(date_received, interval 56 day)"); $total_count84_low = $db->loadResult(); echo $total_count84_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'Y' and message_priority = 'Low' and date_resolved > date_add(date_received, interval 85 day)"); $total_count85_low = $db->loadResult(); echo $total_count85_low; ?></td>
                <td align="center" style="border-right:1px solid;"><?php $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and gender = 'Female' and confirmed_closed = 'N' and (message_priority = 'Low' or message_priority is null or message_priority = '')"); $unresolved = $db->loadResult(); echo $unresolved; ?></td>
                <td align="center"><?php echo ($total_count_low - $unresolved); ?></td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">High Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_high3/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_high/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_high/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_high/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_high/$total_count*100, 1)-round($total_count84_high/$total_count*100, 1)-round($total_count56_high/$total_count*100, 1)-round($total_count28_high/$total_count*100, 1)-round($total_count21_high/$total_count*100, 1)-round($total_count14_high/$total_count*100, 1)-round($total_count7_high/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr>
                <td align="left" style="border-right:1px solid;">Medium Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_medium3/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_medium/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_medium/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_medium/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_medium/$total_count*100, 1)-round($total_count84_medium/$total_count*100, 1)-round($total_count56_medium/$total_count*100, 1)-round($total_count28_medium/$total_count*100, 1)-round($total_count21_medium/$total_count*100, 1)-round($total_count14_medium/$total_count*100, 1)-round($total_count7_medium/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>
            <tr style="border-bottom:1px solid;">
                <td align="left" style="border-right:1px solid;">Low Priority</td>
                <td align="center" style="border-right:1px solid;">100%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($res_within_standards_low3/$total_count * 100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count7_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count14_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count21_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count28_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count56_low/$total_count*100, 1); ?>%</td>
                <td align="center"><?php echo @round($total_count84_low/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php echo @round($total_count85_low/$total_count*100, 1); ?>%</td>
                <td align="center" style="border-right:1px solid;"><?php @$unresolved = (100-round($total_count85_low/$total_count*100, 1)-round($total_count84_low/$total_count*100, 1)-round($total_count56_low/$total_count*100, 1)-round($total_count28_low/$total_count*100, 1)-round($total_count21_low/$total_count*100, 1)-round($total_count14_low/$total_count*100, 1)-round($total_count7_low/$total_count*100, 1)); echo $unresolved; ?>%</td>
                <td align="center"><?php echo @(100 - $unresolved); ?>%</td>
            </tr>

        </table>
    </div>

    <div style="margin:15px 0;"><img src="<?php echo JURI::base(true); ?>/components/com_cls/ACP-EU_NDRR-Logo-EN.png" width="100%" height="100%" border="0" alt="The GCLS was developed with the support of ACP-EU" title="The GCLS was developed with the support of ACP-EU" /></div>
    <?php } ?>

<?php } ?>