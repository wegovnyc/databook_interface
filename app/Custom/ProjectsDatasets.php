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
			'hdrs' => ['Publication Date', 'Project ID', 'Agency', 'Name', 'Category', 'Borough', 'Current Budget', 'Budget Change (%)', 'Timeline Change'],
			'visible' => [false, true, true, true, true, true, true, true, true],
			'hide_on_map_open' => '0, 5, 6, 7, 8',
			'flds' => [
					'function (r) { return toDashDate(r["PUB_DATE"]) }',
					'function (r) { return `<a href="/capitalprojects/${r.PROJECT_ID}">${r.PROJECT_ID}</a>` }', 
					'function (r) { return `<a href="/agency/${r["wegov-org-id"]}/capitalprojects">${r["wegov-org-name"]}</a>` }', 
					'"PROJECT_DESCR"', '"TYP_CATEGORY_NAME"', 
					'"BORO"', 
					'function (r) { return `<span data-content="${toFin(r["BUDG_CURR"], 1000)}">${toFinShortK(r["BUDG_CURR"], 1000)}</span>` }',
					'function (r) { return `<span class="${r["BUDG_ORIG"] >= r["BUDG_CURR"] ? "good" : "bad "}">${toPerc(r["BUDG_ORIG"], r["BUDG_CURR"])}</span>` }',
					'function (r) { 
						if ((r["END_DIFF"] == "-") || (r["END_DIFF"] == "12/31/1969"))
							return "NA"
						var v = parseFloat(r["END_DIFF"]).toFixed(1).toString()
						if (v < 0)
							return `<span class="bad">${-v} years late</span>`
						return v > 0 ? `<span class="good">${v} years early</span>` : `<span class="good">on time</span>`;
					}'
				],
			'filters' => [2 => null, 4 => null],
			'details' => [
					'Planned Cost' => '`<span data-content="${toFin(r["BUDG_ORIG"], 1000)}">${toFinShortK(r["BUDG_ORIG"], 1000)}</span>`', 
					'Budget Increase' => '(!r["ORIG_BUD_AMT"] 
							? "NA" 
							: (r["BUDG_DIFF"] == 0 
								? "0" 
								: (r["BUDG_DIFF"] > 0 
									? "-" 
									: `${toFinShortK(-r["BUDG_DIFF"], 1000)}`
								)
							)
					)', 
					'Original Budget' => '`<span data-content="${toFin(r["BUDG_ORIG"], 1000)}">${toFinShortK(r["BUDG_ORIG"], 1000)}</span>`',
					'Prior Spending' =>  '`<span data-content="${toFin(r["CITY_PRIOR_ACTUAL"], 1000)}">${toFinShortK(r["CITY_PRIOR_ACTUAL"], 1000)}</span>`', 
					'Planned Spending' => '`<span data-content="${toFin(r["CITY_PLAN_TOTAL"], 1000)}">${toFinShortK(r["CITY_PLAN_TOTAL"], 1000)}</span>`',
					'Community Boards Served' => 'r["COMMUNITY_BOARD"]',
					'Budget Lines' => 'r["BUDGET_LINE"]',
					'Site Description' => 'r["SITE_DESCR"]',
					'Explanation for Delay' => 'r["DELAY_DESC"]',
			],
			'order' => [[8, 'desc']],					#7 - wo details col inrement
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