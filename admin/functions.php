<?php
function get_admin_menu ($page, $type='', $id=0) {
	$op='';
	if ($page=='dash') {
		$op.='
		<div class="card mb-4"><div class="card-body p-0">
		<div class="btn-toolbar bg-light justify-content-between">
		  '.list_types().'
		</div>
		</div></div>';
	}
	if ($page=='list') {
		$op.='
		<div class="card mb-4"><div class="card-body p-0">
		<div class="btn-toolbar bg-light justify-content-between">
		  '.list_types().''.new_and_list($type).'
		</div>
		</div></div>';
	}
	if ($page=='edit') {
		$op.='
		<div class="card mb-4"><div class="card-body p-0">
		<div class="btn-toolbar bg-light justify-content-between">
			<div class="btn-group">
				<button type="submit" class="btn btn-outline-secondary bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg save_btn"><span class="fa fa-save"></span>&nbsp;Save '.$type.'</button>
				<button type="button" data-toggle="modal" data-target="#delete_conf_'.$id.'" class="btn btn-outline-danger bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg"><span class="fa fa-trash-alt"></span>&nbsp;Delete</button>
			</div>'.new_and_list($type).'
		</div>
		</div></div>';
	}
	return $op;
}

function new_and_list ($type) {
	global $types;
	return '
	<div class="btn-group">
		<a href="/admin/edit?type='.$type.'" class="btn btn-outline-secondary bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg"><span class="fa fa-edit"></span>&nbsp;New '.$type.'</a>
		<a href="/admin/list?type='.$type.'" class="btn btn-outline-secondary bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg"><span class="fa fa-list"></span>&nbsp;List '.$types[$type]['plural'].'</a>
	</div>';
}

function list_types() {
	global $types;
	$list_types='<div class="btn-group" role="group"><a href="/admin/" class="btn btn-outline-secondary bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg"><span class="fa fa-tachometer-alt"></span></a><button id="types-admin-dropdown" type="button" class="btn btn-outline-secondary bg-light border-top-0 border-left-0 border-right-0 rounded-0 btn-lg dropdown-toggle" data-toggle="dropdown">Content types</button><div class="dropdown-menu" aria-labelledby="types-admin-dropdown">';
	foreach ($types as $key => $value) {
    	$list_types.='<a class="dropdown-item" href="/admin/list?type='.$types[$key]['slug'].'">'.ucfirst($types[$key]['plural']).'</a>';
	}
	$list_types.='</div></div>';
	return $list_types;
}
?>