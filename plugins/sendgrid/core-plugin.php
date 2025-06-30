<?php
$mailer = new CoreSendGrid();

class CoreSendGrid {

	function send_email($mailr = array(), $show_errors = 0) {
		if (isset($mailr['to_email']) && defined('ABSOLUTE_PATH') && defined('CONTACT_EMAIL') && defined('CONTACT_NAME') && defined('SENDGRID_API_KEY')) {
			require_once __DIR__ . '/sendgrid-php.php';
			$email = new \SendGrid\Mail\Mail();
			$email->setFrom(CONTACT_EMAIL, CONTACT_NAME);
			$email->setSubject($mailr['subject']);
			$email->addTo($mailr['to_email'], ($mailr['to_name'] ?? ''));
			if (isset($mailr['body_text'])) {
				$email->addContent("text/plain", $mailr['body_text']);
			}

			$email->addContent("text/html", $mailr['body_html']);
			$sendgrid = new \SendGrid(SENDGRID_API_KEY);
			if ($show_errors) {
				try {
					$response = $sendgrid->send($email);
					print $response->statusCode() . "\n";
					print_r($response->headers());
					print $response->body() . "\n";
				} catch (Exception $e) {
					echo 'Caught exception: ' . $e->getMessage() . "\n";
				}
			} else {
				$response = $sendgrid->send($email);
			}

		}
	}

	function send_email_to_json_list($filepath, $mailr = array()) {
		$emails = json_decode(file_get_contents($filepath), true);
		$i = 0;
		foreach ($emails as $email_row) {
			if (!$i) {
				$fields = explode(',', '{' . implode('},{', array_keys($email_row)) . '}');
			}

			$mail_arr = $mailr;
			$mail_arr['to_email'] = $email_row['email'];
			if ($email_row['link']) {
				$mail_arr['body_html'] = str_replace($fields, $email_row, $mailr['body_html']);
			} else {
				$mail_arr['body_html'] = $mailr['link_html'] . $mailr['body_html'];
			}

			if ($mail_arr['to_email']) {
				$this->send_email($mail_arr);
			}

			$i++;
		}
	}

}
?>