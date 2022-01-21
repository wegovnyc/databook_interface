<?php	
Namespace App\Custom;

class Curl2
{
	public static $curl_options = 
		array(
			CURLOPT_RETURNTRANSFER    => 1,
			CURLOPT_BINARYTRANSFER    => 1,
			CURLOPT_CONNECTTIMEOUT    => 30,
			CURLOPT_TIMEOUT            => 600,
			CURLOPT_USERAGENT        => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36 OPR/26.0.1656.60',
			CURLOPT_VERBOSE            => 0,
			CURLOPT_HEADER            => 0,
			CURLOPT_FOLLOWLOCATION    => 1,
			CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_0,  // лечение от transfer closed with 10088805 bytes remaining to read
			CURLOPT_SSL_VERIFYPEER    => 0,
			CURLOPT_SSL_VERIFYHOST    => 0,
			CURLOPT_MAXREDIRS        => 7, 
			CURLOPT_AUTOREFERER        => 1,
			CURLOPT_HTTPHEADER        => array(
				"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
				"Accept-Language: en-US,en;q=0.5",
				"Connection: keep-alive",
			)
		);

	public static function exec($url, $type='get', $cParam=[], $data='', $json='', &$redirect=null)
	{
		$ch = self::init($url, $type, $cParam, $data, $json);
		$res = curl_exec($ch);
		if (curl_errno ($ch)) 
		{
			curl_close($ch);
			return false;
		}
		$redirect = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		curl_close($ch);
		return $res;
	}
	
	public static function init($url, $type='get', $cParam=[], $data='', $json='')
	{
		$ch = curl_init();
		$options = (array)$cParam + (array)self::$curl_options;
		$options[CURLOPT_URL] = $url;
		if (!isset($options[CURLOPT_USERAGENT]))
			$options[CURLOPT_USERAGENT] = self::pickUserAgent();
			
		if (strtolower($type) == 'get')
		{
			$options[CURLOPT_POST] = 0;
		}
		else
		{
			$options[CURLOPT_POST] = 1; 				
			$options[CURLOPT_CUSTOMREQUEST] = strtoupper($type);
			if ($data && $json)
				$data = null;
			if ($data)
			{
				if (is_array($data))
					$data = http_build_query($data);
				$options[CURLOPT_HTTPHEADER][] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8"; 
				$options[CURLOPT_HTTPHEADER][] = "Content-Length: " . strlen($data); 
				$options[CURLOPT_POSTFIELDS] = $data;
			}
			if ($json)
			{
				if (is_array($json))
					$json = json_encode($json);
				$options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
				$options[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($json);
				$options[CURLOPT_POSTFIELDS] = $json;
			}
		}
		curl_setopt_array($ch, $options);
		return $ch;
	}
}