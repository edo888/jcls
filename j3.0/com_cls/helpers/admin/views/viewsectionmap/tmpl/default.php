<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

viewSectionMap();

function viewSectionMap() {
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
    $db->setQuery('select polyline, polygon from #__complaint_sections where id = ' . JRequest::getInt('id', 0));
    $row = $db->loadObject();
    $polyline = empty($row->polyline) ? array() : explode(';', $row->polyline);
    $polygon  = empty($row->polygon)  ? array() : explode(';', $row->polygon);
    ?>
        <div id="map" style="width:100%;height:100%;"></div>
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
            map.addOverlay(polygon);
            <?php endif; ?>
        //]]>
        </script>
<?php } ?>