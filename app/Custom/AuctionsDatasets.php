<?php
Namespace App\Custom;

class AuctionsDatasets
{
	public $dd = [
		'auctions' => [
			'fullname' => 'Auctions',
			'title' => 'Auctions',
			'sql' => 'SELECT * FROM auctions WHERE date("Auction Ends") >= date(now()) ORDER BY "Auction Ends"',
			'hdrs' => ['Title', 'Time Left', 'Auction Ends', 'Current Price', '# of Bids', 'Seller', 'Description'],
			'flds' => [
					'function (r) { return `<a href="${r["URL"]}" target="_blank">${r["Title"]}</a>` }',
					'"Time Left"', 
					'function (r) { return r["Auction Ends"].replace("T", " ").replace(".000Z", "") }',
					'"Current Price"', '"# of Bids"', '"Seller"', 
					'function (r) { return r["Description"].substr(0, 200).replace(/[\r\n]+/g, "<br/>") + "..." }'
				],
			'visible' => [true, true, true, true, true, true, true],
			'filters' => [],
			'details' => [],
			'description' => '',
			'script' => '',
		],
	];

	public $list = [
		'news' => 'News',
		'events' => 'Events',
		'publichearings' => 'Public Hearings',
		'contractawards' => 'Contract Awards',
		'specialmaterials' => 'Special Materials',
		'agencyrules' => 'Agency Rules',
		'propertydisposition' => 'Property Disposition',
		'courtnotices' => 'Court Notices',
		'procurement' => 'Procurement',
		'changeofpersonnel' => 'Change in Personnel',
	];
	
	public $menu = [
		'news',
		'events',
		'publichearings',
		'contractawards',
		'specialmaterials',
		'agencyrules',
		'propertydisposition',
		'courtnotices',
		'procurement',
		'changeofpersonnel',
	];
	
	public function menuActiveDD($sect)
	{
		foreach ($this->menu as $h=>$items)
			if (is_array($items) && (array_search($sect, $items) !== false))
				return $h;
		return '';
	}
	
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