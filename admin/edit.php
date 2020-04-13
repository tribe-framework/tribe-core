<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');

if ($_GET['id'])
	$post = $dash::get_content($_GET['id']);

if (($_GET['id'] && $post['type']==$_GET['type']) || !$_GET['id']): ?>

	<link rel="stylesheet" type="text/css" href="/plugins/typeout/typeout.css">

	<div class="container mt-3">
	<a name="infos"></a><div id="infos" class="d-none alert alert-info"></div>
	<a name="errors"></a><div id="errors" class="d-none alert alert-danger"></div>

	<form method="post" class="edit_form" id="edit_form" action="/admin/json">

		<?php echo get_admin_menu('edit', $_GET['type']); ?>

		<h2>Edit <?php echo $types->{$_GET['type']}->name; ?></h2>

		<?php foreach ($types->{$_GET['type']}->modules as $module) { ?>
			
		<?php if ($module->input_type=='text'): ?>
		<div class="input-group input-group-lg my-4"><input type="text" name="<?php echo $module->input_slug; ?>" class="pl-0 border-top-0 border-left-0 border-right-0 rounded-0 form-control" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>" id="<?php echo $module->input_slug; ?>" value="<?php echo ($post[$module->input_slug]?$post[$module->input_slug]:''); ?>"></div>
		<?php endif; ?>

		<?php if ($module->input_type=='textarea'): ?>
		<div class="input-group my-4"><textarea name="<?php echo $module->input_slug; ?>" class="pl-0 border-top-0 border-left-0 border-right-0 rounded-0 form-control" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>" id="<?php echo $module->input_slug; ?>"><?php echo ($post[$module->input_slug]?$post[$module->input_slug]:''); ?></textarea></div>
		<?php endif; ?>

		<?php if ($module->input_type=='typeout'): ?>
		<div class="typeout-menu my-4" id="typeout-menu">
			<?php if (in_array('undo', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-undo" data-typeout-command="undo"><span class="fas fa-undo"></span></button>
			<?php } ?>

			<?php if (in_array('insertParagraph', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-insertParagraph" data-typeout-command="insertParagraph"><span class="fas fa-paragraph"></span></button>
			<?php } ?>

			<?php if (in_array('bold', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-bold" data-typeout-command="bold"><span class="fas fa-bold"></span></button>
			<?php } ?>

			<?php if (in_array('italic', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-italic" data-typeout-command="italic"><span class="fas fa-italic"></span></button>
			<?php } ?>

			<?php if (in_array('createLink', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="createLink" data-typeout-info="Enter link URL"><span class="fas fa-link"></span></button>
			<?php } ?>

			<?php if (in_array('unlink', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-unlink" data-typeout-command="unlink"><span class="fas fa-unlink"></span></button>
			<?php } ?>

			<?php if (in_array('insertImage', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertImage" data-typeout-info="Enter image URL"><span class="fas fa-image"></span></button>
			<?php } ?>

			<?php if (in_array('insertPDF', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertPDF" data-typeout-info="Enter PDF URL"><span class="fas fa-file-pdf"></span></button>
			<?php } ?>

			<?php if (in_array('insertHTML', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertHTML" data-typeout-info="Enter HTML"><span class="fas fa-code"></span></button>
			<?php } ?>

			<?php if (in_array('attach', $module->input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="attach" data-typeout-info=""><span class="fas fa-paperclip"></span></button>
			<?php } ?>
		</div>

		<div class="typeout-content my-4 border-bottom" id="typeout-content" data-input-slug="<?php echo $module->input_slug; ?>" contenteditable="true" style="overflow: auto;" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>"><?php echo $post[$module->input_slug]; ?></div>
		<input type="hidden" name="<?php echo $module->input_slug; ?>">
		<?php endif; ?>

		<?php if ($module->input_type=='date'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-calendar"></span></span>
		  </div>
		  <input type="date" name="<?php echo $module->input_slug; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>" value="<?php echo $post[$module->input_slug]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='url'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-link"></span></span>
		  </div>
		  <input type="url" name="<?php echo $module->input_slug; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>" value="<?php echo $post[$module->input_slug]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='tel'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-phone"></span></span>
		  </div>
		  <input type="tel" name="<?php echo $module->input_slug; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>" value="<?php echo $post[$module->input_slug]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='email'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-envelope"></span></span>
		  </div>
		  <input type="email" name="<?php echo $module->input_slug; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>" value="<?php echo $post[$module->input_slug]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='password'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-key"></span></span>
		  </div>
		  <input type="password" name="<?php echo $module->input_slug; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>" value="<?php echo $post[$module->input_slug]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='select'): ?>
		<div class="my-4">
			<select class="form-control pl-0 border-top-0 border-left-0 border-right-0 rounded-0 mt-1" id="select_<?php echo $module->input_slug; ?>" name="<?php echo $module->input_slug; ?>"><option <?php echo ($post[$module->input_slug]?'':'selected="selected"'); ?>><?php echo ($module->input_placeholder?$module->input_placeholder:'Select '.$module->input_slug); ?></option>
				<?php 
				if ($options=$module->input_options) {
					foreach ($options as $opt)
						echo '<option value="'.$opt.'" '.(($post[$module->input_slug]==$opt)?'selected="selected"':'').'>'.$opt.'</option>';
				}
				else {
					$options=$dash::get_all_ids($module->input_slug);
					foreach ($options as $opt) {
						$option=$dash::get_content($opt['id']);
						echo '<option value="'.$option['slug'].'" '.(($post[$module->input_slug]==$option['slug'])?'selected="selected"':'').'>'.$option['title'].'</option>';
					}
				}
				?>
			</select>
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='multi_select'): ?>
		<div class="my-4"><?php echo ($module->input_placeholder?$module->input_placeholder:'Select '.$module->input_slug); ?>
			<?php 
			if ($options=$module->input_options) {
				$i=0;
				foreach ($options as $opt) {
					$i++;
					echo '
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" name="'.$module->input_slug.'[]" id="customSwitch_'.$i.'" value="'.$opt.'" '.(in_array($opt, $post[$module->input_slug])?'checked="checked"':'').'>
						<label class="custom-control-label" for="customSwitch_'.$i.'">'.$opt.'</label>
					</div>';
				}
			}
			else {
				$options=$dash::get_all_ids($module->input_slug);
				$i=0;
				foreach ($options as $opt) {
					$i++;
					$option=$dash::get_content($opt['id']);
					echo '
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" name="'.$module->input_slug.'[]" id="customSwitch_'.$i.'" value="'.$option['slug'].'" '.(in_array($option['slug'], $post[$module->input_slug])?'checked="checked"':'').'>
						<label class="custom-control-label" for="customSwitch_'.$i.'">'.$option['title'].'</label>
					</div>';
				}
			}
			?>
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='file_uploader'): ?>
		<div class="input-group my-4">
			<div class="input-group-prepend">
			<span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="inputGroupFileAddon01"><span class="fas fa-upload"></span></span>
			</div>
			<div class="custom-file border-top-0 border-left-0 border-right-0 rounded-0">
			<input type="file" class="custom-file-input border-top-0 border-left-0 border-right-0 rounded-0" type="file" id="<?php echo $module->input_slug; ?>" name="<?php echo $module->input_slug; ?>[]" data-url="/admin/uploader" multiple>
			<label class="custom-file-label border-top-0 border-left-0 border-right-0 rounded-0" for="fileupload">Choose file</label>
			</div>
		</div>
		<div class="col-12 p-0 mb-4 d-none" id="<?php echo $module->input_slug; ?>_fileuploads">
			<div id="progress">
			    <div style="width: 0%;" class="bar"></div>
			</div>
		</div>
		<?php endif; ?>

		<?php if ($module->input_type=='google_map_marker'): ?>
		<?php 
		if ($post[$module->input_slug]) {
			$mapr=json_decode(file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?place_id='.$post[$module->input_slug].'&fields=address_components&key='.GOOGLE_MAP_API_KEY_2), true);
			$mapr_val='';
			foreach ($mapr['result']['address_components'] as $kv)
				$mapr_val.=$kv['long_name'].', ';
			echo '<div class="input-group my-4">
				  <div class="input-group-prepend">
				    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-map-marked-alt"></span></span>
				  </div>
				  <input disabled type="text" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" value="'.substr($mapr_val,0,-2).'">
				  <div class="input-group-append">
				    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><a href="#google_map_div" data-toggle="collapse">Edit map location</a></span>
				  </div>
				</div>';
		}
		?>
		<div class="col-12 card p-0 <?php echo ($post[$module->input_slug]?'collapse':''); ?>" id="google_map_div">
		  <div class="card-header pl-3"><span class="fas fa-map-marker-alt mr-3"></span>&nbsp;Mark your location on the map</div>
		  <div class="card-body">
		    <form><div class="input-group">
		    <input id="pac-input" class="form-control" type="text"
		        placeholder="<?php echo ($module->input_placeholder?$module->input_placeholder:ucfirst($types->{$_GET['type']}->name).' '.$module->input_slug); ?>"></div>
		  </div>
		  <div id="map"></div>
		  <div id="infowindow-content">
		    <h6 id="place-name"></h6>
		    <p id="place-address" class="text-muted"></p>
		    <input id="place-input" class="form-control" name="<?php echo $module->input_slug; ?>" type="hidden" value="<?php echo $post[$module->input_slug]; ?>">
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
		      anchorPoint: new google.maps.Point(0, -29)
		    });

		    <?php if ($post[$module->input_slug]) { ?>
	        var request = {
	          placeId: '<?php echo $post[$module->input_slug]; ?>',
	          fields: ['address_components', 'place_id', 'geometry', 'icon', 'name']
	        };

	        var service = new google.maps.places.PlacesService(map);

	        service.getDetails(request, function(place, status) {
	          if (status === google.maps.places.PlacesServiceStatus.OK) {
	            var marker = new google.maps.Marker({
	              map: map,
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
		<?php endif; ?>

		<?php } ?>
	
		<input type="hidden" name="class" value="dash">
		<input type="hidden" name="function" value="push_content">
		<input type="hidden" name="type" value="<?php echo $types->{$_GET['type']}->slug; ?>">
		<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
		<input type="hidden" name="slug" value="<?php echo $post['slug']; ?>">
		
		<?php if (count($types->{$_GET['type']}->modules)>3) { echo get_admin_menu('edit', $_GET['type']); } ?>
		<p>&nbsp;</p>
	</form>

	</div>

<?php endif; ?>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>