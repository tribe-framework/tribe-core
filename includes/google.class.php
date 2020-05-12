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

	function curl_api ($url, $params=array(), $method='GET', $body_params='', $content_type='application/json') {
		//echo "curl ".$method." -H 'Content-type: ".$content_type."' -H 'Authorization: Bearer ".$this->access_token."' --data @".ABSOLUTE_PATH."/uploads/2020/05-May/09-Sat/oceans.mp4"." ".(empty($body_params)?"":"--data '".json_encode($body_params)."'")." '".$url.(empty($params)?'':'?'.http_build_query($params))."'";
		return json_decode(shell_exec("curl -v -X ".$method." -H 'Content-type: ".$content_type."' -H 'Authorization: Bearer ".$this->access_token."' --data @".ABSOLUTE_PATH."/uploads/2020/05-May/09-Sat/oceans.mp4"." ".(empty($body_params)?"":"--data '".json_encode($body_params)."'")." '".$url.(empty($params)?'':'?'.http_build_query($params))."'"), true);
	}

	function get_curl_file ($file){
		$mime = mime_content_type($file);
		$info = pathinfo($file);
		$name = $info['basename'];
		$output = new CURLFile($file, $mime, $name);
		return $output;
	}
}
?>