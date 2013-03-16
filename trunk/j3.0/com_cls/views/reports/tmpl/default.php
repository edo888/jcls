<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

JHTML::_('behavior.calendar');
$user =& JFactory::getUser();
?>
<h3>Statistics Period</h3>
<form action="" method="post" name="adminForm">
<table>
    <tr>
        <td><?php echo JText::_('Start Date'); ?></td>
        <td><?php echo JHTML::_('calendar', $this->startdate, "startdate" , "startdate", '%Y-%m-%d');?></td>
    </tr>
    <tr>
        <td><?php echo JText::_('End Date'); ?></td>
        <td><?php echo JHTML::_('calendar', $this->enddate, "enddate" , "enddate", '%Y-%m-%d');?></td>
    </tr>
</table>
<br />
<input type="submit" name="submit" value="Submit" />
<input type="hidden" name="option" value="com_cls" />
<input type="hidden" name="view" value="reports" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>

<?php if($user->getParam('role', 'Guest') != 'Guest' and $user->getParam('role', 'Guest') != 'Level 2'): ?>
<h3>Complaint Downloads</h3>
<a href="index.php?option=com_cls&amp;task=download_report&period=period">Download Selected Period</a><br />
<a href="index.php?option=com_cls&amp;task=download_report&period=current_month">Download Current Month</a><br />
<a href="index.php?option=com_cls&amp;task=download_report&period=prev_month">Download Previous Month</a><br />
<a href="index.php?option=com_cls&amp;task=download_report&period=all">Download All</a>
<?php endif; ?>

<h3>Summary of Complaint</h3>
<i>Complaints Received Per Day:</i> <?php echo $this->complaints_received_per_day ?> <?php /*<small style="color:#cc0000;"><?php echo $this->complaints_received_growth ?></small>*/ ?><br />
<i>Complaints Processed Per Day:</i> <?php echo $this->complaints_processed_per_day ?> <?php /*<small style="color:#cc0000;"><?php echo $this->complaints_processed_growth ?></small>*/ ?><br />
<i>Complaints Resolved Per Day:</i> <?php echo $this->complaints_resolved_per_day ?> <?php /*<small style="color:#cc0000;"><?php echo $this->complaints_resolved_growth ?></small>*/ ?><br />
<i>Number of Complaints Received:</i> <?php echo $this->complaints_received ?><br />
<i>Number of Complaints Resolved:</i> <?php echo $this->complaints_resolved ?><br />
<i>Number of Complaints Outstanding:</i> <?php echo $this->complaints_outstanding ?><br />
<i>Number of Complaints with Delayed Resolution:</i> <?php echo $this->complaints_delayed ?><br />

<br /><small><i>The averages are based on <?php echo $this->statistics_period ?> days period data.</i></small>

<h3>Complaint Statistics</h3>
<?php /*<img src="<?php echo $this->complaints_per_day_link ?>" alt="complaints statistics :: drawing failed, select shorter period" /> */ ?>
<div id="container" style="width:740px;height:350px;"></div>

<h3>Complaint Map</h3>
<div id="map" style="width:740px;height:350px;"></div>
<script type="text/javascript">
//<![CDATA[
    var map = new GMap2(document.getElementById("map"));
    var myLatlng = new GLatLng(<?php echo $this->center_map; ?>);
    map.setCenter(myLatlng, <?php echo $this->zoom_level; ?>);
    map.addControl(new GMapTypeControl(1));
    map.addControl(new GLargeMapControl());
    map.enableContinuousZoom();
    map.enableScrollWheelZoom();
    map.enableDoubleClickZoom();
    <?php
    foreach($this->complaints as $complaint) {
        echo 'var point = new GLatLng('.$complaint->location.');';
        echo 'var marker = new GMarker(point, {icon: G_DEFAULT_ICON, draggable: false});';
        echo 'map.addOverlay(marker);';
        if($user->getParam('role', 'Guest') == 'System Administrator' or $user->getParam('role', 'Guest') == 'Level 1' or $user->getParam('role', 'Guest') == 'Supervisor')
            echo 'marker.bindInfoWindowHtml(\'<b>#'.$complaint->message_id.'</b><br/><i>Status:</i> ' . ($complaint->confirmed_closed == 'Y' ? 'Resolved' : 'Open') . '<p>'.addslashes($complaint->processed_message).'</p>\');';
    }
    ?>
//]]>
</script>