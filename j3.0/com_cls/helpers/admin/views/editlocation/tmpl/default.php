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
    $document->addScript('//maps.googleapis.com/maps/api/js?key='.$map_api_key.'&sensor=false');

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
            <?php if($location != ''): ?>
                var myLatlng = new google.maps.LatLng(<?php echo $location; ?>);
            <?php else: ?>
                var myLatlng = new google.maps.LatLng(<?php echo $center_map; ?>);
            <?php endif; ?>
            var map = new google.maps.Map(
                document.getElementById("map"), {
                    center: myLatlng,
                    zoom: <?php echo $zoom_level; ?>,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: true
                }
            );

            <?php if(count($polyline)): ?>
            var polyline = new google.maps.Polyline({path: [
                <?php
                foreach($polyline as $point)
                    $points[] = 'new google.maps.LatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], strokeColor: "#885555", strokeWeight: 5});
            polyline.setMap(map);
            <?php endif; ?>
            <?php if(count($polygon)): ?>
            var polygon = new google.maps.Polygon({paths: [
                <?php
                foreach($polygon as $point)
                    $points[] = 'new google.maps.LatLng(' . $point . ')';
                echo implode(',', $points);
                unset($points);
                ?>
            ], strokeColor: "#f33f00", strokeWeight: 5, strokeOpacity: 1, fillColor: "#ff0000", fillOpacity: 0.2});
            polygon.setMap(map);
            <?php endif; ?>
            <?php if($location != ''): ?>
            var point = new google.maps.LatLng(<?php echo $location; ?>);
            <?php else: ?>
            var point = new google.maps.LatLng(<?php echo $center_map; ?>);
            <?php endif; ?>
            var markerD2 = new google.maps.Marker({position: point, map: map});
            markerD2.setDraggable(true);
            google.maps.event.addListener(markerD2, "drag", function(){
                window.parent.document.getElementById("location").value = markerD2.getPosition().toUrlValue();
            });
        //]]>
        </script>
        <?php
    }

?>