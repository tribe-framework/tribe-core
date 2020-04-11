<?php
class google {

	private $access_token='';

	function __construct() {
		global $_SESSION;
		$this->access_token=$_SESSION['google_token'] ?? '';
	}

	function revoke_access () {
		if ($auth->get_user_id_from_unique_id($this->access_token, 'google')) {
			$params=array();
			$params['token']=$this->access_token;
			$url='https://oauth2.googleapis.com/revoke';
			$this->curl_api($url, $params, 'POST');
			return 1;
		}
		else
			return 0;
	}

	function curl_api ($url, $params=array(), $method='GET', $file_path='') {
		if ($method=='GET') {
			return json_decode(shell_exec("curl -X GET -H 'Authorization: Bearer ".$this->access_token."' '".$url.(count($params)?'?':'').http_build_query($params)."'"), true);
		}
		else if ($method=='UPLOAD') {
			//pgrep curl, and; kill <process number>
			if (substr(mime_content_type($file_path), 0, 5)=='video')
				echo "curl -v POST -H 'Authorization: Bearer ".$this->access_token.";Content-type: ".mime_content_type($file_path)."' -d '@".$file_path."' -H 'Authorization: Bearer ".$this->access_token.";Content-type: application/json' -d '".json_encode($params)."' '".$url."'";
				//echo "curl -v -X POST -H 'Authorization: Bearer ".$this->access_token.";Content-type: application/json' -d '".json_encode($params)."' '".$url."'";
				//echo "curl -v -X POST --upload-file '@".$file_path."' -H 'Authorization: Bearer ".$this->access_token.";Content-type: ".mime_content_type($file_path)."' '".$url."'";
				//return json_decode(shell_exec("curl -v -X POST -H 'Content-type: ".mime_content_type($file_path)."' -d @".$file_path." -H 'Authorization: Bearer ".$this->access_token."' -H 'Content-type: application/json' -d '".json_encode($params)."' '".$url."'"), true);
		}
		else {
			return json_decode(shell_exec("curl -X ".$method." '".$url."' -H 'Content-type: application/json' -d '".json_encode($params)."'"), true);
		}
	}
}
?>