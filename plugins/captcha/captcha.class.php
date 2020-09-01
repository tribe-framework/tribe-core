<?php
class captcha {  
	public $errormsg;
	public $successmsg;
	
	function __construct() {

	}

	function check_captcha ($captcha_input, $captcha_code) {
		global $_SESSION;
		$captcha_input = strtolower(preg_replace('/\s+/', '', $captcha_input));
		$captcha_secret=$this->get_captcha_string_from_code($captcha_code, 0);

		unset($_SESSION['CAPTCHA_STRING']);
		unset($_SESSION['CAPTCHA_CODE']);

		if ($captcha_input==$captcha_secret)
			return 1;
		else
			return 0;
	}
	
	function show_input_fields ($css_classes='form-control', $css_id='') {
		global $_SESSION;
		unset($_SESSION['CAPTCHA_STRING']);
		unset($_SESSION['CAPTCHA_CODE']);
		$_SESSION['CAPTCHA_STRING']=rand(10000,99999);
		$_SESSION['CAPTCHA_CODE']=uniqid();

		$op = '
		<div class="input-group input-group-lg">
			<div class="input-group-prepend"><span class="input-group-text"><img src="/plugins/captcha/captcha.php?captcha_code='.$_SESSION['CAPTCHA_CODE'].'"></span></div>
		    <input type="text" name="captcha_input" id="captcha" class="border captcha_input form-control" aria-describedby="captchaHelp" placeholder="Enter the digits in the CAPTCHA image">
	    </div>
	    <input type="hidden" class="captcha_code" name="captcha_code" value="'.$_SESSION['CAPTCHA_CODE'].'"></div><div class="col">';
		
		return $op;
	}

	function get_captcha_string_from_code ($captcha_code, $display=1) {
		global $_SESSION;
		if ($display)
			return $_SESSION['CAPTCHA_STRING'];
		else
			return strtolower(preg_replace('/\s+/', '', $_SESSION['CAPTCHA_STRING']));
	}
}