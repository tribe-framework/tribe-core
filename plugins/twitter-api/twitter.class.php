<?php
class twitter {

	private $access_token='';

	function __construct() {
		$or=$this->curl_api('https://api.twitter.com/oauth2/token', 'CRED');
		$this->access_token=$or['access_token'];
	}

	function get_tweet_object ($tweet_id) {
		return $this->curl_api('https://api.twitter.com/labs/2/tweets/'.$tweet_id.'?expansions=attachments.media_keys&tweet.fields=source,public_metrics,context_annotations,entities', 'GET');
	}

	function add_filter ($keywords) {
		$params=array();
		if (is_array($keywords)) {
			foreach ($keywords as $keyword)
				$params['add'][]=array('value'=>$keyword);
		}
		else
			$params['add'][]=array('value'=>$keywords);
		return $this->curl_api('https://api.twitter.com/labs/1/tweets/stream/filter/rules', 'POST', $params);
	}

	function delete_filter ($keywords) {
		$params=array();
		if (is_array($keywords)) {
			$params['delete']['values']=$keywords;
		}
		else
			$params['delete']['values']=array($keywords);

		return $this->curl_api('https://api.twitter.com/labs/1/tweets/stream/filter/rules', 'POST', $params);
	}

	function save_stream ($post) {
		//clear up all filters
		if (null !== ($filters=$this->curl_api('https://api.twitter.com/labs/1/tweets/stream/filter/rules', 'GET')['data'])) {
			foreach ($filters as $data) {
				$this->delete_filter($data['value']);
			}
		}
		//add filter
		$this->add_filter($post['keywords']);
		//get stream
		$or=$this->curl_api('https://api.twitter.com/labs/1/tweets/stream/filter', 'STREAM', array('format'=>'detailed', 'tweet.format'=>'detailed', 'user.format'=>'detailed', 'place.format'=>'detailed'));
		//delete filter
		$this->delete_filter($post['keywords']);
		//wait for stream to get collected in a file
		sleep($post['wait_seconds']?$post['wait_seconds']:15);
		//stop collection
		$this->kill_process();
		//also send access_token - no use as of now
		$or['access_token']=$this->access_token;
		return $or;
	}

	function curl_api ($url, $method='GET', $params=array()) {
		if ($method=='CRED') {
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
			return json_decode(shell_exec("curl -X ".$method." '".$url."' -H 'Content-type: application/json' -H 'Authorization: Bearer ".$this->access_token."' -d '".(empty($params)?'':json_encode($params))."'"), true);
		}
	}

	function kill_process () {
		return shell_exec('kill '.shell_exec('pgrep curl'));
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