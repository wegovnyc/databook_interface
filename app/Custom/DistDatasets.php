<?php
Namespace App\Custom;

class DistDatasets
{
	public $dd = [
		'requests' => [
			'fullname' => 'Register of Community Board Budget Requests API',
			'table' => 'budgetrequestsregister',
			'hdrs' => ['Publication Date', 'Borough', 'C Board', 'Priority', 'Tracking Code', 'Request', 'Council District', 'NTA'],
			'visible' => [true, true, true, true, true, true, false, false],
			'flds' => [ //'"Publication"', 
						'function (r) { return toDashDate(r["Publication"]) }', 
						'"Borough"', '"Community Board"', '"Priority"', '"Tracking  Code"', '"Request"', '"wegov-cd-id"', '"wegov-nta-code"'], 
			'sort' => ['"Publication"', '"Borough"',],
			'filters' => [0 => '2020-07-01', 1 => null, 2 => null, 3 => null],
			'details' => [
						'Explanation' => 'Explanation',
						'Response' => 'Response',
						'Responded By' => 'Responded By',
						'Responsible Agency' => 'Responsible Agency',
						'Site Street' => 'Site Street',
						'Number' => 'Number',
						'Street' => 'Street',
						'Block' => 'Block',
						'Lot' => 'Lot',
						'Postcode' => 'Postcode',
						'Council District' => 'Council District',
						'BIN' => 'BIN',
						'BBL' => 'BBL',
						'NTA' => 'NTA'			
			],
			'map' => ['cd' => 'wegov-comd-id', 'cc' => 'wegov-cd-id', 'nta' => 'wegov-nta-code'],
		],
		
		'facilities' => [
			'fullname' => 'NYC Facilities Database',
			'table' => 'facilitydb',
			'hdrs' => ['Name', 'Category', 'Group', 'Subgroup', 'Address', 'Borough', 'Council District', 'NTA'],
			'visible' => [true, true, true, true, true, true, false, false],
			'flds' => ['"facname"', '"facdomain"', '"facgroup"', '"facsubgrp"', '"address"', '"borough"', '"wegov-cd-id"', '"wegov-nta-code"'], 
			'sort' => ['"facname"', '"facdomain"'],
			'filters' => [1 => null, 2 => null, 3 => null, 5 => null],
			'details' => [
					'Number' => 'number',
					'Street' => 'street',
					'City' => 'city',
					'Zipcode' => 'postcode',
					'Latitude' => 'latitude',
					'Longitude' => 'longitude',
					'BIN' => 'bin',
					'BBL' => 'bbl',
					'Community Board' => 'community board',
					'Council District' => 'council district',
					'Neighborhood' => 'nta',
					'Facility Type' => 'factype',
					'Capacity' => 'capacity',
					'Capital Type' => 'captype',
					'Property Type' => 'proptype'			
			],
			'description' => 'The Facilities Database (FacDB) captures the locations and descriptions of public and private facilities ranging from the provision of social services, recreation, education, to solid waste management.',
			'map' => ['cd' => 'wegov-comd-id', 'cc' => 'wegov-cd-id', 'nta' => 'wegov-nta-code'],	
		],

		'nyccouncildiscretionaryfunding' => [
			'fullname' => 'New York City Council Discretionary Funding',
			'table' => 'nyccouncildiscretionaryfunding',
			'hdrs' => ['Fiscal Year', 'Source', 'Council Member', 'Legal Name of Organization', 'Status', 'Amount ($)', 'Borough', 'Council District', 'NTA'],
			'visible' => [true, true, true, true, true, true, true, true, false],
			'flds' => [
					'"Fiscal Year"', '"Source"', '"Council Member"', 
					'function (r) { return `<a href="https://projects.propublica.org/nonprofits/organizations/${r["EIN"]}" target="_blank">${r["Legal Name of Organization"]}</a>` }',
					'"Status"', '"Amount ($)"', '"Borough"', '"Council District"', '"wegov-nta-code"'
				], 
			'sort' => ['"Fiscal Year"', '"Source"'],
			'filters' => [0 => null, 1 => null, 2 => null, 6 => null, 7 => null],
			//'fltDelim' => [3 => ','],
			'details' => [
				'EIN' => 'EIN',
				'MOCS ID' => 'MOCS ID',
				'Program Name' => 'Program Name',
				'Address' => 'Address',
				'Address 2 (optional)' => 'Address 2 (optional)',
				'City' => 'City',
				'State' => 'State',
				'Postcode' => 'Postcode',
				'Purpose of Funds' => 'Purpose of Funds',
				'Fiscal Conduit Name' => 'Fiscal Conduit Name',
				'FC EIN' => 'FC EIN',
				'Latitude' => 'Latitude',
				'Longitude' => 'Longitude',
				'Community Board' => 'Community Board',
				'Census Tract' => 'Census Tract',
				'BIN' => 'BIN',
				'BBL' => 'BBL',
				'NTA' => 'NTA',
			],
			'description' => 'The dataset reflects applications for discretionary funding to be allocated by the New York City Council.',
			'map' => ['cd' => 'wegov-comd-id', 'cc' => 'Council District', 'nta' => 'wegov-nta-code'],
		],

		'capitalprojects' => [
			'fullname' => 'Capital Project Detail Data - Dollars',
			'table' => 'capitalprojectsdollarscomp',
			'description' => 'This dataset contains capital commitment plan data by project type, budget line and source of funds. The dollar values are in thousands. The dataset is updated three times a year during the Preliminary, Executive and Adopted Capital Commitment Plans.',
			'hdrs' => ['Publication Date', 'Project ID', 'Name', 'Scope', 'Category', 'Borough', 'Planned Cost', 'Budget Increase', 'Timeline Change'],
			'visible' => [false, true, true, true, true, true, true, true, true],
			'hide_on_map_open' => '0, 4, 6, 7, 8',		// +1 for details fld is already added
			'flds' => [
					'function (r) { return toDashDate(r["PUB_DATE"]) }',
					'function (r) { return `<a href="/capitalprojects/${r.PROJECT_ID}">${r.PROJECT_ID}</a>` }', 
					'"PROJECT_DESCR"', '"SCOPE_TEXT"', '"TYP_CATEGORY_NAME"', 
					'"BORO"', 
					'function (r) { return `<span data-content="${toFin(r["BUDG_ORIG"], 1000)}">${toFinShortK(r["BUDG_ORIG"], 1000)}</span>` }',
					'function (r) { 
						if (!r["ORIG_BUD_AMT"])
							return "NA"
						return r["BUDG_DIFF"] == 0 ? "0" :
							(r["BUDG_DIFF"] > 0 
								? `<span class="good" data-content="-${toFin(r["BUDG_DIFF"], 1000)}">-${toFinShortK(r["BUDG_DIFF"], 1000)}</span>` 
								: `<span class="bad" data-content="${toFin(-r["BUDG_DIFF"], 1000)}">${toFinShortK(-r["BUDG_DIFF"], 1000)}</span>`);
					}',
					'function (r) { 
						if ((r["END_DIFF"] == "-") || (r["END_DIFF"] == "12/31/1969"))
							return "NA"
						var v = parseFloat(r["END_DIFF"]).toFixed(1).toString()
						if (v < 0)
							return `<span class="bad">${-v} years late</span>`
						return v > 0 ? `<span class="good">${v} years early</span>` : `<span class="good">on time</span>`;
					}'
				],
			'filters' => [/*2 => null, */4 => null],
			'details' => [
					'Original Budget' => '`<span data-content="${toFin(r["BUDG_ORIG"], 1000)}">${toFinShortK(r["BUDG_ORIG"], 1000)}</span>`',
					'Prior Spending' =>  '`<span data-content="${toFin(r["CITY_PRIOR_ACTUAL"], 1000)}">${toFinShortK(r["CITY_PRIOR_ACTUAL"], 1000)}</span>`', 
					'Planned Spending' => '`<span data-content="${toFin(r["CITY_PLAN_TOTAL"], 1000)}">${toFinShortK(r["CITY_PLAN_TOTAL"], 1000)}</span>`',
					'Community Boards Served' => 'r["COMMUNITY_BOARD"]',
					'Budget Lines' => 'r["BUDGET_LINE"]',
					'Site Description' => 'r["SITE_DESCR"]',
					'Explanation for Delay' => 'r["DELAY_DESC"]',
			],
			'order' => [[8, 'asc']],					#7 - wo details col inrement
			'map' => ['cd' => 'wegov-comd-id', 'cc' => 'Council District', 'nta' => 'wegov-nta-code'],
		],
		
	];

	// single level list 'dataset name' => 'dataset title'
	public $list = [
		'nyccouncildiscretionaryfunding' => 'City Council Discretionary',
		'capitalprojects' => 'Projects',
		'requests' => 'Requests',
		'facilities' => 'Facilities',
	];
	
	// multi level list 'menu dropdown title (or zero if single level item)' => ['dataset name', ...]
	public $menu = [
		'nyccouncildiscretionaryfunding',
		'capitalprojects',
		'requests',
		'facilities',
	];
	
	public function menu($type)
	{
		$rr = [];
		foreach ($this->menu as $h=>$vv)
			if (is_array($vv))
			{
				foreach ($vv as $i=>$v)
					if (isset($this->dd[$v]['map'][$type]))
						$rr[$h][$i] = $v;
			} elseif (isset($this->dd[$vv]['map'][$type]))
				$rr[$h] = $vv;
		return $rr;
	}
	
	public function menuActiveDD($sect)
	{
		foreach ($this->menu as $h=>$items)
			if (is_array($items) && (array_search($sect, $items) !== false))
				return $h;
		return '';
	}
	
	public function get($section, $type)
	{
		$dd = $this->dd[strtolower($section)] ?? null;
		if (!$dd)
			return $dd;
		if (!isset($dd['map']) || !isset($dd['map'][$type]))
			return null;
		
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