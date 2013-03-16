<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

editLocation();

function editLocation() {
    JRequest::setVar('tmpl', 'component'); //force the component template
    $document = JFactory::getDocument();
    $document->addStyleDeclaration('html, body {margin:0 !important;padding:0 !important;height:100% !important;}');

    $config = JComponentHelper::getParams('com_cls');
    $center_map = $config->get('center_map');
    $map_api_key = $config->get('map_api_key');
    $zoom_level = $config->get('zoom_level');

    $document = JFactory::getDocument();
    $document->addStyleDeclaration("div#map img, div#map svg {max-width:none !important}");
    $document->addScript('http://maps.google.com/maps?file=api&v=2&key='.$map_api_key);

    $db = JFactory::getDBO();
    $db->setQuery('select polyline, polygon, location from #__complaint_sections as s right join #__complaint_contracts as c on (c.section_id = s.id) right join #__complaints as m on (m.contract_id = c.id) where m.id = ' . JRequest::getInt('id', 0));
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

?>