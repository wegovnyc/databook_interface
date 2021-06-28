<?php
Namespace App\Custom;

class ProjectsDatasets
{
	public $dd = [
		'main' => [
			'name' => 'Capital Projects',
			'fullname' => 'Capital Project Detail Data - Dollars',
			'description' => 'This dataset contains capital commitment plan data by project type, budget line and source of funds. The dollar values are in thousands. The dataset is updated three times a year during the Preliminary, Executive and Adopted Capital Commitment Plans.',
			'table' => 'capitalprojectsdollars',					// Carto table
			'hdrs' => ['Publication Date', 'Project ID', 'Agency', 'Name', 'Scope', 'Category', 'Borough', 'Planned Cost', 'Budget Status'],
			'visible' => [true, true, true, true, true, true, true, true, true],
			'flds' => [
					'function (r) { return toDashDate(r["PUB_DATE"]) }',
					'function (r) { return `<a href="/agency/${r["wegov-org-id"]}/capitalprojects/${r.PROJECT_ID}">${r.PROJECT_ID}</a>` }', 
					'function (r) { return `<a href="/agency/${r["wegov-org-id"]}/capitalprojects">${r["wegov-org-name"]}</a>` }', 
					'"PROJECT_DESCR"', '"SCOPE_TEXT"', '"TYP_CATEGORY_NAME"', 
					'"BORO"', 
					'function (r) { return toFin(r["CITY_PRIOR_ACTUAL"] + r["CITY_PLAN_TOTAL"]) }',
					'function (r) { 
						if (!r["ORIG_BUD_AMT"])
							return "NA"
						r = r["ORIG_BUD_AMT"] - r["CITY_PRIOR_ACTUAL"] - r["CITY_PLAN_TOTAL"]
						return r >= 0 ? toFin(r) : `<span class="bad">${toFin(r)}</bad>`;
					}'
				],
			'filters' => [2 => null, 5 => null],
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