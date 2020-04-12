<?php
/*
 * jQuery File Upload Plugin PHP Example
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

error_reporting(E_ALL | E_STRICT);
include_once ('../config-init.php');
$upload_handler = new UploadHandler(array('script_url'=>ABSOLUTE_PATH.'/admin/uploader.php', 'upload_dir'=>ABSOLUTE_PATH.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D').'/', 'upload_url'=>BASE_URL.'/uploads/'.date('Y').'/'.date('m-F').'/'.date('d-D').'/'));
