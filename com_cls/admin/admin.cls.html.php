<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

defined('_JEXEC') or die('Restricted access');

class CLSView {
    function showComplaints($rows, $pageNav, $options, $lists) {
        $user = & JFactory::getUser();

        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls', true);
        JSubMenuHelper::addEntry(JText::_('Complaint Categories'), 'index.php?option=com_cls&c=areas');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');
        JSubMenuHelper::addEntry(JText::_('Support Groups'), 'index.php?option=com_cls&c=SupportGroups');
        JSubMenuHelper::addEntry(JText::_('Statistics'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Activity Log'), 'index.php?option=com_cls&c=notifications');

        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');
        $raw_complaint_warning_period = (int) $config->get('raw_complaint_warning_period', 2);

        // set separate warning periods for low, medium, high priorities
        $action_period_low = (int) $config->get('action_period_low', 30);
        $action_period_medium = (int) $config->get('action_period_medium', 10);
        $action_period_high = (int) $config->get('action_period_high', 5);

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                    <?php echo $lists['area']; ?>
                    <?php echo $lists['contract']; ?>
                    <?php echo $lists['source']; ?>
                    <?php echo $lists['priority']; ?>
                    <?php echo $lists['status']; ?>
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="6%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Message ID', 'm.message_id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Source', 'm.message_source', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Sender', 'sender', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Received', 'm.date_received', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="4%" align="center">
                        <?php
                            // echo JHTML::_('grid.sort', 'Area', 'g.area', @$lists['order_Dir'], @$lists['order']);
                            echo JHTML::_('grid.sort', 'Category', 'g.area', @$lists['order_Dir'], @$lists['order']);
                        ?>
                    </th>
                    <th width="4%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Priority', 'm.message_priority', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Processed', 'm.date_processed', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Processed by', 'e.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Resolved', 'm.date_resolved', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="6%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Resolved by', 'u.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=edit&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);

                if($row->date_processed == '' and $raw_complaint_warning_period*24*60*60 < time() - strtotime($row->date_received))
                    JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not processed yet.');
                if($row->confirmed_closed == 'N' and $row->date_processed != '') {
                    switch($row->message_priority) {
                        case 'Low': if($action_period_low*24*60*60 < time() - strtotime($row->date_processed)) JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not resolved yet.'); break;
                        case 'Medium': if($action_period_medium*24*60*60 < time() - strtotime($row->date_processed)) JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not resolved yet.'); break;
                        case 'High': if($action_period_high*24*60*60 < time() - strtotime($row->date_processed)) JError::raiseNotice(0, 'Complaint <a href="'.$link.'">#' . $row->message_id . '</a> is not resolved yet.'); break;
                    }
                }
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Complaint' ); ?>">
                            <?php echo $row->message_id; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->message_source; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->sender; ?>
                    </td>
                    <td align="center">
                        <?php echo date('Y-m-d', strtotime($row->date_received)); ?>
                    </td>
                    <td align="center">
                        <?php echo $row->area; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->message_priority; ?>
                    </td>
                    <td align="center">
                        <?php
                        if($row->date_processed)
                            echo date('Y-m-d', strtotime($row->date_processed));
                        ?>
                    </td>
                    <td align="center">
                        <?php echo $row->editor; ?>
                    </td>
                    <td align="center">
                        <?php
                        if($row->date_resolved)
                            echo date('Y-m-d', strtotime($row->date_resolved));
                        ?>
                    </td>
                    <td align="center">
                        <?php echo $row->resolver; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showReports() {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Complaint Categories'), 'index.php?option=com_cls&c=areas');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');
        JSubMenuHelper::addEntry(JText::_('Support Groups'), 'index.php?option=com_cls&c=SupportGroups');
        JSubMenuHelper::addEntry(JText::_('Statistics'), 'index.php?option=com_cls&c=reports', true);
        JSubMenuHelper::addEntry(JText::_('Activity Log'), 'index.php?option=com_cls&c=notifications');

        JHTML::_('behavior.calendar');

        $db =& JFactory::getDBO();
        $user =& JFactory::getUser();
        $session =& JFactory::getSession();
        $config =& JComponentHelper::getParams('com_cls');
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

        <h3>Statistics Period</h3>
        <form action="index.php?option=com_cls" method="post" name="adminForm">
        <table>
            <tr>
                <td><?php echo JText::_('Start Date'); ?></td>
                <td><input class="inputbox" type="text" name="startdate" id="startdate" size="25" maxlength="25" value="<?php echo $startdate; ?>" /> <input type="reset" class="button" value="..." onclick="return showCalendar('startdate','%Y-%m-%d');" /></td>
            </tr>
            <tr>
                <td><?php echo JText::_('End Date'); ?></td>
                <td><input class="inputbox" type="text" name="enddate" id="enddate" size="25" maxlength="25" value="<?php echo $enddate; ?>" /> <input type="reset" class="button" value="..." onclick="return showCalendar('enddate','%Y-%m-%d');" /></td>
            </tr>
        </table>
        <br />
        <input type="submit" name="submit" value="Submit" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="task" value="showReports" />
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

        echo '<h3>Summary of Complaint</h3>';
        echo '<i>Complaints Received Per Day:</i> ' . $complaints_received_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_received_growth . '</small><br />';
        echo '<i>Complaints Processed Per Day:</i> ' . $complaints_processed_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_processed_growth . '</small><br />';
        echo '<i>Complaints Resolved Per Day:</i> ' . $complaints_resolved_per_day . '<br />'; //' <small style="color:#cc0000;">' . $complaints_resolved_growth . '</small><br />';
        echo '<i>Number of Complaints Received:</i> ' . $complaints_received . ' <br />';
        echo '<i>Number of Complaints Resolved:</i> ' . $complaints_resolved . ' <br />';
        echo '<i>Number of Complaints Outstanding:</i> ' . ($complaints_received - $complaints_resolved < 0 ? 0 : $complaints_received - $complaints_resolved) . ' <br />';
        echo '<i>Number of Complaints with Delayed Resolution:</i> ' . $complaints_delayed . ' <br />';

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
        $document =& JFactory::getDocument();
        $document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js');
        $document->addScript('http://jcls.googlecode.com/svn/trunk/assets/js/highcharts.js');

        $complaints_js = <<< EOT
jQuery.noConflict();
var chart;
jQuery(document).ready(function() {
    chart = new Highcharts.Chart({
        chart: {
            renderTo: 'container',
            type: 'line',
            marginRight: 130,
            marginBottom: 25
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
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);
        $db->setQuery("select * from #__complaints where location != '' and date_received >= DATE_ADD('$enddate', interval -$statistics_period day)");
        $complaints = $db->loadObjectList();
        ?>
        <div id="map" style="width:900px;height:500px;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();

            <?php
            foreach($complaints as $complaint) {
                echo 'var point = new GLatLng('.$complaint->location.');';
                echo 'var marker = new GMarker(point, {icon: G_DEFAULT_ICON, draggable: false});';
                echo 'map.addOverlay(marker);';
                if($user->getParam('role', 'Guest') == 'System Administrator' or $user->getParam('role', 'Guest') == 'Level 1' or $user->getParam('role', 'Guest') == 'Supervisor')
                    echo 'marker.bindInfoWindowHtml(\'<b>#'.$complaint->message_id.'</b><br/><i>Status:</i> ' . ($complaint->confirmed_closed == 'Y' ? 'Resolved' : 'Open') . '<p>'.addslashes($complaint->processed_message).'</p>\');';
            }
            ?>
        //]]>
        </script>
        <?php
        # -- End Complaint Map --
    }

    function showNotifications($rows, $pageNav, $options, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Complaint Categories'), 'index.php?option=com_cls&c=areas');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');
        JSubMenuHelper::addEntry(JText::_('Support Groups'), 'index.php?option=com_cls&c=SupportGroups');
        JSubMenuHelper::addEntry(JText::_('Statistics'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Activity Log'), 'index.php?option=com_cls&c=notifications', true);

        JHTML::_('behavior.tooltip');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                    <?php echo $lists['user_id']; ?>
                    <?php echo $lists['action']; ?>
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JHTML::_('grid.sort', 'User', 'u.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Action', 'm.action', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JHTML::_('grid.sort', 'Date', 'm.date', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="68%" align="left">Description</th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php if($row->user_id == 0) echo 'System'; else echo $row->user; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->action; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->date; ?>
                    </td>
                    <td align="left">
                        <?php echo preg_replace('/#(20[^\s]+)/', '<a href="?option=com_cls&search=$1">#$1</a>', $row->description); ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="notifications" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showContracts($rows, $pageNav, $option, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Complaint Categories'), 'index.php?option=com_cls&c=areas');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts', true);
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');
        JSubMenuHelper::addEntry(JText::_('Support Groups'), 'index.php?option=com_cls&c=SupportGroups');
        JSubMenuHelper::addEntry(JText::_('Statistics'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Activity Log'), 'index.php?option=com_cls&c=notifications');

        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                    <?php echo $lists['section']; ?>
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Name', 'm.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Contract ID', 'm.contract_id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Start Date', 'm.start_date', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'End Date', 'm.end_date', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Section', 's.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=editContract&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Contract' ); ?>">
                            <?php echo $row->name; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->contract_id; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->start_date; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->end_date; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->section_name; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="contracts" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showAreas($rows, $pageNav, $option, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Complaint Categories'), 'index.php?option=com_cls&c=areas', true);
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');
        JSubMenuHelper::addEntry(JText::_('Support Groups'), 'index.php?option=com_cls&c=SupportGroups');
        JSubMenuHelper::addEntry(JText::_('Statistics'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Activity Log'), 'index.php?option=com_cls&c=notifications');

        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Category Name', 'm.area', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="77%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Description', 'm.description', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=editArea&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Complaint Category' ); ?>">
                            <?php echo $row->area; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->description; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="areas" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showSections($rows, $pageNav, $option, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Complaint Categories'), 'index.php?option=com_cls&c=areas');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections', true);
        JSubMenuHelper::addEntry(JText::_('Support Groups'), 'index.php?option=com_cls&c=SupportGroups');
        JSubMenuHelper::addEntry(JText::_('Statistics'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Activity Log'), 'index.php?option=com_cls&c=notifications');

        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Name', 'm.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="77%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Description', 'm.description', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=editSection&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Section' ); ?>">
                            <?php echo $row->name; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->description; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="sections" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    function showSupportGroups($rows, $pageNav, $option, $lists) {
        JSubMenuHelper::addEntry(JText::_('Complaints'), 'index.php?option=com_cls');
        JSubMenuHelper::addEntry(JText::_('Complaint Categories'), 'index.php?option=com_cls&c=areas');
        JSubMenuHelper::addEntry(JText::_('Contracts'), 'index.php?option=com_cls&c=contracts');
        JSubMenuHelper::addEntry(JText::_('Sections'), 'index.php?option=com_cls&c=sections');
        JSubMenuHelper::addEntry(JText::_('Support Groups'), 'index.php?option=com_cls&c=SupportGroups', true);
        JSubMenuHelper::addEntry(JText::_('Statistics'), 'index.php?option=com_cls&c=reports');
        JSubMenuHelper::addEntry(JText::_('Activity Log'), 'index.php?option=com_cls&c=notifications');

        JHTML::_('behavior.tooltip');

        $config =& JComponentHelper::getParams('com_cls');

        jimport('joomla.filter.output');
        ?>
        <form action="index.php?option=com_cls" method="post" name="adminForm">

        <table>
            <tr>
                <td align="left" width="100%">
                    <?php echo JText::_('Filter'); ?>:
                    <input type="text" name="search" id="search" value="<?php echo $lists['search'];?>" class="text_area" onchange="document.adminForm.submit();" />
                    <button onclick="this.form.submit();"><?php echo JText::_('Go'); ?></button>
                    <button onclick="document.getElementById('search').value='';this.form.submit();"><?php echo JText::_('Reset'); ?></button>
                </td>
                <td nowrap="nowrap">
                </td>
            </tr>
        </table>

        <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="1%" align="center">
                        <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" />
                    </th>
                    <th width="20%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Name', 'm.name', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="77%" class="title">
                        <?php echo JHTML::_('grid.sort', 'Description', 'm.description', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                    <th width="1%" nowrap="nowrap">
                        <?php echo JHTML::_('grid.sort', 'ID', 'm.id', @$lists['order_Dir'], @$lists['order']); ?>
                    </th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($rows); $i < $n; $i++) {
                $row = &$rows[$i];
                JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

                $link        = JRoute::_('index.php?option=com_cls&task=editSupportGroup&cid[]='. $row->id);
                $checked     = JHTML::_('grid.checkedout',$row,$i);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $pageNav->getRowOffset( $i ); ?>
                    </td>
                    <td align="center">
                        <?php echo $checked; ?>
                    </td>
                    <td align="center">
                        <a href="<?php echo $link; ?>" title="<?php echo JText::_( 'Edit Support Group' ); ?>">
                            <?php echo $row->name; ?></a>
                    </td>
                    <td align="center">
                        <?php echo $row->description; ?>
                    </td>
                    <td align="center">
                        <?php echo $row->id; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            <tfoot>
                <td colspan="13">
                    <?php echo $pageNav->getListFooter(); ?>
                </td>
            </tfoot>
            </table>
        </div>

        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="c" value="SupportGroups" />
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
        <input type="hidden" name="filter_order_Dir" value="" />
        <?php echo JHTML::_( 'form.token' ); ?>
        </form>
        <?php
    }

    /**
     * Simple encodeing
     * @param $values array of integers
     * @param $min min value to scale
     * @param $max max value to scale
     */
    function simpleEncode($values, $min, $max) {
        $simple_table = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $chardata = '';
        $delta = $max - $min;
        $size = strlen($simple_table) - 1;
        foreach($values as $k => $v)
                if($v >= $min and $v <= $max and $delta != 0)
                        $chardata .= $simple_table[round($size * ($v - $min) / $delta)];
                else
                        $chardata .= '_';
        return $chardata;
    }

    function editComplaint($row, $lists, $user_type) {
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        //add the links to the external files into the head of the webpage (note the 'administrator' in the path, which is not nescessary if you are in the frontend)
        $document =& JFactory::getDocument();
        $document->addScript(JURI::base(true).'/components/com_cls/swfupload/swfupload.js');
        $document->addScript(JURI::base(true).'/components/com_cls/swfupload/swfupload.queue.js');
        $document->addScript(JURI::base(true).'/components/com_cls/swfupload/fileprogress.js');
        $document->addScript(JURI::base(true).'/components/com_cls/swfupload/handlers.js');
        $document->addStyleSheet(JURI::base(true).'/components/com_cls/swfupload/default.css');

        //when we send the files for upload, we have to tell Joomla our session, or we will get logged out
        $session = & JFactory::getSession();

        $swfUploadHeadJs ='
        var swfu;

        window.onload = function() {
            var settings = {
                //this is the path to the flash file, you need to put your components name into it
                flash_url : "'.JURI::base(true).'/components/com_cls/swfupload/swfupload.swf",

                //we can not put any vars into the url for complicated reasons, but we can put them into the post...
                upload_url: "index.php",
                post_params: {
                    "option" : "com_cls",
                    "task" : "upload_picture",
                    "id" : "'.$row->id.'",
                    "'.$session->getName().'" : "'.$session->getId().'",
                    "format" : "raw"
                },
                //you need to put the session and the "format raw" in there, the other ones are what you would normally put in the url
                file_size_limit : "8 MB",
                //client side file chacking is for usability only, you need to check server side for security
                file_types : "*.jpg;*.jpeg;*.gif;*.png",
                file_types_description : "Images only",
                file_upload_limit : 20,
                file_queue_limit : 20,
                custom_settings : {
                    progressTarget : "fsUploadProgress",
                    cancelButtonId : "btnCancel"
                },
                debug: false,

                // Button settings
                button_image_url: "'.JURI::base(true).'/components/com_cls/swfupload/TestImageNoText_65x29.png",
                button_width: "65",
                button_height: "29",
                button_placeholder_id: "spanButtonPlaceHolder",
                button_text: \'<span class="theFont">Select</span>\',
                button_text_style: ".theFont { font-size: 13; }",
                button_text_left_padding: 5,
                button_text_top_padding: 5,

                // The event handler functions are defined in handlers.js
                file_queued_handler : fileQueued,
                file_queue_error_handler : fileQueueError,
                file_dialog_complete_handler : fileDialogComplete,
                upload_start_handler : uploadStart,
                upload_progress_handler : uploadProgress,
                upload_error_handler : uploadError,
                upload_success_handler : uploadSuccess,
                upload_complete_handler : uploadComplete,
                queue_complete_handler : queueComplete     // Queue plugin event
            };
            swfu = new SWFUpload(settings);
        };';

        //add the javascript to the head of the html document
        if(isset($row->id))
            $document->addScriptDeclaration($swfUploadHeadJs);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancel') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.message_source && form.message_source.value == "")
                alert('Message Source is required');
            else if(form.name.value == "" && form.email.value == "" && form.phone.value == "" && form.address.value == "" && form.ip_address.value == "")
                alert('Sender is required');
            else if(form.raw_message && form.raw_message.value == "")
                alert('Raw message is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <?php if(property_exists($row, 'message_id')): ?>
            <tr>
                <td width="200" class="key">
                    <label for="title">
                        <?php echo JText::_( 'Message ID' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->message_id; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Message Source' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or $user_type != 'System Administrator')
                        echo @$row->message_source;
                    else
                        echo $lists['source'];
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->name;
                    else
                        echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @$row->name, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Email' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->email;
                    else
                        echo '<input class="inputbox" type="text" name="email" id="email" size="60" value="', @$row->email, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Phone' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->phone;
                    else
                        echo '<input class="inputbox" type="text" name="phone" id="phone" size="60" value="', @$row->phone, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Address' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->address;
                    else
                        echo '<input class="inputbox" type="text" name="address" id="address" size="60" value="', @$row->address, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Sender IP' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or $user_type != 'System Administrator')
                        echo @$row->ip_address;
                    else
                        echo '<input class="inputbox" type="text" name="ip_address" id="ip_address" size="60" value="', @$row->ip_address, '" />';
                    ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Preferred Contact Method' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->preferred_contact;
                    else
                        echo $lists['preferred_contact'];
                    ?>
                </td>
            </tr>
            <?php if(property_exists($row, 'date_received')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Date Received' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_received; ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Raw Message' ); ?>
                    </label>
                </td>
                <td>
                        <?php
                        if($row->confirmed_closed == 'Y' or $user_type != 'System Administrator')
                            echo '<pre>', @$row->raw_message, '</pre>';
                        else
                            echo '<textarea name="raw_message" id="raw_message" cols="80" rows="5">', @$row->raw_message, '</textarea>';
                        ?>
                </td>
            </tr>
            <?php if(isset($row->date_processed)): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Date Processed' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_processed; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'processed_message')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Processed Message' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo '<pre>', @$row->processed_message, '</pre>';
                    else
                        echo '<textarea name="processed_message" id="processed_message" cols="80" rows="5">', @$row->processed_message, '</textarea>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'support_group_id')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Assign to Support Group' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->support_group;
                    else
                        echo $lists['support_group'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'contract_id')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Contract' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->contract;
                    else
                        echo $lists['contract'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'location')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Location where Issue was Identified' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo '<a href="index.php?option=com_cls&c=view_location&cid=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">View Map</a>';
                    else
                        echo '<input type="hidden" name="location" id="location" value="', @$row->location, '" /><a href="index.php?option=com_cls&c=edit_location&cid=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">'.( empty($row->location) ? 'Add Location' : 'Edit Location' ).'</a>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'editor_id') and $row->date_processed != ''): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Processed by' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or $user_type != 'System Administrator')
                        echo @$row->editor;
                    else
                        echo $lists['editor'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'complaint_area_id')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php
                        // echo JText::_( 'Complaint Area' );
                        echo JText::_( 'Complaint Category' );
                        ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->complaint_area;
                    else
                        echo $lists['area'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'message_priority')): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Complaint Priority' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->message_priority;
                    else
                        echo $lists['priority'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(isset($row->date_resolved)): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Date Resolved' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo @$row->date_resolved; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'confirmed_closed') and $row->date_processed != ''): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Resolved and Closed' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1')) {
                        if($row->confirmed_closed == 'Y') {
                            $difference = strtotime($row->date_closed) - strtotime($row->date_received);

                            $days = floor($difference / 86400);
                            $difference = $difference - ($days * 86400);

                            $hours = floor($difference / 3600);
                            $difference = $difference - ($hours * 3600);

                            $minutes = floor($difference / 60);
                            $difference = $difference - ($minutes * 60);

                            $seconds = $difference;

                            if($days > 0)
                                $time_to_resolve = sprintf("%d %s %01d:%02d:%02d", $days, ($days == 1) ? "day" :  "days", $hours, $minutes, $seconds);
                            else
                                $time_to_resolve = sprintf("%01d:%02d:%02d", $hours, $minutes, $seconds);

                            echo 'On ', @$row->date_closed, ' and took <b>', $time_to_resolve, '</b>';
                        }
                        else
                            echo @$row->confirmed_closed;
                    }
                    else
                        echo $lists['confirmed'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'resolution') and $row->date_processed != ''): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="custom_script">
                        <?php echo JText::_( 'Resolution' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or ($user_type != 'System Administrator' and $user_type != 'Level 1'))
                        echo @$row->resolution;
                    else
                        echo '<textarea name="resolution" id="resolution" cols="80" rows="3">', @$row->resolution, '</textarea>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'resolver_id') and $row->date_processed != ''): ?>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Resolved by' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                    if($row->confirmed_closed == 'Y' or $user_type != 'System Administrator')
                        echo @$row->resolver;
                    else
                        echo $lists['resolver'];
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if(property_exists($row, 'comments')): ?>
            <tr>
                <td class="key" valign="top">
                    <label for="custom_script">
                        <?php echo JText::_( 'Comments' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        if(isset($row->comments))
                            echo '<pre>', $row->comments, '</pre>';
                        if($row->confirmed_closed != 'Y' and $user_type != 'Guest') {
                            echo JText::_('Add your comment here'), ':<br />';
                            echo '<textarea name="comments" id="comments" cols="80" rows="3"></textarea>';
                        }
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>

        <?php if(isset($row->id) and count($row->pictures)): ?>
        <fieldset class="adminform">
            <legend><?php echo JText::_('Pictures'); ?></legend>
            <?php
            foreach($row->pictures as $i => $picture)
                echo '<a class="modal" href="'.JURI::base(true).'/'.$picture->path.'"><img src="'.JURI::base(true).'/'.$picture->path.'" border="0" alt="Picture #'.$i.'" style="max-height:150px;max-width:150px" /></a> ';
            ?>
        </fieldset>
        <div class="clr"></div>
        <?php endif; ?>

        <?php if(isset($row->id)): ?>
        <?php if($row->confirmed_closed != 'Y' and $user_type != 'Guest' and $user_type != 'Supervisor'): ?>
        <form id="form1" action="index.php" method="post" enctype="multipart/form-data">
        <fieldset class="adminform">
            <legend>Upload Picture</legend>
            <div class="fieldset flash" id="fsUploadProgress"><span class="legend">Upload Queue</span></div>
            <div id="divStatus">0 Files Uploaded</div>
                <div>
                    <span id="spanButtonPlaceHolder"></span>
                    <input id="btnCancel" type="button" value="Cancel All Uploads" onclick="swfu.cancelQueue();" disabled="disabled" style="margin-left:2px;font-size:8pt;height:29px;" />
                </div>
        </fieldset>
        </form>
        <?php endif; ?>
        <?php endif; ?>

        <?php if(isset($row->id)): ?>
        <form action="index.php" method="post" name="notificationForm">
        <fieldset class="adminform">
            <legend><?php echo JText::_('Notifications'); ?></legend>

            <p><i>Save your changes before sending a notification.</i></p>

            <table class="admintable">
            <?php if($row->phone != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send acknowledgment notification by SMS' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =& JFactory::getDBO();
                    $db->setQuery("select status from #__complaint_message_queue where complaint_id = $row->id and msg_type = 'Acknowledgment'");
                    $status = $db->loadResult();
                    if($status == '')
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_acknowledge\';document.notificationForm.submit();">Click here</a>';
                    elseif($status == 'Pending' or $status == 'Outgoing')
                        echo JText::_('Message is in the queue');
                    elseif($status == 'Sent')
                        echo JText::_('Message is sent');
                    elseif($status == 'Failed')
                        echo 'Failed to send. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_acknowledge\';document.notificationForm.submit();">Click here</a> to try again.';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->email != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send acknowledgment notification by Email' ); ?>
                    </label>
                </td>
                <td>
                    <a href="javascript:void(0);" onclick="document.notificationForm.task.value='notify_email_acknowledge';document.notificationForm.submit();">Click here</a>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->phone != '' and $row->date_resolved != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send resolution notification by SMS' ); ?>
                    </label>
                </td>
                <td>
                <?php
                    $db =& JFactory::getDBO();
                    $db->setQuery("select status from #__complaint_message_queue where complaint_id = $row->id and msg_type = 'Resolved'");
                    $status = $db->loadResult();
                    if($status == '')
                        echo '<a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_resolve\';document.notificationForm.submit();">Click here</a>';
                    elseif($status == 'Pending' or $status == 'Outgoing')
                        echo JText::_('Message is in the queue');
                    elseif($status == 'Sent')
                        echo JText::_('Message is sent');
                    elseif($status == 'Failed')
                        echo 'Failed to send. <a href="javascript:void(0);" onclick="document.notificationForm.task.value=\'notify_sms_resolve\';document.notificationForm.submit();">Click here</a> to try again.';
                ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if($row->email != '' and $row->date_resolved != ''): ?>
            <tr>
                <td class="key" style="width:300px;">
                    <label for="title">
                        <?php echo JText::_( 'Send resolution notification by Email' ); ?>
                    </label>
                </td>
                <td>
                    <a href="javascript:void(0);" onclick="document.notificationForm.task.value='notify_email_resolve';document.notificationForm.submit();">Click here</a>
                </td>
            </tr>
            <?php endif; ?>
            </table><br />

            <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Type'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Msg From'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Msg To'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Date'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Status'); ?>
                    </th>
                    <th width="49%" align="left">Msg</th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($row->notifications_queue); $i < $n; $i++) {
                $row_i = &$row->notifications_queue[$i];
                JFilterOutput::objectHTMLSafe($row_i, ENT_QUOTES);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $i+1; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->msg_type; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->msg_from; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->msg_to; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->date_created; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->status; ?>
                    </td>
                    <td align="left">
                        <?php echo $row_i->msg; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            </table>
            </div>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        </form>
        <?php endif; ?>

        <?php if(isset($row->id)): ?>
        <?php if($user_type != 'Guest' and $user_type != 'Supervisor' and $user_type != 'Level 2'): ?>
        <fieldset class="adminform">
            <legend><?php echo JText::_('Activity Log'); ?></legend>
            <div id="tablecell">
            <table class="adminlist">
            <thead>
                <tr>
                    <th width="1%">
                        <?php echo JText::_('NUM'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('User'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Action'); ?>
                    </th>
                    <th width="10%" align="center">
                        <?php echo JText::_('Date'); ?>
                    </th>
                    <th width="68%" align="left">Description</th>
                </tr>
            </thead>
            <?php
            $k = 0;
            for($i=0, $n=count($row->activity_log); $i < $n; $i++) {
                $row_i = &$row->activity_log[$i];
                JFilterOutput::objectHTMLSafe($row_i, ENT_QUOTES);
                ?>
                <tr class="<?php echo "row$k"; ?>">
                    <td>
                        <?php echo $i+1; ?>
                    </td>
                    <td align="center">
                        <?php if($row_i->user_id == 0) echo 'System'; else echo $row_i->user; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->action; ?>
                    </td>
                    <td align="center">
                        <?php echo $row_i->date; ?>
                    </td>
                    <td align="left">
                        <?php echo $row_i->description; ?>
                    </td>
                </tr>
                <?php
                $k = 1 - $k;
            }
            ?>
            </table><br />
            <a href="index.php?option=com_cls&c=notifications&search=<?php echo $row->message_id ?>">View full log</a>
        </div>
        </fieldset>

        <div class="clr"></div>
        <?php endif; ?>
        <?php endif; ?>
    <?php
    }

    function editArea($row, $lists, $user_type) {
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancelArea') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.area && form.area.value == "")
                alert('Category name is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Category Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="area" id="area" size="60" value="', @JRequest::getVar('area', $row->area), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Description' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @JRequest::getVar('description', $row->description), '</textarea>'; ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="c" value="areas" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>
    <?php
    }

    function editContract($row, $lists, $user_type) {
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancelContract') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.name && form.name.value == "")
                alert('Name is required');
            else if(form.section_id && form.section_id.value == "")
                alert('Section is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @JRequest::getVar('name', $row->name), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Contract ID' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="contract_id" id="contract_id" size="60" value="', @JRequest::getVar('contract_id', $row->contract_id), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Start Date' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="start_date" id="start_date" size="60" value="', @JRequest::getVar('start_date', $row->start_date), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Anticipated End Date' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="end_date" id="end_date" size="60" value="', @JRequest::getVar('end_date', $row->end_date), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Contractor(s)' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="contractors" id="contractors" cols="80" rows="5">', @JRequest::getVar('contractors', $row->contractors), '</textarea>'; ?>
                </td>
            </tr>
            <tr>
                <td class="key">
                    <label for="path">
                        <?php echo JText::_( 'Section' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo $lists['section']; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Description' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @JRequest::getVar('description', $row->description), '</textarea>'; ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="c" value="contracts" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>
    <?php
    }

    function editSection($row, $lists, $user_type) {
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancelSection') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.name && form.name.value == "")
                alert('Name is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @JRequest::getVar('name', $row->name), '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Description' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @JRequest::getVar('description', $row->description), '</textarea>'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Tag on the map' ); ?>
                    </label>
                </td>
                <td>
                    <?php
                        if($user_type != 'System Administrator' and $user_type != 'Level 1')
                            echo '<a href="index.php?option=com_cls&c=view_section_map&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">View Map</a>';
                        else
                            echo '<input type="hidden" name="polygon" id="polygon" value="', @$row->polygon, '" /><input type="hidden" name="polyline" id="polyline" value="', @$row->polyline, '" /><a href="index.php?option=com_cls&c=edit_section_map&id=' . @$row->id . '" class="modal" rel="{handler:\'iframe\',size:{x:screen.availWidth-250, y:screen.availHeight-250}}">'.( (empty($row->polygon) and empty($row->polyline)) ? 'Add a tag' : 'Edit the tag' ).'</a>';
                    ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="c" value="sections" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>
    <?php
    }

    function editSupportGroup($row, $lists, $user_type) {
        jimport('joomla.filter.output');
        JFilterOutput::objectHTMLSafe($row, ENT_QUOTES);

        JHTML::_('behavior.modal');

        //echo '<pre>', print_r($row, true), '</pre>';
    ?>
        <script language="javascript" type="text/javascript">
        function submitbutton(pressbutton) {
            var form = document.adminForm;
            if(pressbutton == 'cancelSupportGroup') {
                submitform(pressbutton);
                return;
            }

            // validation
            if(form.name && form.name.value == "")
                alert('Name is required');
            else
                submitform(pressbutton);
        }
        </script>
        <form action="index.php" method="post" name="adminForm">

        <fieldset class="adminform">
            <legend><?php echo JText::_('Details'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key">
                    <label for="alias">
                        <?php echo JText::_( 'Name' ); ?>
                    </label>
                </td>
                <td>
                    <?php echo '<input class="inputbox" type="text" name="name" id="name" size="60" value="', @$row->name, '" />'; ?>
                </td>
            </tr>
            <tr>
                <td class="key" valign="top">
                    <label for="path">
                        <?php echo JText::_( 'Description' ); ?>
                    </label>
                </td>
                <td>
                        <?php echo '<textarea name="description" id="description" cols="80" rows="5">', @$row->description, '</textarea>'; ?>
                </td>
            </tr>
            </table>
        </fieldset>

        <fieldset class="adminform">
            <legend><?php echo JText::_('Users'); ?></legend>

            <table class="admintable">
            <tr>
                <td width="200" class="key" valign="top">
                    <?php echo JText::_( 'User Selection' ); ?>
                </td>
                <td>
                    <select multiple="multiple" size="15" class="inputbox" id="users" name="users[]">
                        <?php
                            foreach($row->users as $user) {
                                if($user->group_id)
                                    echo '<option value="'.$user->user_id.'" selected>'.$user->name.'</option>';
                                else
                                    echo '<option value="'.$user->user_id.'">'.$user->name.'</option>';
                            }
                        ?>
                    </select>
                </td>
            </tr>
            </table>
        </fieldset>

        <div class="clr"></div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="c" value="SupportGroups" />
        <input type="hidden" name="option" value="com_cls" />
        <input type="hidden" name="id" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="cid[]" value="<?php echo @$row->id; ?>" />
        <input type="hidden" name="textfieldcheck" value="<?php echo @$n; ?>" />
        </form>
    <?php
    }

    function viewLocation() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);

        $db =& JFactory::getDBO();
        $db->setQuery('select polyline, polygon, location from #__complaint_sections as s right join #__complaint_contracts as c on (c.section_id = s.id) right join #__complaints as m on (m.contract_id = c.id) where m.id = ' . JRequest::getInt('cid', 0));
        $row = $db->loadObject();
        $polyline = empty($row->polyline) ? array() : explode(';', $row->polyline);
        $polygon  = empty($row->polygon)  ? array() : explode(';', $row->polygon);
        $location = $row->location;
        ?>
        <div id="map" style="width:100%;height:100%;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            <?php if($location != ''): ?>
            var myLatlng = new GLatLng(<?php echo $location; ?>);
            <?php else: ?>
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            <?php endif; ?>
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();
            <?php if(count($polyline)): ?>
            var polyline = new GPolyline([
                <?php
                foreach($polyline as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], "#885555", 5);
            map.addOverlay(polyline);
            <?php endif; ?>
            <?php if(count($polygon)): ?>
            var polygon = new GPolygon([
                <?php
                foreach($polygon as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], "#f33f00", 5, 1, "#ff0000", 0.2);
            map.addOverlay(polygon);
            <?php endif; ?>
            <?php if($location != ''): ?>
            var point = new GLatLng(<?php echo $location; ?>);
            var markerD2 = new GMarker(point, {icon:G_DEFAULT_ICON, draggable: false});
            map.addOverlay(markerD2);
            <?php endif; ?>
        //]]>
        </script>
        <?php
    }

    function editLocation() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);

        $db =& JFactory::getDBO();
        $db->setQuery('select polyline, polygon, location from #__complaint_sections as s right join #__complaint_contracts as c on (c.section_id = s.id) right join #__complaints as m on (m.contract_id = c.id) where m.id = ' . JRequest::getInt('cid', 0));
        $row = $db->loadObject();
        $polyline = empty($row->polyline) ? array() : explode(';', $row->polyline);
        $polygon  = empty($row->polygon)  ? array() : explode(';', $row->polygon);
        $location = $row->location;
        ?>
        <div id="map" style="width:100%;height:100%;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            <?php if($location != ''): ?>
            var myLatlng = new GLatLng(<?php echo $location; ?>);
            <?php else: ?>
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            <?php endif; ?>
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();
            <?php if(count($polyline)): ?>
            var polyline = new GPolyline([
                <?php
                foreach($polyline as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], "#885555", 5);
            map.addOverlay(polyline);
            <?php endif; ?>
            <?php if(count($polygon)): ?>
            var polygon = new GPolygon([
                <?php
                foreach($polygon as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], "#f33f00", 5, 1, "#ff0000", 0.2);
            map.addOverlay(polygon);
            <?php endif; ?>
            <?php if($location != ''): ?>
            var point = new GLatLng(<?php echo $location; ?>);
            <?php else: ?>
            var point = new GLatLng(<?php echo $center_map; ?>);
            <?php endif; ?>
            var markerD2 = new GMarker(point, {icon:G_DEFAULT_ICON, draggable: true});
            map.addOverlay(markerD2);
            markerD2.enableDragging();
            GEvent.addListener(markerD2, "drag", function(){
                window.parent.document.getElementById("location").value = markerD2.getPoint().toUrlValue();
            });
        //]]>
        </script>
        <?php
    }

    function viewSectionMap() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);

        $db =& JFactory::getDBO();
        $db->setQuery('select polyline, polygon from #__complaint_sections where id = ' . JRequest::getInt('id', 0));
        $row = $db->loadObject();
        $polyline = empty($row->polyline) ? array() : explode(';', $row->polyline);
        $polygon  = empty($row->polygon)  ? array() : explode(';', $row->polygon);
        ?>
        <div id="map" style="width:100%;height:100%;"></div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();
            <?php if(count($polyline)): ?>
            var polyline = new GPolyline([
                <?php
                foreach($polyline as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], "#885555", 5);
            map.addOverlay(polyline);
            <?php endif; ?>
            <?php if(count($polygon)): ?>
            var polygon = new GPolygon([
                <?php
                foreach($polygon as $point)
                    $points[] = 'new GLatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], "#f33f00", 5, 1, "#ff0000", 0.2);
            map.addOverlay(polygon);
            <?php endif; ?>
        //]]>
        </script>
        <?php
    }

    function editSectionMap() {
        JRequest::setVar('tmpl', 'component'); //force the component template
        $document =& JFactory::getDocument();
        $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');

        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);
        ?>
        <div style="width:100%;height:100%;">
            <div id="controls" style="height:5%;">Mode: <input type="radio" id="drawPolyline" name="type" style="vertical-align:bottom;" checked /> Polyline <input type="radio" id="drawPolygon" name="type" style="vertical-align:bottom;" /> Polygon <input type="button" onclick="editline()" value="Edit Poly Shape" /> <input type="button" onclick="finishedit()" value="Done Editing" /> <input type="button" onclick="closepolyshape()" value="Close Polyshape" /> <input type="button" onclick="removelastpoint()" value="Remove last point" /></div>
            <div id="map" style="width:100%;height:95%;"></div>
        </div>
        <script type="text/javascript">
        //<![CDATA[
            var map = new GMap2(document.getElementById("map"));
            var myLatlng = new GLatLng(<?php echo $center_map; ?>);
            map.setCenter(myLatlng, <?php echo $zoom_level; ?>);
            map.addControl(new GMapTypeControl(1));
            map.addControl(new GLargeMapControl());
            map.enableContinuousZoom();
            map.enableScrollWheelZoom();
            map.enableDoubleClickZoom();
            var mapListener;
            var editListener;
            var dropPolypointListener;
            var polyline = null;
            var polygon = null;
            var polylinepoints = [];
            var polygonpoints = [];

            parentpoints = window.parent.document.getElementById("polyline").value.split(';');
            if(parentpoints.length > 1)
                for(var i = 0; i < parentpoints.length; i++)
                    polylinepoints.push(eval('new GLatLng('+parentpoints[i]+')'));

            parentpoints = window.parent.document.getElementById("polygon").value.split(';');
            if(parentpoints.length > 1)
                for(var i = 0; i < parentpoints.length; i++)
                    polygonpoints.push(eval('new GLatLng('+parentpoints[i]+')'));

            if(polylinepoints.length) {
                polyline = new GPolyline(polylinepoints, "#885555", 5);
                map.addOverlay(polyline);
            }
            if(polygonpoints.length) {
                var polygon = new GPolygon(polygonpoints, "#f33f00", 5, 1, "#ff0000", 0.2);
                map.addOverlay(polygon);
            }
            var mapListener = GEvent.addListener(map, "click", mapClick);

            function mapClick(section, point) {
                if(section == null) {
                    if(document.getElementById('drawPolyline').checked) {
                        if(polyline == null) {
                            polylinepoints.push(point);
                            polyline = new GPolyline(polylinepoints, "#885555", 5);
                            map.addOverlay(polyline);
                        } else {
                            map.removeOverlay(polyline);
                            polylinepoints.push(point);
                            polyline = new GPolyline(polylinepoints, "#885555", 5);
                            map.addOverlay(polyline);
                        }
                    } else if(document.getElementById('drawPolygon').checked) {
                        if(polygon == null) {
                            polygonpoints.push(point);
                            polygon = new GPolygon(polygonpoints, "#f33f00", 5, 1, "#ff0000", 0.2);
                            map.addOverlay(polygon);
                        } else {
                            map.removeOverlay(polygon);
                            polygonpoints.push(point);
                            polygon = new GPolygon(polygonpoints, "#f33f00", 5, 1, "#ff0000", 0.2);
                            map.addOverlay(polygon);
                        }
                    }
                }
                updateParentCoordinates();
            }

            function editline() {
                if(polyline == null && polygon == null)
                    return;

                GEvent.removeListener(mapListener);
                if(document.getElementById('drawPolyline').checked) {
                    if(polyline !== null) {
                        polyline.enableEditing();
                        editListener = GEvent.addListener(polyline, 'lineupdated', updateCoordinates);
                        dropPolypointListener = GEvent.addListener(polyline, 'click', function(latlng, index) {
                            if(typeof index == 'number') {
                                polyline.deleteVertex(index);
                                updateCoordinates();
                            }
                        });
                    }
                } else if(document.getElementById('drawPolygon').checked) {
                    if(polygon !== null) {
                        polygon.enableEditing();
                        editListener = GEvent.addListener(polygon, 'lineupdated', updateCoordinates);
                        dropPolypointListener = GEvent.addListener(polygon, 'click', function(latlng, index) {
                            if(typeof index == 'number') {
                                polygon.deleteVertex(index);
                                updateCoordinates();
                            }
                        });
                    }
                }
            }

            function finishedit() {
                mapListener = GEvent.addListener(map, "click", mapClick);
                GEvent.removeListener(editListener);
                GEvent.removeListener(dropPolypointListener);
                if(polyline !== null)
                    polyline.disableEditing();
                if(polygon !== null)
                    polygon.disableEditing();

                updateParentCoordinates();
            }

            function closepolyshape() {
                if(document.getElementById('drawPolyline').checked) {
                    if(polyline !== null) {
                        map.removeOverlay(polyline);
                        polylinepoints.push(polylinepoints[0]);
                        polyline = new GPolyline(polylinepoints, "#885555", 5);
                        map.addOverlay(polyline);
                    }
                } else if(document.getElementById('drawPolygon').checked) {
                    if(polygon !== null) {
                        map.removeOverlay(polygon);
                        polygonpoints.push(polygonpoints[0]);
                        polygon = new GPolygon(polygonpoints, "#f33f00", 5, 1, "#ff0000", 0.2);
                        map.addOverlay(polygon);
                    }
                }

                updateParentCoordinates();
            }

            function removelastpoint() {
                if(document.getElementById('drawPolyline').checked) {
                    if(polyline !== null) {
                        map.removeOverlay(polyline);
                        polylinepoints.pop();
                        polyline = new GPolyline(polylinepoints, "#885555", 5);
                        map.addOverlay(polyline);
                    }
                } else if(document.getElementById('drawPolygon').checked) {
                    if(polygon !== null) {
                        map.removeOverlay(polygon);
                        polygonpoints.pop();
                        polygon = new GPolygon(polygonpoints, "#f33f00", 5, 1, "#ff0000", 0.2);
                        map.addOverlay(polygon);
                    }
                }

                updateParentCoordinates();
            }

            function updateCoordinates() {
                if(document.getElementById('drawPolyline').checked) {
                    polylinepoints = [];
                    var j = polyline.getVertexCount(); // get the amount of points
                    for(var i = 0; i < j; i++)
                        polylinepoints[i] = polyline.getVertex(i); // update polyPoints array
                } else if(document.getElementById('drawPolygon').checked) {
                    polygonpoints = [];
                    var j = polygon.getVertexCount(); // get the amount of points
                    for(var i = 0; i < j; i++)
                        polygonpoints[i] = polygon.getVertex(i); // update polyPoints array
                }

                updateParentCoordinates();
            }

            function updateParentCoordinates() {
                window.parent.document.getElementById("polyline").value = polylinepoints.toString().replace(/\)\,\(/g, ';').replace(/[\(\)]/g, '');
                window.parent.document.getElementById("polygon").value = polygonpoints.toString().replace(/\)\,\(/g, ';').replace(/[\(\)]/g, '');
            }
        //]]>
        </script>
        <?php
    }
}