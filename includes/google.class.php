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

	function curl_api ($url, $params=array(), $method='GET', $body_params='', $file_path='', $content_type='application/json') {
		return json_decode(shell_exec("curl -v -X ".$method." '".$url.(empty($params)?'':'?'.http_build_query($params))."' -H 'Content-type: ".$content_type."' -H 'Authorization: Bearer ".$this->access_token."' ".(empty($body_params)?"":"-d '".json_encode($body_params)."'")." ".(trim($file_path)?"-F 'file=@".$file_path.";type=application/octet-stream'":"")), true);
	}
}
?>