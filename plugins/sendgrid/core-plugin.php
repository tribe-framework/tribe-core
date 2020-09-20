<?php

function send_email ($mailr=array()) {
  if ($mailr['to_email'] && ABSOLUTE_PATH && CONTACT_EMAIL && WEBSITE_NAME && SENDGRID_API_KEY) {
    require_once ABSOLUTE_PATH.'/plugins/sendgrid/sendgrid-php.php';
    $email = new \SendGrid\Mail\Mail(); 
    $email->setFrom(CONTACT_EMAIL, WEBSITE_NAME);
    $email->setSubject($mailr['subject']);
    $email->addTo($mailr['to_email'], ($mailr['to_name']??''));
    if (isset($mailr['body_text']))
	    $email->addContent("text/plain", $mailr['body_text']);
    $email->addContent("text/html", $mailr['body_html']);
    $sendgrid = new \SendGrid(SENDGRID_API_KEY);
    $response = $sendgrid->send($email);
  }
}

function send_email_to_json_list ($filepath, $mailr=array()) {
	$emails=json_decode(file_get_contents($filepath), true);
	foreach ($emails as $email) {
		$mailr['to_email']=$email;
		send_email($mailr);
	}
}

?>