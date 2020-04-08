<?php
include_once ('config-init.php');

if ($_GET['go']) {
	$params=array();
	$params['client_id']=YOUTUBE_CLIENT_ID;
	$params['redirect_uri']='https://beatrootnews.com/youtube';
	$params['response_type']='code';
	$params['scope']='https://www.googleapis.com/auth/youtube https://www.googleapis.com/auth/youtube.upload';
	$url='https://accounts.google.com/o/oauth2/v2/auth';
	header('Location: '.$url.'?'.http_build_query($params));
}
else if ($_GET['error']=='access_denied') {
	echo 'youtube denied access';
}
else if (trim($_GET['code'])) {
	$params=array();
	$params['client_id']=YOUTUBE_CLIENT_ID;
	$params['redirect_uri']='https://beatrootnews.com/youtube';
	$params['client_secret']=YOUTUBE_CLIENT_SECRET;
	$params['grant_type']='authorization_code';
	$params['code']=$_GET['code'];
	$url='https://oauth2.googleapis.com/token';
	$or=$youtube->curl_api($url, $params, 'POST');
	$or['type']='youtube';
	$or['unique_id']=$or['access_token'];
	$_SESSION['youtube_token']=$or['access_token'];
	$auth->push_user($or);
}
else {
	$url='https://www.googleapis.com/upload/youtube/v3/videos?part='.urlencode('snippet,status');
	$params=array();
	$params['snippet']=array('categoryId'=>'11', 'description'=>'test description', 'title'=>'test video');
	$params['status']=array('privacyStatus'=>'private');
	var_dump($youtube->curl_api($url, $params, 'UPLOAD', '393020651.mp4'));
	
	//var_dump($youtube->curl_api('https://www.googleapis.com/youtube/v3/videos', array('id'=>'OH1CFtIM52U', 'part'=>'status')));

	//var_dump($youtube->curl_api('https://www.googleapis.com/youtube/v3/videoCategories', array('regionCode'=>'in', 'part'=>'snippet')));
}
?>
