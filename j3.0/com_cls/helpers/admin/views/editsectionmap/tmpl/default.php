<?php
/**
* @version   $Id$
* @package   CLS
* @copyright Copyright (C) 2010 Edvard Ananyan. All rights reserved.
* @license   GNU/GPL, see LICENSE.php
*/

// no direct access
defined('_JEXEC') or die('Restircted access');

editSectionMap();

function editSectionMap() {
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
    <div style="width:100%;height:100%;">
        <div id="controls" style="height:5%;">Mode: <input type="radio" id="drawPolyline" name="type" style="vertical-align:bottom;" checked /> Polyline <input type="radio" id="drawPolygon" name="type" style="vertical-align:bottom;" /> Polygon <input type="button" onclick="editline()" value="Edit Poly Shape" /> <input type="button" onclick="finishedit()" value="Done Editing" /> <input type="button" onclick="closepolyshape()" value="Close Polyshape" /> <input type="button" onclick="removelastpoint()" value="Remove last point" /></div>
        <div id="map" style="width:100%;height:95%;"></div>
    </div>
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
                polylinepoints.push(eval('new google.maps.LatLng('+parentpoints[i]+')'));

        parentpoints = window.parent.document.getElementById("polygon").value.split(';');
        if(parentpoints.length > 1)
            for(var i = 0; i < parentpoints.length; i++)
                polygonpoints.push(eval('new google.maps.LatLng('+parentpoints[i]+')'));

        if(polylinepoints.length) {
            polyline = new google.maps.Polyline({path: polylinepoints, strokeColor: "#885555", strokeWeight: 5});
            polyline.setMap(map);
        }
        if(polygonpoints.length) {
            var polygon = new google.maps.Polygon({paths: polygonpoints, strokeColor: "#f33f00", strokeWeight: 5, strokeOpacity: 1, fillColor: "#ff0000", fillOpacity: 0.2});
            polygon.setMap(map);
        }
        var mapListener = google.maps.event.addListener(map, "click", mapClick);

        function mapClick(event) {
            var point = event.latLng;
            //if(section == null) {
                if(document.getElementById('drawPolyline').checked) {
                    if(polyline == null) {
                        polylinepoints.push(point);
                        polyline = new google.maps.Polyline({path: polylinepoints, strokeColor: "#885555", strokeWeight: 5});
                        polyline.setMap(map);
                    } else {
                        // remove overlay
                        polyline.setMap(null);

                        polylinepoints.push(point);
                        polyline = new google.maps.Polyline({path: polylinepoints, strokeColor: "#885555", strokeWeight: 5});
                        polyline.setMap(map);
                    }
                } else if(document.getElementById('drawPolygon').checked) {
                    if(polygon == null) {
                        polygonpoints.push(point);
                        polygon = new google.maps.Polygon({paths: polygonpoints, strokeColor: "#f33f00", strokeWeight: 5, strokeOpacity: 1, fillColor: "#ff0000", fillOpacity: 0.2});
                        polygon.setMap(map);
                    } else {
                        // remove overlay
                        polygon.setMap(null);

                        polygonpoints.push(point);
                        polygon = new google.maps.Polygon({paths: polygonpoints, strokeColor: "#f33f00", strokeWeight: 5, strokeOpacity: 1, fillColor: "#ff0000", fillOpacity: 0.2});
                        polygon.setMap(map);
                    }
                }
            //}
            updateParentCoordinates();
        }

        function editline() {
            if(polyline == null && polygon == null)
                return;

            google.maps.event.removeListener(mapListener);
            if(document.getElementById('drawPolyline').checked) {
                if(polyline !== null) {
                    polyline.setEditable(true);
                }
            } else if(document.getElementById('drawPolygon').checked) {
                if(polygon !== null) {
                    polygon.setEditable(true);
                }
            }
        }

        function finishedit() {
            mapListener = google.maps.event.addListener(map, "click", mapClick);
            if(polyline !== null)
                polyline.setEditable(false);
            if(polygon !== null)
                polygon.setEditable(false);

            updateCoordinates();
        }

        function closepolyshape() {
            if(document.getElementById('drawPolyline').checked) {
                if(polyline !== null) {
                    // remove overlay
                    polyline.setMap(null);

                    polylinepoints.push(polylinepoints[0]);
                    polyline = new google.maps.Polyline({path: polylinepoints, strokeColor: "#885555", strokeWeight: 5});
                    polyline.setMap(map);
                }
            } else if(document.getElementById('drawPolygon').checked) {
                if(polygon !== null) {
                    // remove overlay
                    polygon.setMap(null);

                    polygonpoints.push(polygonpoints[0]);
                    polygon = new google.maps.Polygon({paths: polygonpoints, strokeColor: "#f33f00", strokeWeight: 5, strokeOpacity: 1, fillColor: "#ff0000", fillOpacity: 0.2});
                    polygon.setMap(map);
                }
            }

            updateParentCoordinates();
        }

        function removelastpoint() {
            if(document.getElementById('drawPolyline').checked) {
                if(polyline !== null) {
                    // remove overlay
                    polyline.setMap(null);

                    polylinepoints.pop();
                    polyline = new google.maps.Polyline({path: polylinepoints, strokeColor: "#885555", strokeWeight: 5});
                    polyline.setMap(map);
                }
            } else if(document.getElementById('drawPolygon').checked) {
                if(polygon !== null) {
                    // remove overlay
                    polygon.setMap(null);

                    polygonpoints.pop();
                    polygon = new google.maps.Polygon({paths: polygonpoints, strokeColor: "#f33f00", strokeWeight: 5, strokeOpacity: 1, fillColor: "#ff0000", fillOpacity: 0.2});
                    polygon.setMap(map);
                }
            }

            updateParentCoordinates();
        }

        function updateCoordinates() {
            if(document.getElementById('drawPolyline').checked) {
                polylinepoints = [];
                var j = polyline.getPath().getLength(); // get the amount of points
                for(var i = 0; i < j; i++)
                    polylinepoints[i] = polyline.getPath().getAt(i); // update polyPoints array
            } else if(document.getElementById('drawPolygon').checked) {
                polygonpoints = [];
                var j = polygon.getPath().getLength(); // get the amount of points
                for(var i = 0; i < j; i++)
                    polygonpoints[i] = polygon.getPath().getAt(i); // update polyPoints array
            }

            updateParentCoordinates();
        }

        function updateParentCoordinates() {
            window.parent.document.getElementById("polyline").value = polylinepoints.toString().replace(/\)\,\(/g, ';').replace(/[\(\)]/g, '');
            window.parent.document.getElementById("polygon").value = polygonpoints.toString().replace(/\)\,\(/g, ';').replace(/[\(\)]/g, '');
        }
    //]]>
    </script>
<?php } ?>