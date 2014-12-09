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
    ?>
    <script language="javascript" type="text/javascript">
        Joomla.submitbutton = function(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'reports.cancel') {
                submitform(pressbutton);
                return;
            }

            submitform(pressbutton);
        }
    </script>
    <h3>Statistics Period</h3>
    <form action="index.php?option=com_cls" method="post" name="adminForm" id="adminForm">
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
    <input type="button" value="Submit" onclick="Joomla.submitbutton('reports.show')" />
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

    $db->setQuery("select * from #__complaints where confirmed_closed = 'Y' and date_received <= '$enddate 23:59:59'");
    $complaints_received_till_date = $db->loadObjectList();
    
    $res_within_standards = 0;
    foreach($complaints_till_date as $complaint) {
        if($complaint->message_priority == '')
            $complaint->message_priority = 'Low';

        switch($complaint->message_priority) {
            case 'Low': $action_period = $action_period_low; break;
            case 'Medium': $action_period = $action_period_medium; break;
            case 'High': $action_period = $action_period_high; break;
            default: break;
        }
        
        // todo
    }
    
    $db->setQuery("select count(*) from #__complaints where date_received <= '$enddate 23:59:59'");
    $complaints_received_till_date = $db->loadResult();
    
    $db->setQuery("select count(*) from #__complaints where related_to_pb = 1 and date_received <= '$enddate 23:59:59'");
    $all_complaints_related_to_pb = $db->loadResult();
    
    $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'Y' and related_to_pb = 1 and date_received <= '$enddate 23:59:59'");
    $complaints_resolved_related_to_pb = $db->loadResult();
    
    $res_within_standards = ($complaints_received_till_date > 0 ? round($res_within_standards/$complaints_received_till_date * 100) . ' %' : '0 %');
    $rel_pb_addressed = ($all_complaints_related_to_pb > 0 ? round($complaints_resolved_related_to_pb/$all_complaints_related_to_pb * 100) . ' %' : '0 %');
    
    echo '<h3>Summary of Complaints</h3>';
    echo '<i>Complaints Received Per Day:</i> ' . $complaints_received_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_received_growth . '</small><br />';
    echo '<i>Complaints Processed Per Day:</i> ' . $complaints_processed_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_processed_growth . '</small><br />';
    echo '<i>Complaints Resolved Per Day:</i> ' . $complaints_resolved_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_resolved_growth . '</small><br />';
    echo '<i>Number of Complaints Received:</i> ' . $complaints_received . ' <br />';
    echo '<i>Number of Complaints Resolved:</i> ' . $complaints_resolved . ' <br />';
    echo '<i>Number of Complaints Outstanding:</i> ' . ($complaints_received - $complaints_resolved < 0 ? 0 : $complaints_received - $complaints_resolved) . ' <br />';
    echo '<i>Number of Complaints with Delayed Resolution:</i> ' . $complaints_delayed . ' <br />';
    echo '<i>Grievances responded to and/or resolved within the stipulated service standards:</i> ' . $res_within_standards . '<br />';
    echo '<i>Grievances related to delivery of project benefits which are addressed:</i> ' . $rel_pb_addressed . '<br />';

    echo '<br /><small><i>The averages are based on ' . $statistics_period . ' days period data.</i></small>';
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

    echo '<h3>Complaint Statistics</h3>';
    //echo '<img src="' . $complaints_per_day_link . '" alt="complaints statistics :: drawing failed, select shorter period" />';
    $document = JFactory::getDocument();
    //$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
    $document->addScript('http://code.highcharts.com/highcharts.js');

    $complaints_js = <<< EOT
jQuery.noConflict();
var chart;
jQuery(document).ready(function() {
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'container',
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
    echo '<div id="container" style="width:900px;height:500px;"></div>';
    # -- End Complaint Statistics --

    # -- Complaint Map --
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
    # -- End Complaint Map --
    ?>
<?php } ?>