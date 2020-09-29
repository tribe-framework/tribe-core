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
    $i=0;
	foreach ($emails as $email_row) {
        if (!$i)
            $fields=explode(',', '{'.implode('},{', array_keys($email_row)).'}');
        $mail_arr=$mailr;
		$mail_arr['to_email']=$email_row['email'];
        if ($fields['link'])
            $mail_arr['body_html']=str_replace($fields, $email_row, $mailr['body_html']);
        else
            $mail_arr['body_html']=$mailr['link_html'].$mailr['body_html'];
		send_email($mail_arr);
        $i++;
	}
}

?>