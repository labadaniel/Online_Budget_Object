<?php

namespace App;

use App\Config;
 use \Mailjet\Resources;

/**
 * Mail
 *
 * PHP version 7.0
 */
class Mail
{

    /**
     * Send a message
     *
     * @param string $to Recipient
     * @param string $subject Subject
     * @param string $text Text-only content of the message
     * @param string $html HTML content of the message
     *
     * @return mixed
     */
    public static function send($to, $subject, $text, $html)
    {
        $mj = new \Mailjet\Client(Config::API_KEY, CONFIG::SECRET_KEY_MAIL,true,['version' => 'v3.1']);
	  $body = [
		'Messages' => [
		  [
			'From' => [
			  'Email' => "laba.daniel@gmail.com",
			  'Name' => "Daniel"
			],
			'To' => [
			  [
				'Email' => $to,
				'Name' => "You"
			  ]
			],
			'Subject' => $subject,
			'TextPart' => $text,
			'HTMLPart' => $html
		  ]
		]
	  ];
	  $response = $mj->post(Resources::$Email, ['body' => $body]);

	}
}