<?php
Namespace App\Custom;

class ProjectsDatasets
{
	public $dd = [
		'main' => [
			'name' => 'Capital Projects',
			'fullname' => 'Capital Project Detail Data - Dollars',
			'description' => 'This dataset contains capital commitment plan data by project type, budget line and source of funds. The dollar values are in thousands. The dataset is updated three times a year during the Preliminary, Executive and Adopted Capital Commitment Plans.',
			'table' => 'capitalprojectsdollarscomp',					// Carto table
			'hdrs' => ['Publication Date', 'Project ID', 'Agency', 'Name', /*'Scope', */'Category', 'Borough', 'Planned Cost', 'Budget Status', 'Timeline Status'],
			'visible' => [false, true, true, true, /*true, */true, true, true, true, true],
			'hide_on_map_open' => '0, 1, 4, 5, 7, 8',
			'flds' => [
					'function (r) { return toDashDate(r["PUB_DATE"]) }',
					'function (r) { return `<a href="/capitalprojects/${r.PROJECT_ID}">${r.PROJECT_ID}</a>` }', 
					'function (r) { return `<a href="/agency/${r["wegov-org-id"]}/capitalprojects">${r["wegov-org-name"]}</a>` }', 
					'"PROJECT_DESCR"', /*'"SCOPE_TEXT"', */'"TYP_CATEGORY_NAME"', 
					'"BORO"', 
					'function (r) { return toFin(r["BUDG_ORIG"]) }',
					'function (r) { 
						if (!r["ORIG_BUD_AMT"])
							return "NA"
						return r["BUDG_DIFF"] >= 0 ? toFin(r["BUDG_DIFF"]) : `<span class="bad">${toFin(-r["BUDG_DIFF"])}</span>`;
					}',
					'function (r) { 
						if ((r["END_DIFF"] == "-") || (r["END_DIFF"] == "12/31/1969"))
							return "NA"
						var v = parseFloat(r["END_DIFF"]).toFixed(1).toString()
						if (v < 0)
							return `<span class="bad">${-v} years late</span>`
						return v > 0 ? `<span class="good">${v} years earlier</span>` : `<span class="good">on time</span>`;
					}'
				],
			'filters' => [2 => null, 4 => null],
			'details' => [
					'Original Budget' => 'ORIG_BUD_AMT', 
					'Prior Spending' => 'CITY_PRIOR_ACTUAL', 
					'Planned Spending' => 'CITY_PLAN_TOTAL',
					'Community Boards Served' => 'COMMUNITY_BOARD',
					'Budget Lines' => 'BUDGET_LINE',
					'Site Description' => 'SITE_DESCR',
					'Explanation for Delay' => 'DELAY_DESC',
			],
		],
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