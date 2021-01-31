<?php
Namespace App\Custom;

class NewsletterAPI
{
	static function subscribe($req)
	{
		if (
			($req['key'] ?? null) == config('apis.app_key')
			&& preg_match('~\w[\w\.-_]+@(\w[-_\w]+\.){1,3}[a-zA-Z]+~si', $req['email'])
		)
			return json_encode(self::subsReq($req['email']));
		else
			return json_encode(['success' => false]);
	}
	
	
	static function subsReq($email)
	{
		$dd = [
			['fields' => [
					'Email' => $email
				]
			]
		];
		
		$hh = ['Authorization: Bearer ' . config('apis.airtable_key')];
		$url = sprintf(
						'https://api.airtable.com/v0/%s/%s', 
						config('apis.newsletter_doc_id'),
						'Users'
					);
		$resp = Curl2::exec($url, 'post', [CURLOPT_HTTPHEADER => $hh], '', ['records' => $dd]);
		$rr = json_decode($resp, true);
		return isset($rr['records']) 
				? ['success' => true]
				: ['success' => false, 'resp' => $rr];
	}
}