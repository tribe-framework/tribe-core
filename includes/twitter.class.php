<?php
class twitter {

	private $access_token='';

	function __construct() {
		$or=$this->curl_api('https://api.twitter.com/oauth2/token');
		$this->access_token=$or['access_token'];
	}

	function add_filter ($keywords) {
		$params=array();
		if (is_array($keywords)) {
			foreach ($keywords as $keyword)
				$params['add'][]=array('value'=>$keyword);
		}
		else
			$params['add'][]=array('value'=>$keywords);

		return $this->curl_api('https://api.twitter.com/labs/1/tweets/stream/filter/rules', $params, 'POST');
	}

	function delete_filter ($keywords) {
		$params=array();
		if (is_array($keywords)) {
			$params['delete']['values']=$keywords;
		}
		else
			$params['delete']['values']=array($keywords);

		return $this->curl_api('https://api.twitter.com/labs/1/tweets/stream/filter/rules', $params, 'POST');
	}

	function save_stream ($keywords) {
		$this->add_filter($keywords);
		$or=$this->curl_api('https://api.twitter.com/labs/1/tweets/stream/filter', array('format'=>'detailed', 'tweet.format'=>'detailed', 'user.format'=>'detailed', 'place.format'=>'detailed'), 'STREAM');
		$this->delete_filter($keywords);
		return $or['filename'];
	}

	function curl_api ($url, $params=array(), $method='GET') {
		if ($method=='GET') {
			return json_decode(shell_exec("curl -u '".TWITTER_API_KEY.":".TWITTER_API_SECRET."' --data 'grant_type=client_credentials' '".$url."'"), true);
		}
		else if ($method=='STREAM') {
			//pgrep curl, and; kill <process number>
			$filename=uniqid().'.json';
			$filepath=ABSOLUTE_PATH.'/uploads/twitter/'.$filename;
			$fileurl=BASE_URL.'/uploads/twitter/'.$filename;
			shell_exec("curl -X GET -H 'Authorization: Bearer ".$this->access_token."' '".$url.'?'.http_build_query($params)."' >> ".$filepath." &");
			return array('filename'=>$filename, 'filepath'=>$filepath, 'fileurl'=>$fileurl);
		}
		else {
			return json_decode(shell_exec("curl -X ".$method." '".$url."' -H 'Content-type: application/json' -H 'Authorization: Bearer ".$this->access_token."' -d '".json_encode($params)."'"), true);
		}
	}
}

/*
	function get_twitter_user ($user) {
		if (is_int($user)) {
			$params=array('user_id'=>$user);
		}
		else {
			$params=array('screen_name'=>$user);
		}

		$host_url='https://api.twitter.com/1.1/users/show.json';
		
		if ($bearer_access_token=$this->get_bearer_access_token($host_url)) {
			$result=$this->twitter_request($host_url, $params, $bearer_access_token, 'GET');
			return $result;
		}
		else
			return 0;
	}

	function get_latest_tweets ($user_id, $count=25) {

		$host_url='https://api.twitter.com/1.1/statuses/user_timeline.json';
		$params=array('user_id'=>$user_id, 'count'=>$count);
		
		if ($bearer_access_token=$this->get_bearer_access_token($host_url)) {
			$result=$this->twitter_request($host_url, $params, $bearer_access_token, 'GET');
			return $result;
		}
		else
			return 0;
	}

	function get_twitter_user_friends ($user_id, $cursor='-1', $count=5000) {

		$host_url='https://api.twitter.com/1.1/friends/ids.json';
		$friends=array();
		$params=array('user_id'=>$user_id, 'count'=>$count, 'cursor'=>$cursor);

		if ($user_id && $bearer_access_token=$this->get_bearer_access_token($host_url)) {
			$result=$this->twitter_request($host_url, $params, $bearer_access_token, 'GET');
			return $result;
		}
		else
			return 0;
	}
*/
?>