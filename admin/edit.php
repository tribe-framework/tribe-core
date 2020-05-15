<?php
include_once ('../config-init.php');
include_once (ABSOLUTE_PATH.'/admin/header.php');

if ($_GET['id'])
	$post = $dash::get_content($_GET['id']);

if (($_GET['id'] && $post['type']==$type) || !$_GET['id']):

	//for testing resticted min and max ids for archive format changes
	if (!($pid=$_GET['id']))
		$pid=$dash::get_next_id();
?>

	<link rel="stylesheet" type="text/css" href="/plugins/typeout/typeout.css">

	<div class="container mt-3">
	<a name="infos"></a><div id="infos" class="d-none alert alert-info"></div>
	<a name="errors"></a><div id="errors" class="d-none alert alert-danger"></div>

	<form method="post" class="edit_form" action="/admin/json">

		<?php echo get_admin_menu('edit', $type, $_GET['id']); ?>

		<h2 class="form_title">Edit <?php echo $types[$type]['name']; ?></h2>

		<?php foreach ($types[$type]['modules'] as $module) {
			if ((!$module['restrict_id_max'] || $pid<=$module['restrict_id_max']) && (!$module['restrict_id_min'] || $pid>=$module['restrict_id_min'])):

			$module_input_slug=$module['input_slug'];
			$module_input_type=$module['input_type'];
			$module_input_lang=$module['input_lang'];
			$module_input_options=$module['input_options'];
			$module_input_placeholder=$module['input_placeholder'];
			$slug_displayed=0;

			$module_input_slug_arr=array();
			if (is_array($module_input_lang))
				$module_input_slug_arr=$module_input_lang;
			else
				$module_input_slug_arr[0]['slug']='';

			foreach ($module_input_slug_arr as $input_lang) {
				$module_input_slug_lang=$module_input_slug.($input_lang['slug']?'_'.$input_lang['slug']:'');
			?>
			
		<?php if ($module_input_type=='text'): ?>
		<div class="input-group input-group-lg my-4"><input type="text" name="<?php echo $module_input_slug_lang; ?>" class="pl-0 border-top-0 border-left-0 border-right-0 rounded-0 form-control" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" id="<?php echo $module_input_slug_lang; ?>" value="<?php echo ($post[$module_input_slug_lang]?$post[$module_input_slug_lang]:''); ?>"></div>

		<?php if ($module_input_slug=='title' && !$slug_displayed) {$slug_displayed=1; echo '<div class="input-group"><div id="slug_update_div" class="custom-control custom-switch '.($_GET['id']?'d-block':'d-none').'"><input type="checkbox" class="custom-control-input" name="slug_update" id="slug_update" value="1"><label class="custom-control-label" for="slug_update">Update the URL slug based on title (will change the link)</label></div></div>';} ?>
		<?php endif; ?>

		<?php if ($module_input_type=='textarea'): ?>
		<div class="input-group my-4"><textarea name="<?php echo $module_input_slug_lang; ?>" class="pl-0 border-top-0 border-left-0 border-right-0 rounded-0 form-control" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" id="<?php echo $module_input_slug_lang; ?>"><?php echo ($post[$module_input_slug_lang]?$post[$module_input_slug_lang]:''); ?></textarea></div>
		<?php endif; ?>

		<?php if ($module_input_type=='typeout'): ?>
		<div class="typeout-menu my-4">
			<?php if (in_array('fullScreen', $module_input_options)) { ?>
			<button type="button" data-expanded="0" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-fullscreen" data-toggle="tooltip" data-placement="top" title="full screen"><span class="fas fa-compress"></span></button>
			<?php } ?>

			<?php if (in_array('undo', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-undo" data-typeout-command="undo" data-toggle="tooltip" data-placement="top" title="undo"><span class="fas fa-undo"></span></button>
			<?php } ?>

			<?php if (in_array('insertParagraph', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-insertParagraph" data-typeout-command="insertParagraph" data-toggle="tooltip" data-placement="top" title="insert paragraph break"><span class="fas fa-paragraph"></span></button>
			<?php } ?>

			<?php if (in_array('heading', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input-exec typeout-heading" data-typeout-command="heading" data-typeout-info="h4" data-toggle="tooltip" data-placement="top" title="heading"><span class="fas fa-heading"></span></button>
			<?php } ?>

			<?php if (in_array('bold', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-bold" data-typeout-command="bold" data-toggle="tooltip" data-placement="top" title="bold"><span class="fas fa-bold"></span></button>
			<?php } ?>

			<?php if (in_array('italic', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-italic" data-typeout-command="italic" data-toggle="tooltip" data-placement="top" title="italic"><span class="fas fa-italic"></span></button>
			<?php } ?>

			<?php if (in_array('createLink', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="createLink" data-typeout-info="Enter link URL" data-toggle="tooltip" data-placement="top" title="create link"><span class="fas fa-link"></span></button>
			<?php } ?>

			<?php if (in_array('unlink', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec typeout-unlink" data-typeout-command="unlink" data-toggle="tooltip" data-placement="top" title="un-link"><span class="fas fa-unlink"></span></button>
			<?php } ?>

			<?php if (in_array('insertImage', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertImage" data-typeout-info="Enter image URL" data-toggle="tooltip" data-placement="top" title="insert image"><span class="fas fa-image"></span></button>
			<?php } ?>

			<?php if (in_array('insertPDF', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertPDF" data-typeout-info="Enter PDF URL" data-toggle="tooltip" data-placement="top" title="insert PDF"><span class="fas fa-file-pdf"></span></button>
			<?php } ?>

			<?php if (in_array('insertHTML', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertHTML" data-typeout-info="Enter HTML" data-toggle="tooltip" data-placement="top" title="insert HTML"><span class="fas fa-code"></span></button>
			<?php } ?>

			<?php if (in_array('attach', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="attach" data-typeout-info="" data-toggle="tooltip" data-placement="top" title="add attachment"><span class="fas fa-paperclip"></span></button>
			<?php } ?>

			<?php if (in_array('removeFormat', $module_input_options)) { ?>
			<button type="button" class="btn bg-light border-0 rounded-0 mt-1 typeout typeout-exec" data-typeout-command="removeFormat" data-toggle="tooltip" data-placement="top" title="remove formatting"><span class="fas fa-remove-format"></span></button>
			<?php } ?>
		</div>

		<div class="typeout-content my-4 border-bottom" id="typeout-<?php echo $module_input_slug_lang; ?>" data-input-slug="<?php echo $module_input_slug_lang; ?>" contenteditable="true" style="overflow: auto;" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>"><?php echo $post[$module_input_slug_lang]; ?></div>
		<input type="hidden" name="<?php echo $module_input_slug_lang; ?>">
		<?php endif; ?>

		<?php if ($module_input_type=='date'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-calendar"></span></span>
		  </div>
		  <input type="date" name="<?php echo $module_input_slug_lang; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" value="<?php echo $post[$module_input_slug_lang]; ?>">
		  <?php echo ($module_input_placeholder?'<small class="col-12 row form-text text-muted">'.$module_input_placeholder.'</small>':''); ?>
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='url' || $module_input_type=='multi_url'): ?>
		<div class="url-group" id="url-group-<?php echo $module_input_slug_lang; ?>">
		<?php
		$i=0;
		$type_name_values=array();
		if (is_array($post[$module_input_slug_lang]))
			$type_name_values=$post[$module_input_slug_lang];
		else
			$type_name_values[0]=$post[$module_input_slug_lang];
		foreach ($type_name_values as $type_name_value) { 
			if ($i<1 || trim($type_name_value)) {
		?>
			<div class="input-group my-4">
			  <div class="input-group-prepend">
			    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-link"></span></span>
			  </div>
			  <input type="url" name="<?php echo $module_input_slug_lang.($module_input_type=='multi_url'?'[]':''); ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" value="<?php echo $type_name_value; ?>">
			  <?php echo ($module_input_type=='multi_url'?'<div class="input-group-append multi_add_btn" data-group-class="url-group" data-input-slug="'.$module_input_slug_lang.'"><button class="btn btn-outline-secondary" type="button"><span class="fas fa-plus"></span></button></div>':''); ?>
			</div>
		<?php } $i++; } ?>
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='tel'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-phone"></span></span>
		  </div>
		  <input type="tel" name="<?php echo $module_input_slug_lang; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" value="<?php echo $post[$module_input_slug_lang]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='priority'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-sort-numeric-up"></span></span>
		  </div>
		  <input type="number" name="<?php echo $module_input_slug_lang; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" min="<?php echo (isset($module['input_min'])?$module['input_min']:''); ?>" max="<?php echo (isset($module['input_max'])?$module['input_max']:''); ?>"  placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" value="<?php echo $post[$module_input_slug_lang]; ?>">
		  <?php echo ($module_input_placeholder?'<small class="col-12 row form-text text-muted">'.$module_input_placeholder.'</small>':''); ?>
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='email'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-envelope"></span></span>
		  </div>
		  <input type="email" name="<?php echo $module_input_slug_lang; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" value="<?php echo $post[$module_input_slug_lang]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='password'): ?>
		<div class="input-group my-4">
		  <div class="input-group-prepend">
		    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-key"></span></span>
		  </div>
		  <input type="password" name="<?php echo $module_input_slug_lang; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" value="<?php echo $post[$module_input_slug_lang]; ?>">
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='select'): ?>
		<div class="form-group my-4">
			<select class="form-control pl-0 border-top-0 border-left-0 border-right-0 rounded-0 mt-1" id="select_<?php echo $module_input_slug_lang; ?>" name="<?php echo $module_input_slug_lang; ?>"><option <?php echo ($post[$module_input_slug_lang]?'':'selected="selected"'); ?> value=""><?php echo ($module_input_placeholder?$module_input_placeholder:'Select '.$module_input_slug_lang); ?></option>
				<?php 
				if ($options=$module_input_options) {
					foreach ($options as $opt) {
						if (is_array($opt))
							echo '<option value="'.$opt['slug'].'" '.(($post[$module_input_slug_lang]==$opt['slug'])?'selected="selected"':'').'>'.$opt['title'].'</option>';
						else
							echo '<option value="'.$opt.'" '.(($post[$module_input_slug_lang]==$opt)?'selected="selected"':'').'>'.$opt.'</option>';
					}
				}
				else {
					$options=$dash::get_all_ids($module_input_slug_lang, $types[$module_input_slug_lang]['primary_module'], 'ASC');
					foreach ($options as $opt) {
						$option=$dash::get_content($opt['id']);
						echo '<option value="'.$option['slug'].'" '.(($post[$module_input_slug_lang]==$option['slug'])?'selected="selected"':'').'>'.$option['title'].'</option>';
					}
				}
				?>
			</select>
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='multi_select'): ?>
		<div class="form-group my-4"><?php echo ($module_input_placeholder?$module_input_placeholder:'Select '.$module_input_slug_lang); ?>
			<?php 
			if ($options=$module_input_options) {
				$i=0;
				foreach ($options as $opt) {
					$i++;
					if (is_array($opt)) {
						echo '
						<div class="custom-control custom-switch">
							<input type="checkbox" class="custom-control-input" name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" value="'.$opt['slug'].'" '.(in_array($opt['slug'], $post[$module_input_slug_lang])?'checked="checked"':'').'>
							<label class="custom-control-label" for="'.$module_input_slug_lang.'_customSwitch_'.$i.'">'.$opt['title'].'</label>
						</div>';
					}
					else {
						echo '
						<div class="custom-control custom-switch">
							<input type="checkbox" class="custom-control-input" name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" value="'.$opt.'" '.(in_array($opt, $post[$module_input_slug_lang])?'checked="checked"':'').'>
							<label class="custom-control-label" for="'.$module_input_slug_lang.'_customSwitch_'.$i.'">'.$opt.'</label>
						</div>';
					}
				}
			}
			else {
				$options=$dash::get_all_ids($module_input_slug_lang, $types[$module_input_slug_lang]['primary_module'], 'ASC');
				$i=0;
				foreach ($options as $opt) {
					$i++;
					$option=$dash::get_content($opt['id']);
					echo '
					<div class="custom-control custom-switch">
						<input type="checkbox" class="custom-control-input" name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" value="'.$option['slug'].'" '.(in_array($option['slug'], $post[$module_input_slug_lang])?'checked="checked"':'').'>
						<label class="custom-control-label" for="'.$module_input_slug_lang.'_customSwitch_'.$i.'">'.$option['title'].'</label>
					</div>';
				}
			}
			?>
		</div>
		<?php endif; ?>

		<?php if ($module_input_type=='file_uploader'): ?>
		<div class="input-group my-4">
			<div class="input-group-prepend">
			<span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="inputGroupFileAddon01"><span class="fas fa-upload"></span></span>
			</div>
			<div class="custom-file border-top-0 border-left-0 border-right-0 rounded-0">
			<input type="file" class="custom-file-input border-top-0 border-left-0 border-right-0 rounded-0" type="file" id="<?php echo $module_input_slug_lang; ?>" data-bunching='<?php echo json_encode($module['input_bunching']); ?>' data-descriptor="<?php echo ($module['input_descriptor']?'1':''); ?>" data-url="/admin/uploader" multiple>
			<label class="custom-file-label border-top-0 border-left-0 border-right-0 rounded-0" for="fileupload">Choose file</label>
			</div>
		  	<?php echo ($module_input_placeholder?'<small class="col-12 row form-text text-muted">'.$module_input_placeholder.'</small>':''); ?>
		</div>
		<div class="col-12 p-0 mb-4 d-none" id="<?php echo $module_input_slug_lang; ?>_fileuploads">
			<div id="progress">
			    <div style="width: 0%;" class="bar"></div>
			</div>
		</div>

		<div class="col-12 p-0 mb-4 d-block">
			<?php
			$i=0;
			foreach ($post[$module_input_slug_lang] as $file) {
				echo '<p class="file done"><span>'.urldecode(basename($file)).'</span>&nbsp;&nbsp;<span class="delete_btn btn btn-sm bg-danger"><span class="fas fa-trash-alt"></span></span><input type="hidden" name="'.$module_input_slug_lang.'[]" value="'.$file.'">&nbsp;&nbsp;<span class="copy_btn btn btn-sm bg-white" data-clipboard-text="'.$file.'"><span class="fas fa-link"></span>&nbsp;copy URL</span>&nbsp;&nbsp;<a style="display: inline; padding:8px;" class="btn btn-sm bg-white" href="'.$file.'" target="new"><span class="fas fa-external-link-alt"></span>&nbsp;view</a>';
				if (is_array($module['input_bunching'])) {
					echo '&nbsp;&nbsp;<select class="btn btn-sm bg-white" name="'.$module_input_slug_lang.'_bunching[]"><option value="">file option</option>';
					foreach ($module['input_bunching'] as $opt)
						echo '<option value="'.$opt['slug'].'" '.(($post[$module_input_slug_lang.'_bunching'][$i]==$opt['slug'])?'selected="selected"':'').'>'.$opt['title'].'</option>';
					echo '</select>';
				}
				echo ($module['input_descriptor']?'&nbsp;&nbsp;<button type="button" class="btn btn-sm bg-white m-1" data-toggle="modal" data-target="#'.$module_input_slug_lang.'_descriptor_m_'.$i.'">descriptor</button><div class="modal fade" id="'.$module_input_slug_lang.'_descriptor_m_'.$i.'" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">add file descriptor</h5><button type="button" class="close" data-dismiss="modal" aria-label="close"><span aria-hidden="true">×</span></button></div><div class="modal-body"><textarea name="'.$module_input_slug_lang.'_descriptor[]" class="form-control" placeholder="enter file descriptor">'.$post[$module_input_slug_lang.'_descriptor'][$i].'</textarea><input name="'.$module_input_slug_lang.'_descriptor_date[]" value="'.$post[$module_input_slug_lang.'_descriptor_date'][$i].'" type="date" class="form-control" placeholder="enter file date"></div><div class="modal-footer"><button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">save</button></div></div></div></div>':'').'</p>';
				$i++;
			}
			?>
		</div>

		<?php endif; ?>

		<?php if ($module_input_type=='google_map_marker'): ?>
		<?php 
		if ($post[$module_input_slug_lang]) {
			$mapr=json_decode(file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?place_id='.$post[$module_input_slug_lang].'&fields=address_components&key='.GOOGLE_MAP_API_KEY_2), true);
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

		<?php } endif; } ?>
	
		<input type="hidden" name="class" value="dash">
		<input type="hidden" name="function" value="push_content">
		<input type="hidden" name="type" value="<?php echo $types[$type]['slug']; ?>">
		<input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
		<input type="hidden" name="slug" value="<?php echo $post['slug']; ?>">
		
		<?php if (count($types[$type]['modules'])>3) { echo get_admin_menu('edit', $type, $_GET['id']); } ?>
		<p>&nbsp;</p>
	</form>

	</div>

	<div class="modal fade" id="delete_conf_<?php echo $_GET['id']; ?>" tabindex="-1" role="dialog">
	  <div class="modal-dialog" role="document">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h5 class="modal-title">Confirm</h5>
	        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
	          <span aria-hidden="true">&times;</span>
	        </button>
	      </div>
	      <div class="modal-body">
	        Are you sure you wish to delete this content?
	      </div>
	      <div class="modal-footer">
	        <form method="post" class="edit_form" action="/admin/json">
	          <input type="hidden" name="class" value="dash">
	          <input type="hidden" name="function" value="do_delete">
	          <input type="hidden" name="type" value="<?php echo $types[$type]['slug']; ?>">
	          <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
	          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
	          <button type="submit" class="btn btn-danger">Yes, delete it</button>
	        </form>
	      </div>
	    </div>
	  </div>
	</div>
		
<?php endif; ?>

<?php include_once (ABSOLUTE_PATH.'/admin/footer.php'); ?>