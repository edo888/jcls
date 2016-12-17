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
    ?>
        <div id="map" style="width:100%;height:100%;"></div>
        <script type="text/javascript">
        //<![CDATA[
            if(window.parent.document.getElementById("location").value != '') {
                var parent_lat = window.parent.document.getElementById("location").value.split(',')[0];
                var parent_lng = window.parent.document.getElementById("location").value.split(',')[1];

                var point = new google.maps.LatLng(parent_lat, parent_lng);
            } else {
                var point = new google.maps.LatLng(<?php echo $center_map; ?>);
            }

            var map = new google.maps.Map(
                document.getElementById("map"), {
                    center: point,
                    zoom: <?php echo $zoom_level; ?>,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: true
                }
            );

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