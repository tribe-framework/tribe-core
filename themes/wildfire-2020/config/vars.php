<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

//timezone
date_default_timezone_set('Asia/Kolkata');

//allow upload in file types
define('UPLOAD_FILE_TYPES', '/\.(zip|png|jpe?g|gif|pdf|doc|docx|xls|xlsx|mov|mp4|vtt)$/i');

//S3 backup credentials for /config/backup.php
define('S3_BKUP_HOST_BASE', 's3.wasabisys.com');
define('S3_BKUP_HOST_BUCKET', '%(bucket)s.s3.wasabisys.com');
define('S3_BKUP_ACCESS_KEY', '');
define('S3_BKUP_SECRET_KEY', '');
define('S3_BKUP_FOLDER_NAME', BARE_URL);

//other services
define('GOOGLE_MAP_API_KEY_1', '');
define('GOOGLE_MAP_API_KEY_2', '');
define('YOUTUBE_CLIENT_ID', '');
define('YOUTUBE_CLIENT_SECRET', '');
define('TWITTER_API_KEY', '');
define('TWITTER_API_SECRET', '');
define('TWITTER_ACCESS_KEY', '');
define('TWITTER_ACCESS_SECRET', '');
define('CLOUDFLARE_ACCOUNT_ID', '');
define('CLOUDFLARE_ACCOUNT_KEY', '');
define('CLOUDNS_AUTH_ID', '');
define('CLOUDNS_AUTH_PASSWORD', '');

//payment gateways
define('INSTAMOJO_KEY', '');
define('INSTAMOJO_TOKEN', '');
define('INSTAMOJO_SALT', '');
define('RAZORPAY_KEY', '');
define('RAZORPAY_SECRET', '');
define('PAYUMONEY_KEY', '');
define('PAYUMONEY_SALT', '');
?>