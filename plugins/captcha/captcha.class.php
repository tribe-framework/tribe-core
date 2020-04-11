<?php
class captcha {  
	public $errormsg;
	public $successmsg;
	
	public $left_arr=array();
	
	function __construct() {
		$this->left_arr=array_map('trim', explode(',', CAPTCHA_WORDS));
	}

	function check_captcha ($captcha_input, $captcha_code) {
		global $sql;
		$captcha_input = strtolower(preg_replace('/\s+/', '', $captcha_input));
		$captcha_secret=$this->get_captcha_string_from_code($captcha_code, 0);
		$sql->executeSQL("DELETE FROM `captcha` WHERE `code`='$captcha_code'");
		if ($captcha_input==$captcha_secret)
			return 1;
		else
			return 0;
	}
	
	function show_input_fields ($css_classes='form-control', $css_id='') {
		global $sql;
		$string = rand(10000,99999);
		$code = uniqid();
		$sql->executeSQL("INSERT INTO `captcha` (`string`, `code`) VALUES ('$string', '$code')");
		if ($sql->lastInsertID()) {
			$op = '<div class="form-group col-6"><label for="captcha"><img src="/captcha.php?captcha_code='.$code.'"></label>
		    <input type="text" name="captcha_input" id="captcha" class="captcha_input form-control" aria-describedby="captchaHelp" placeholder="Type the above digits here">
		    <small id="emailHelp" class="form-text text-muted">Enter the digits in the CAPTCHA image above.</small>
		    <input type="hidden" class="captcha_code" name="captcha_code" value="'.$code.'"></div><div class="col"></div>';
		}
		else {
			$op = '<div class="col-6">ERROR: please report to the system administrator.</div><div class="col"></div>';
		}
		return $op;
	}

	function get_captcha_string_from_code($captcha_code, $display=1) {
		global $sql;
		$q=$sql->executeSQL("SELECT `string` FROM `captcha` WHERE `code`='$captcha_code'
			");
		if ($display)
			return $q[0]['string'];
		else
			return strtolower(preg_replace('/\s+/', '', $q[0]['string']));
	}
}