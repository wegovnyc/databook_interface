<?php
Namespace App\Custom;

class UnDatasets
{
	public $dd = [
		'civil-service-titles' => [
			'fullname' => 'NYC Civil Service Titles',
			'table' => 'nyccivilservicetitles',
			'hdrs' => ['Title Code', 'Title Description', 'Standard Hours', 'Assignment Level', 'Bargaining Unit', 'Union Description', 'Minimum Salary', 'Maximum Salary'],
			'visible' => [true, true, true, true, false, true, true, true],
			'flds' => [
					'function (r) { return `<a href="/titles/${r["Title Code"]}">${r["Title Code"]}</a>` }',
					'"Title Description"',
					'"Standard Hours"',
					'"Assignment Level"',
					'function (r) { return r["wegov-org-id"] 
									? `<a href="/organization/${r["wegov-org-id"]}">${r["wegov-org-name"]}</a>` 
									: `<a disabled>${r["Bargaining Unit Description"]}</a>` 
					}',
					'"Union Description"',
					'function (r) { return toFin(r["Minimum Salary Rate"]) }',
					'function (r) { return toFin(r["Maximum Salary Rate"]) }'
			],
			'filters' => [],
			'details' => [],
		],
	];

	public $list = [
		#'about' => 'About',
		'civil-service-titles' => 'Civil Service Titles',
	];
	
	public $menu = [
		#'about',
		'civil-service-titles'
	];
	
	public function menuActiveDD($sect)
	{
		foreach ($this->menu as $h=>$items)
			if (is_array($items) && (array_search($sect, $items) !== false))
				return $h;
		return '';
	}
	
	public $socicons = [
		'main_address' => ['geo-alt-fill', 'https://www.google.com/maps?q='],
		'email' => ['envelope', 'mailto:'],
		'url' => ['link-45deg', ''],
		'twitter' => ['twitter', ''],
		'facebook' => ['facebook', ''],
		'main_phone' => ['telephone', 'tel:'],
		'main_fax' => ['printer', 'fax:'],
		'rss' => ['rss', ''],
		'ical' => ['calendar-event-fill', ''],
	];
	
	public function get($section)
	{
		$dd = $this->dd[strtolower($section)] ?? null;
		if (!$dd)
			return $dd;
		$dd['detFlag'] = $inc = $dd['details'] ?? null ? 1 : 0;
		$flts = [];
		foreach ((array)$dd['filters'] as $i=>$v)
			$flts[$i + $inc] = $v;
		$dd['filters'] = $flts;
		
		$fltDel = [];
		foreach ((array)($dd['fltDelim'] ?? []) as $i=>$v)
			$fltDel[$i + $inc] = $v;
		$dd['fltDelim'] = $fltDel;

		$dd['fltsCols'] = implode(',', array_keys($dd['filters']));
		return $dd;
	}
}