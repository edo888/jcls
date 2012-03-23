<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport( 'joomla.application.component.view');

class CLSViewReports extends JView {
    function display($tpl = null) {

        // authorize
        $user =& JFactory::getUser();
        /*
        if($user->getParam('role', '') == '') {
            global $mainframe;

            $return = JURI::base() . 'index.php?option=com_user&view=login';
            $return .= '&return=' . base64_encode(JURI::base() . 'index.php?' . JURI::getInstance()->getQuery());
            $mainframe->redirect($return);
        }
        */

        CLSView::showToolbar();

        $db =& JFactory::getDBO();
        $session =& JFactory::getSession();
        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');
        $statistics_period = (int) $session->get('statistics_period', $config->get('statistics_period', 20));
        //$statistics_period_compare = (int) $config->get('statistics_period_compare', 5);
        $delayed_resolution_period = (int) $config->get('delayed_resolution_period', 30);

        $startdate = JRequest::getCmd('startdate', $session->get('startdate', date('Y-m-d', strtotime("-$statistics_period days")), 'com_cls'));
        $session->set('startdate', $startdate, 'com_cls');
        $enddate = JRequest::getCmd('enddate', $session->get('enddate', date('Y-m-d'), 'com_cls'));
        $session->set('enddate', $enddate, 'com_cls');

        $this->assignRef('startdate', $startdate);
        $this->assignRef('enddate', $enddate);

        $statistics_period = (int) ((strtotime($enddate) - strtotime($startdate)) / 86400);

        # -- Complaints Averages --
        $db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD(now(), interval -$statistics_period day)");
        $n_complaints_received = $complaints_received = $db->loadResult();
        $complaints_received_per_day = round($complaints_received/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where date_processed >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints_processed = $db->loadResult();
        $complaints_processed_per_day = round($complaints_processed/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where date_resolved >= DATE_ADD(now(), interval -$statistics_period day)");
        $n_complaints_resolved = $complaints_resolved = $db->loadResult();
        $complaints_resolved_per_day = round($complaints_resolved/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N' and date_processed >= DATE_ADD(now(), interval -$statistics_period day) and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= now()");
        $complaints_delayed = $db->loadResult();

        $complaints_outstanding = $complaints_received - $complaints_resolved < 0 ? 0 : $complaints_received - $complaints_resolved;

        $this->assignRef('complaints_received', $n_complaints_received);
        $this->assignRef('complaints_resolved', $n_complaints_resolved);
        $this->assignRef('complaints_outstanding', $complaints_outstanding);
        $this->assignRef('complaints_delayed', $complaints_delayed);

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
        # -- End Complaints Averages --

        # -- Complaints Statistics --
        for($i = 0, $time = strtotime("-$statistics_period days"); $time < time() + 86400; $i++, $time = strtotime("-$statistics_period days +$i days"))
            $dates[date('M j', $time)] = 0;
        //echo '<pre>', print_r($dates, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_received, '%b %e') as date from #__complaints where date_received >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_received");
        $received = $db->loadObjectList();
        $complaints_received = $dates;
        foreach($received as $complaint)
            $complaints_received[$complaint->date] = (int) $complaint->count;
        //echo '<pre>', print_r($complaints_received, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_processed, '%b %e') as date from #__complaints where date_processed >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_processed");
        $processed = $db->loadObjectList();
        $complaints_processed = $dates;
        foreach($processed as $complaint)
            $complaints_processed[$complaint->date] = (int) $complaint->count;
        //echo '<pre>', print_r($complaints_processed, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_resolved, '%b %e') as date from #__complaints where date_resolved >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_resolved");
        $resolved = $db->loadObjectList();
        $complaints_resolved = $dates;
        foreach($resolved as $complaint)
            $complaints_resolved[$complaint->date] = (int) $complaint->count;
        //echo '<pre>', print_r($complaints_resolved, true), '</pre>';

        for($i = 0, $time = strtotime("-$statistics_period days"); $time < time() + 86400; $i++, $time = strtotime("-$statistics_period days +$i days")) {
            $date = date('Y-m-d', $time);
            $key = date('M j', $time);
            $db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD('$date', interval -" . ($delayed_resolution_period + $statistics_period) . " day)and date_received <= '$date' and ((date_resolved is not null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= date_resolved) or (date_resolved is null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= '$date'))");
            $delayed_resolution[$key] = (int) $db->loadResult();
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
        # -- End Complaints Statistics --

        # -- Complaints Map --
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);
        $db->setQuery("select * from #__complaints where location != '' and date_received >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints = $db->loadObjectList();
        # -- End Complaints Map --

        $this->assignRef('complaints_received_per_day', $complaints_received_per_day);
        //$this->assignRef('complaints_received_growth', $complaints_received_growth);
        $this->assignRef('complaints_processed_per_day', $complaints_received_per_day);
        //$this->assignRef('complaints_processed_growth', $complaints_processed_growth);
        $this->assignRef('complaints_resolved_per_day', $complaints_resolved_per_day);
        //$this->assignRef('complaints_resolved_growth', $complaints_resolved_growth);
        $this->assignRef('statistics_period', $statistics_period);
        $this->assignRef('complaints_per_day_link', $complaints_per_day_link);
        $this->assignRef('complaints', $complaints);
        $this->assignRef('center_map', $center_map);
        $this->assignRef('zoom_level', $zoom_level);

        parent::display($tpl);
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
                if($v >= $min and $v <= $max and $delta)
                        $chardata .= $simple_table[round($size * ($v - $min) / $delta)];
                else
                        $chardata .= '_';
        return $chardata;
    }
}