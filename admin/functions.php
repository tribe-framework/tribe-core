<?php
function get_admin_menu ($page, $type='', $role_slug='', $id=0) {
	$op='';
	if ($page=='dash') {
		$op.='
		<div class="mb-4"><div class="card-body p-0">
		<div class="btn-toolbar justify-content-between">
		  '.list_types().'
		</div>
		</div></div>';
	}
	if ($page=='list') {
		$op.='
		<div class="mb-4"><div class="card-body p-0">
		<div class="btn-toolbar justify-content-between">
		  '.list_types($type).new_and_list($type, $role_slug).'
		</div>
		</div></div>';
	}
	if ($page=='edit') {
		$op.='
		<div class="mb-4"><div class="card-body p-0">
		<div class="btn-toolbar justify-content-between">
		'.list_types($type).edit_options($type, $id).new_and_list($type, $role_slug).'
		</div>
		</div></div>';
	}
	if ($page=='view') {
		$op.='
		<div class="mb-4"><div class="card-body p-0">
		<div class="btn-toolbar justify-content-between">
		'.list_types($type).new_and_list($type, $role_slug).'
		</div>
		</div></div>';
	}
	return $op;
}

function edit_options ($type, $id=0) {
	global $dash;
	return '<div class="btn-group">
				<button type="submit" class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0 save_btn"><span class="fa fa-save"></span>&nbsp;Save</button>
				<a href="'.($id?BASE_URL.'/'.$type.'/'.$dash::get_content_meta($id, 'slug'):'#').'" target="new" class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0 view_btn '.($type=='user'?'d-none':'').' '.($id?'':'disabled').'"><span class="fa fa-external-link-alt"></span>&nbsp;View</a>
				<button type="button" data-toggle="modal" data-target="#delete_conf_'.$id.'" class="btn btn-outline-danger border-top-0 border-left-0 border-right-0 rounded-0"><span class="fa fa-trash-alt"></span>&nbsp;Delete</button>
			</div>';
}

function new_and_list ($type, $role_slug='') {
	global $types;
	return '
	<div class="btn-group">
		<a href="'.BASE_URL.'/admin/edit?type='.$type.(trim($role_slug)?'&role='.urlencode($role_slug):'').'" class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0"><span class="fa fa-edit"></span>&nbsp;New</a>
		<a href="'.BASE_URL.'/admin/list?type='.$type.(trim($role_slug)?'&role='.urlencode($role_slug):'').'" class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0"><span class="fa fa-list"></span>&nbsp;List</a>
	</div>';
}

function list_types($type='') {
	global $types;
	$list_types='<div class="btn-group" role="group"><a href="'.BASE_URL.'/admin/" class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0"><span class="fa fa-tachometer-alt"></span></a>';

	if ($type) {
		$list_types.='<button id="types-admin-dropdown" type="button" class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0 dropdown-toggle" data-toggle="dropdown">'.(isset($type)?ucfirst($types[$type]['plural']):'').'&nbsp;<span class="sr-only">Content types</span></button><div class="dropdown-menu" aria-labelledby="types-admin-dropdown">';
		foreach ($types as $key => $value) {
			if ($types[$key]['type']=='content')
		    	$list_types.='<a class="dropdown-item" href="'.BASE_URL.'/admin/list?type='.$types[$key]['slug'].'">'.ucfirst($types[$key]['plural']).'</a>';
		}
		$list_types.='</div></div>';
	}
	else {
		$list_types.='<button id="types-admin-dropdown" type="button" class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0 dropdown-toggle d-md-none" data-toggle="dropdown">'.(isset($type)?ucfirst($types[$type]['plural']):'').'&nbsp;Content types</button><div class="dropdown-menu" aria-labelledby="types-admin-dropdown">';
		foreach ($types as $key => $value) {
	    	if ($types[$key]['type']=='content')
		    	$list_types.='<a class="dropdown-item" href="'.BASE_URL.'/admin/list?type='.$types[$key]['slug'].'">'.ucfirst($types[$key]['plural']).'</a>';
		}
		$list_types.='</div><div class="btn-group d-none d-md-block" role="group">';
		foreach ($types as $key => $value) {
			if ($types[$key]['type']=='content' && $types[$key]['slug'])
		    	$list_types.='<a class="btn btn-outline-primary border-top-0 border-left-0 border-right-0 rounded-0" href="'.BASE_URL.'/admin/list?type='.$types[$key]['slug'].'">'.ucfirst($types[$key]['plural']).'</a>';
		}
		$list_types.='</div></div>';
	}
	return $list_types;
}

function is_access_allowed ($id, $user_restricted_to_input_modules=array()) {
	global $session_user, $dash;

	//if user has even on field allowing access to edit post, they will be given access to the post
    $allowed_access=0;
    if (count($user_restricted_to_input_modules)) {
      foreach ($user_restricted_to_input_modules as $key => $value) {
      	if (is_array($dash->get_content_meta($id, $value)) && count(array_intersect($session_user[$value], $dash->get_content_meta($id, $value)))) {
          $allowed_access=1;
          break;
      	}
      	else (in_array($dash->get_content_meta($id, $value), $session_user[$value])) {
          $allowed_access=1;
          break;
      	}
      }
    }
    else
      $allowed_access=1;

  	return $allowed_access;
}
?>