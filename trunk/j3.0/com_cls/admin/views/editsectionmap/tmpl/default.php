<?php 
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