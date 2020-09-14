<?php
            if ($post[$module_input_slug_lang]) {
                $mapr=json_decode(file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?place_id='.$post[$module_input_slug_lang].'&fields=address_components&key='.GOOGLE_MAP_API_KEY_2), true);
                $mapr_val='';
                foreach ($mapr['result']['address_components'] as $kv)
                    $mapr_val.=$kv['long_name'].', ';
                echo '<div class="input-group mt-5">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-map-marked-alt"></span></span>
                    </div>
                    <input disabled type="text" class="form-control border-top-0 border-left-0 border-right-0 rounded-0 m-0" value="'.substr($mapr_val,0,-2).'">
                    <div class="input-group-append">
                        <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><a href="#google_map_div" data-toggle="collapse">Edit map location</a></span>
                    </div>
                    </div>';
            }
            ?>
            <div class="col-12 card p-0 <?php echo ($post[$module_input_slug_lang]?'collapse':''); ?>" id="google_map_div">
            <div class="card-header pl-3"><span class="fas fa-map-marker-alt mr-3"></span>&nbsp;Mark your location on the map</div>
            <div class="card-body">
                <form><div class="input-group">
                <input id="pac-input" class="form-control" type="text"
                    placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>"></div>
            </div>
            <div id="map"></div>
            <div id="infowindow-content">
                <h6 id="place-name"></h6>
                <p id="place-address" class="text-muted"></p>
                <input id="place-input" class="form-control" name="<?php echo $module_input_slug_lang; ?>" type="hidden" value="<?php echo $post[$module_input_slug_lang]; ?>">
            </div>
            </div>

            <script type="text/javascript">
            function initMap() {
                var map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 23.971529, lng: 77.939153},
                zoom: 4
                });
                var card = document.getElementById('pac-card');
                var input = document.getElementById('pac-input');

                var fontawesomePin = {
                path: 'M112 316.94v156.69l22.02 33.02c4.75 7.12 15.22 7.12 19.97 0L176 473.63V316.94c-10.39 1.92-21.06 3.06-32 3.06s-21.61-1.14-32-3.06zM144 0C64.47 0 0 64.47 0 144s64.47 144 144 144 144-64.47 144-144S223.53 0 144 0zm0 76c-37.5 0-68 30.5-68 68 0 6.62-5.38 12-12 12s-12-5.38-12-12c0-50.73 41.28-92 92-92 6.62 0 12 5.38 12 12s-5.38 12-12 12z',
                fillColor: '#ffcc00',
                fillOpacity: 0.8,
                scale: 0.15,
                strokeColor: '#ff3300',
                strokeWeight: 1,
                anchor: new google.maps.Point(144,512)
                };

                map.controls[google.maps.ControlPosition.TOP_RIGHT].push(card);

                var autocomplete = new google.maps.places.Autocomplete(input);

                // Set initial restrict to the greater list of countries.
                autocomplete.setComponentRestrictions({'country': ['in']});

                // Specify only the data fields that are needed.
                autocomplete.setFields(
                    ['address_components', 'place_id', 'geometry', 'icon', 'name']);

                var infowindow = new google.maps.InfoWindow();
                var infowindowContent = document.getElementById('infowindow-content');
                infowindow.setContent(infowindowContent);
                var marker = new google.maps.Marker({
                map: map,
                draggable: true,
                icon: fontawesomePin,
                animation: google.maps.Animation.DROP
                });

                marker.addListener('dragend', function(event) {
                    alert('moved to: '+event.latLng.lat()+' '+event.latLng.lng());
                });

                <?php if ($post[$module_input_slug_lang]) { ?>
                var request = {
                placeId: '<?php echo $post[$module_input_slug_lang]; ?>',
                fields: ['address_components', 'place_id', 'geometry', 'icon', 'name']
                };

                var service = new google.maps.places.PlacesService(map);

                service.getDetails(request, function(place, status) {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    var marker = new google.maps.Marker({
                    map: map,
                    draggable: true,
                    icon: fontawesomePin,
                    animation: google.maps.Animation.DROP,
                    position: place.geometry.location
                    });

                    if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                    } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                    }
                    marker.setPosition(place.geometry.location);
                    marker.setVisible(true);

                    var address = '';
                    if (place.address_components) {
                    var address = parseGoogleResponse(place.address_components);
                    }

                    infowindowContent.children['place-name'].textContent = place.name;
                    infowindowContent.children['place-address'].textContent = address;
                    infowindowContent.children['place-input'].value = place.place_id;
                    infowindow.open(map, marker);
                }
                });
                <?php } ?>

                autocomplete.addListener('place_changed', function() {
                infowindow.close();
                marker.setVisible(false);
                var place = autocomplete.getPlace();
                if (!place.geometry) {
                    window.alert("No details available for input: '" + place.name + "'");
                    return;
                }

                if (place.geometry.viewport) {
                    map.fitBounds(place.geometry.viewport);
                } else {
                    map.setCenter(place.geometry.location);
                    map.setZoom(17);
                }
                marker.setPosition(place.geometry.location);
                marker.setVisible(true);

                var address = '';
                if (place.address_components) {
                    var address = parseGoogleResponse(place.address_components);
                }

                infowindowContent.children['place-name'].textContent = place.name;
                infowindowContent.children['place-address'].textContent = address;
                infowindowContent.children['place-input'].value = place.place_id;
                infowindow.open(map, marker);
                });

            }
            </script>
