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
        $db =& JFactory::getDBO();
        $config =& JComponentHelper::getParams('com_cls');
        $center_map = $config->get('center_map');
        $map_api_key = $config->get('map_api_key');
        $zoom_level = $config->get('zoom_level');
        $statistics_period = (int) $config->get('statistics_period', 20);
        $statistics_period_compare = (int) $config->get('statistics_period_compare', 5);
        $delayed_resolution_period = (int) $config->get('delayed_resolution_period', 30);

        # -- Complaints Averages --
        $db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints_received = $db->loadResult();
        $complaints_received_per_day = round($complaints_received/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where date_processed >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints_processed = $db->loadResult();
        $complaints_processed_per_day = round($complaints_processed/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where date_resolved >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints_resolved = $db->loadResult();
        $complaints_resolved_per_day = round($complaints_resolved/$statistics_period, 2);
        $db->setQuery("select count(*) from #__complaints where confirmed_closed = 'N' and date_processed >= DATE_ADD(now(), interval -$statistics_period day) and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= now()");
        $complaints_delayed = $db->loadResult();

        $complaints_outstanding = $complaints_received - $complaints_resolved < 0 ? 0 : $complaints_received - $complaints_resolved;

        $this->assignRef('complaints_received', $complaints_received);
        $this->assignRef('complaints_resolved', $complaints_resolved);
        $this->assignRef('complaints_outstanding', $complaints_outstanding);
        $this->assignRef('complaints_delayed', $complaints_delayed);

        $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_received >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_received <= DATE_ADD(now(), interval -$statistics_period_compare day)");
        $complaints_received_per_day2 = $db->loadResult();
        $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_processed >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_processed <= DATE_ADD(now(), interval -$statistics_period_compare day)");
        $complaints_processed_per_day2 = $db->loadResult();
        $db->setQuery("select round(count(*)/$statistics_period, 2) from #__complaints where date_resolved >= DATE_ADD(now(), interval -" . ($statistics_period+$statistics_period_compare) . " day) and date_resolved <= DATE_ADD(now(), interval -$statistics_period_compare day)");
        $complaints_resolved_per_day2 = $db->loadResult();

        $complaints_received_growth = ($complaints_received_per_day >= $complaints_received_per_day2 ? '+' : '-') . round(abs($complaints_received_per_day - $complaints_received_per_day2)/$complaints_received_per_day*100, 2) . '%';
        $complaints_processed_growth = ($complaints_processed_per_day >= $complaints_processed_per_day2 ? '+' : '-') . round(abs($complaints_processed_per_day - $complaints_processed_per_day2)/$complaints_processed_per_day*100, 2) . '%';
        $complaints_resolved_growth = ($complaints_resolved_per_day >= $complaints_resolved_per_day2 ? '+' : '-') . round(abs($complaints_resolved_per_day - $complaints_resolved_per_day2)/$complaints_resolved_per_day*100, 2) . '%';
        # -- End Complaints Averages --

        # -- Complaints Statistics --
        for($i = 0, $time = strtotime("-$statistics_period days"); $time < time() + 86400; $i++, $time = strtotime("-$statistics_period days +$i days"))
            $dates[date('M j', $time)] = 0;
        //echo '<pre>', print_r($dates, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_received, '%b %e') as date from #__complaints where date_received >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_received");
        $received = $db->loadObjectList();
        $complaints_received = $dates;
        foreach($received as $complaint)
            $complaints_received[$complaint->date] = $complaint->count;
        //echo '<pre>', print_r($complaints_received, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_processed, '%b %e') as date from #__complaints where date_processed >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_processed");
        $processed = $db->loadObjectList();
        $complaints_processed = $dates;
        foreach($processed as $complaint)
            $complaints_processed[$complaint->date] = $complaint->count;
        //echo '<pre>', print_r($complaints_processed, true), '</pre>';

        $db->setQuery("select count(*) as count, date_format(date_resolved, '%b %e') as date from #__complaints where date_resolved >= DATE_ADD(now(), interval -$statistics_period day) group by date order by date_resolved");
        $resolved = $db->loadObjectList();
        $complaints_resolved = $dates;
        foreach($resolved as $complaint)
            $complaints_resolved[$complaint->date] = $complaint->count;
        //echo '<pre>', print_r($complaints_resolved, true), '</pre>';

        for($i = 0, $time = strtotime("-$statistics_period days"); $time < time() + 86400; $i++, $time = strtotime("-$statistics_period days +$i days")) {
            $date = date('Y-m-d', $time);
            $key = date('M j', $time);
            $db->setQuery("select count(*) from #__complaints where date_received >= DATE_ADD('$date', interval -" . ($delayed_resolution_period + $statistics_period) . " day)and date_received <= '$date' and ((date_resolved is not null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= date_resolved) or (date_resolved is null and DATE_ADD(date_processed, interval +$delayed_resolution_period day) <= '$date'))");
            $delayed_resolution[$key] = $db->loadResult();
        }
        //echo '<pre>', print_r($delayed_resolution, true), '</pre>';

        $max = max(max($complaints_received), max($complaints_processed), max($complaints_resolved), max($delayed_resolution));
        $max = ceil($max/5)*5;
        //echo 'Max: ', $max;

        $x_axis  = implode('|', array_keys($dates));
        $y_axis  = implode('|', range(0, $max, $max/5));

        $complaints_per_day_link  = "http://chart.apis.google.com/chart?chs=900x330&amp;";
        $complaints_per_day_link .= "cht=lc&amp;";
        $complaints_per_day_link .= "chdl=Complaints Received|Complaints Processed|Complaints Resolved|Delayed Resolution&amp;";
        $complaints_per_day_link .= "chdlp=b&amp;";
        $complaints_per_day_link .= "chco=000080FF,008000FF,808000FF,808080FF&amp;";
        $complaints_per_day_link .= "chxt=x,y&amp;";
        $complaints_per_day_link .= "chxl=0:|".$x_axis."|1:|".$y_axis."&amp;";
        $complaints_per_day_link .= "chd=s:".self::simpleEncode($complaints_received, 0, $max).",".self::simpleEncode($complaints_processed, 0, $max).",".self::simpleEncode($complaints_resolved, 0, $max).",".self::simpleEncode($delayed_resolution, 0, $max);
        # -- End Complaints Statistics --

        # -- Complaints Map --
        $document =& JFactory::getDocument();
        $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);
        $db->setQuery("select * from #__complaints where location != '' and date_received >= DATE_ADD(now(), interval -$statistics_period day)");
        $complaints = $db->loadObjectList();
        # -- End Complaints Map --

        $this->assignRef('complaints_received_per_day', $complaints_received_per_day);
        $this->assignRef('complaints_received_growth', $complaints_received_growth);
        $this->assignRef('complaints_processed_per_day', $complaints_received_per_day);
        $this->assignRef('complaints_processed_growth', $complaints_processed_growth);
        $this->assignRef('complaints_resolved_per_day', $complaints_resolved_per_day);
        $this->assignRef('complaints_resolved_growth', $complaints_resolved_growth);
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
                if($v >= $min && $v <= $max)
                        $chardata .= $simple_table[round($size * ($v - $min) / $delta)];
                else
                        $chardata .= '_';
        return $chardata;
    }
}