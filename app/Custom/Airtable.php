<?php
Namespace App\Custom;
Use App\Custom\Curl2 as Curl;
class Airtable
{
	public $id;
	public $key;
	
	function __construct($id, $key)
	{
		$this->id = $id;
		$this->key = $key;
	}
	
	function get($tbl, $id)
	{
		$hh = ["Authorization: Bearer " . $this->key];
		$url = sprintf(
						'https://api.airtable.com/v0/%s/%s/%s', 
						$this->id,
						rawurlencode($tbl),
						$id
					);
		$resp = Curl::exec($url, 'get', [CURLOPT_HTTPHEADER => $hh]);
		$rr = json_decode($resp, true);
		return $rr['id'] ? $rr : [];
	}
	
	
	function find($tbl, $req, $sort=null, $view=null)
	{
		$hh = ["Authorization: Bearer " . $this->key];
		$url = sprintf(
						'https://api.airtable.com/v0/%s/%s?filterByFormula=%s%s%s', 
						$this->id,
						rawurlencode($tbl),
						rawurlencode($req),
						($sort ? '&sort%5B0%5D%5Bfield%5D=' . rawurlencode($sort) : ''),
						($view ? '&view=' . rawurlencode($view) : '')
					);
		//echo $url . "\n";
		$resp = Curl::exec($url, 'get', [CURLOPT_HTTPHEADER => $hh]);
		return json_decode($resp, true);
	}
	
	
	function update($tbl, $id, $data)
	{
		$hh = ['Authorization: Bearer ' . $this->key, 'Content-Type: application/json'];
		$url = sprintf(
						'https://api.airtable.com/v0/%s/%s',
						$this->id,
						rawurlencode($tbl)
					);
		$dd = ['records' => [['id' => $id, 'fields' => $data]]];
		//print_r(json_encode($dd));
		$resp = Curl::exec($url, 'patch', [CURLOPT_HTTPHEADER => $hh], $dd);
		//return json_decode($resp, true)['records'][0]['id'];
		return json_decode($resp, true);
	}

	
	function del($tbl, $id)
	{
		$hh = ['Authorization: Bearer ' . $this->key, 'Content-Type: application/json'];
		$url = sprintf(
						'https://api.airtable.com/v0/%s/%s/%s',
						$this->id,
						rawurlencode($tbl),
						$id
					);
		$resp = Curl::exec($url, 'delete', [CURLOPT_HTTPHEADER => $hh, CURLOPT_CUSTOMREQUEST => 'DELETE']);
		return json_decode($resp, true);
		
	}
	
	
	function create($tbl, $data)
	{
		if (is_string(array_keys($data)[0]))
			$data = [$data];
		$dd = [];
		foreach ($data as $r)
			$dd[] = ['fields' => $r];
		$hh = ['Authorization: Bearer ' . $this->key, 'Content-Type: application/json'];
		$url = sprintf(
						'https://api.airtable.com/v0/%s/%s', 
						$this->id,
						rawurlencode($tbl)
					);
		//print_r($dd);
		$resp = Curl::exec($url, 'json', [CURLOPT_HTTPHEADER => $hh], '', ['records' => $dd]);
		$rr = json_decode($resp, true);
		return $rr;
	}

	function readTableFlat($table, $sort=null, $addon='')
	{
		$dd = $this->readTable($table, $sort, $addon);
		return self::mapFlat($dd);
	}
	
	function readTable($table, $sort=null, $addon='')
	{
		$res = [];
		$offs = null;
		while (True)
		{
			$resp = self::readTablePage($table, $offs, $sort, $addon);
			foreach ($resp['records'] as $r)
				$res[$r['id']] = $r;
			$offs = $resp['offset'] ?? null;
			if (!$offs)
				return $res;
		}
	}
	
	function readTablePage($table, $offs=null, $sort=null, $addon='')
	{
		$hh = ["Authorization: Bearer " . $this->key];
		$url = sprintf(
						'https://api.airtable.com/v0/%s/%s?pageSize=100%s%s%s', 
						$this->id,
						rawurlencode($table),
						($offs ? "&offset={$offs}" : ''),
						($sort ? '&sort%5B0%5D%5Bfield%5D=' . rawurlencode($sort) : ''),
						$addon
					);
		$resp = Curl::exec($url, 'get', [CURLOPT_HTTPHEADER => $hh]);
		return json_decode($resp, true);
	}
	
	static function mapFlat($dd)
	{
		$rr = [];
		foreach ($dd as $d)
			$rr[$d['id']] = ['_id' => $d['id']] + $d['fields'];
		return $rr;
	}
}