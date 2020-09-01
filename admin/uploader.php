<?php
error_reporting(E_ALL | E_STRICT);
include_once ('../init.php');

//https://github.com/blueimp/jQuery-File-Upload/blob/master/server/php/UploadHandler.php
include_once(ABSOLUTE_PATH.'/plugins/blueimp-jquery-file-upload/UploadHandler.php');

if (UPLOAD_FILE_TYPES)
	$upload_handler = new UploadHandler(array('script_url'=>ABSOLUTE_PATH.'/admin/uploader.php', 'upload_dir'=>ABSOLUTE_PATH.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D').'/', 'upload_url'=>BASE_URL.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D').'/', 'inline_file_types'=>UPLOAD_FILE_TYPES, 'accept_file_types'=>UPLOAD_FILE_TYPES));
else
	$upload_handler = new UploadHandler(array('script_url'=>ABSOLUTE_PATH.'/admin/uploader.php', 'upload_dir'=>ABSOLUTE_PATH.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D').'/', 'upload_url'=>BASE_URL.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D').'/'));
