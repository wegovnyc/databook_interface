<?php
Namespace App\Custom;

class Datasets
{
	public $dd = [
		'expense-budget' => [
			'fullname' => 'Expense Budget on NYC Open Data',
			'table' => 'expensebudgetonnycopendata',					// Carto table
			'hdrs' => ['Publication Date', 'Fiscal Year', 'Budget Code Name', 'Object Class Name', 'Object Code Name', 'Adopted Budget Amount', 'Current Modified Budget Amount', 'Financial Plan Amount'],										// datatables header
			'visible' => [true, true, true, true, true, true, true, true],	// column visibility
			'flds' => [
					//'"Publication Date"', 
					'function (r) { return toDashDate(r["Publication Date"]) }', 
					'"Fiscal Year"', '"Budget Code Name"', '"Object Class Name"', '"Object Code Name"', 
					//'"Adopted Budget Amount"', 
					'function (r) { return toFin(r["Adopted Budget Amount"]) }',
					//'"Current Modified Budget Amount"', 
					'function (r) { return toFin(r["Current Modified Budget Amount"]) }',
					//'"Financial Plan Amount"'
					'function (r) { return toFin(r["Financial Plan Amount"]) }',
			],
																		// datatables data source/js fetch function
			'filters' => [0 => '2019-06-19', 1 => '2020'],				// filters - fld no => def value or null if empty
			'details' => [												// additional details fields
					'Adopted Budget Position' => 'Adopted Budget Position',
					'Current Modified Budget Position' => 'Current Modified Budget Position',
					'Financial Plan Position' => 'Financial Plan Position',
					'Adopted Budget - Number of Contracts' => 'Adopted Budget - Number of Contracts',
					'Current Modified Budget - Number of Contracts' => 'Current Modified Budget - Number of Contracts',
					'Unit Appropriation Number' => 'Unit Appropriation Number',
					'Unit Appropriation Name' => 'Unit Appropriation Name',
					'Budget Code Number' => 'Budget Code Number',
					'Object Class Number' => 'Object Class Number',
					'Object Code' => 'Object Code',
					'Responsibility Center Name' => 'Responsibility Center Name',
					'Responsibility Center Code' => 'Responsibility Center Code',
					'Intra-City Purchase Code' => 'Intra-City Purchase Code',
					'Personal Service/Other Than Personal Service Indicator' => 'Personal Service/Other Than Personal Service Indicator',
					'Financial Plan Savings Flag' => 'Financial Plan Savings Flag',
					'Financial Plan - Number of Contracts' => 'Financial Plan - Number of Contracts'
			],
		],
		'capital-projects' => [
			'fullname' => 'NYC Capital Project Detail Data',
			'table' => 'capitalprojects',
			'hdrs' => ['Project ID', 'Name', 'Scope', 'Borough', 'Original Budget', 'Prior Spending', 'Planned Spending', 'Category'],
			'visible' => [true, true, true, true, true, true, true, true],
			'flds' => [
					'function (r) { return `<a href="https://amazing-ptolemy-08d008.netlify.app/project/${r.project_id}">${r.project_id}</a>` }', 
					'"project_description"', '"scope_summary"', '"borough"', 
					//'"original_budget"', 
					'function (r) { return toFin(r["original_budget"]) }',
					//'"combined_prior_actuals"', 
					'function (r) { return toFin(r["combined_prior_actuals"]) }',
					//'"city2021"', 
					'function (r) { return toFin(r["city2021"]) }',
					'"ten_year_plan_category"'
				],
			'filters' => [3 => null, 7 => null],
			'details' => [
					'Explanation for Delay' => 'explanation_for_delay',
					'Project Location' => 'project_location',
					'Community Boards Served' => 'community_boards_served',
					'Budget Lines' => 'budget_lines'			
			],
		],
		'services' => [
			'fullname' => 'Benefits and Programs API on NYC Open Data',
			'table' => 'benefitsapi',
			'hdrs' => ['Program Name', 'Category', 'Blurb', 'Eligibility'], 
			'visible' => [true, true, true, true],
			'flds' => ['"program_name"', '"program_category"', '"brief_excerpt"', '"population_served"'], 
			'filters' => [1 => null, 3 => null],
			'details' => [
					'Description' => 'program_description',
					'Eligibility' => 'plain_language_eligibility',
					'Get Help' => 'get_help_summary',
					'Age Groups' => 'age_group',
					'How to Apply' => 'how_to_apply_summary',
					'Required Documents' => 'required_documents_summary',
					'Languages' => 'language',
					'Apply Online' => 'how_to_apply_or_enroll_online',
					'Get Help Online' => 'get_help_online',
					'Link to Online Applications' => 'url_of_online_application',
					'Link to PDFs Applications' => 'url_of_pdf_application_forms',
					'More Info' => 'office_locations_url'			
			],
		],
		'people' => [
			'fullname' => 'Greenbook on NYC Open Data',
			'table' => 'nycgreenbook',
			'hdrs' => ['First Name', 'Middle Initial', 'Last Name', 'Name Suffix', 'Office Title', 'Division Name', 'Phone 1', 'Phone 2'], 
			'visible' => [true, false, true, false, true, true, true, true],
			'flds' => ['"First Name"', '"Middle Initial"', '"Last Name"', '"Name Suffix"', '"Office Title"', '"Division Name"', '"Phone 1"', '"Phone 2"'], 
			'filters' => [],
			'details' => [
					'Parent Division' => 'Parent Division',
					'Grand Parent Division' => 'Grand Parent Division',
					'Great Grand Parent Division' => 'Great Grand Parent Division',
					'Address' => 'Address',
					'City' => 'City',
					'State' => 'State',
					'Zip Code' => 'Zip Code',
					'Fax 1' => 'Fax 1',
					'Fax 2' => 'Fax 2',
					'Agency Primary Phone' => 'Agency Primary Phone',
					'Division Primary Phone' => 'Division Primary Phone',
					'Section' => 'Section'
			],
		],
		'indicators-mmr' => [
			'fullname' => 'Agency Performance Mapping Indicators – Annual',
			'table' => 'agencypmi',
			'hdrs' => ['Geographic Unit', 'Geo ID', 'Indicator', 'FY11', 'FY12', 'FY13', 'FY14', 'FY15', 'FY16', 'FY17', 'FY18', 'FY19'], 
			'visible' => [true, true, true, false, false, false, false, false, true, true, true, true],
			'flds' => ['"Geographic Unit"', '"Geographic Identifier"', '"Indicator"',
						'function (r) { return +parseFloat(r["FY2011"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2012"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2013"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2014"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2015"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2016"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2017"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2018"]).toFixed(2) }',
						'function (r) { return +parseFloat(r["FY2019"]).toFixed(2) }'
					],
			'filters' => [0 => null, 1 => null],
			'details' => [],
		],
		'requests' => [
			'fullname' => 'Register of Community Board Budget Requests API',
			'table' => 'budgetrequestsregister',
			'hdrs' => ['Pub Date', 'Borough', 'CB', 'Priority', 'Tracking Code', 'Request'], 
			'visible' => [true, true, true, true, true, true],
			'flds' => [ //'"Publication"', 
						'function (r) { return toDashDate(r["Publication"]) }', 
						'"Borough"', '"Community Board"', '"Priority"', '"Tracking  Code"', '"Request"'], 
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
		],
		'jobs' => [
			'fullname' => 'NYC Jobs',
			'table' => 'nycjobs',
			'hdrs' => ['Job ID', 'Title', 'Job Category', 'Posting Type', 'Salary From', 'Salary To', 'Last Updated', 'Posting Date'], 
			'flds' => [
					'function (r) { return `<a href="https://a127-jobs.nyc.gov/index_new.html?keyword=${r["Job ID"]}">${r["Job ID"]}</a>` }', 
					//'"Job ID"', 
					'"Business Title"', '"Job Category"', '"Posting Type"', 
					//'"Salary Range From"', 
					'function (r) { return toFin(r["Salary Range From"]) }',
					//'"Salary Range To"', 
					'function (r) { return toFin(r["Salary Range To"]) }',
					//'"Posting Updated"', 
					'function (r) { return usToDashDate(r["Posting Updated"]) }', 
					//'"Posting Date"'
					'function (r) { return usToDashDate(r["Posting Date"]) }', 
				], 
			'visible' => [true, true, false, false, true, true, true, false],
			'filters' => [2 => null, 3 => null],
			'details' => [
					'# Of Positions' => '# Of Positions',
					'Civil Service Title' => 'Civil Service Title',
					'Title Classification' => 'Title Classification',
					'Title Code No' => 'Title Code No',
					'Level' => 'Level',
					'Full-Time/Part-Time indicator' => 'Full-Time/Part-Time indicator',
					'Career Level' => 'Career Level',
					'Work Location' => 'Work Location',
					'Division/Work Unit' => 'Division/Work Unit',
					'Job Description' => 'Job Description',
					'Minimum Qual Requirements' => 'Minimum Qual Requirements',
					'Preferred Skills' => 'Preferred Skills',
					'Additional Information' => 'Additional Information',
					'To Apply' => 'To Apply',
					'Residency Requirement' => 'Residency Requirement'			
				],
		],
		'facilities' => [
			'fullname' => 'NYC Facilities Database',
			'table' => 'facilitydb',
			'hdrs' => ['Name', 'Category', 'Group', 'Subgroup', 'Address', 'Borough'],
			'visible' => [true, true, true, true, true, true],
			'flds' => ['"facname"', '"facdomain"', '"facgroup"', '"facsubgrp"', '"address"', '"borough"'], 
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
		],
		'' => [
			'fullname' => '',
			'table' => '',
			'hdrs' => [], 
			'flds' => [], 
			'filters' => [],
			'details' => [],
		],
	];

	public $list = [
		'about' => 'About',
		'expense-budget' => 'Expenses',
		'capital-projects' => 'Projects',
		'services' => 'Services',
		'people' => 'People',
		'indicators-mmr' => 'Indicators',
		'requests' => 'Requests',
		'jobs' => 'Jobs',
		'facilities' => 'Facilities',
	];
	
	public $socicons = [
		'email' => ['envelope', 'mailto:'],
		'url' => ['link-45deg', ''],
		'Twitter' => ['twitter', ''],
		'Facebook' => ['facebook', ''],
		'main_phone' => ['telephone', 'tel:'],
		'main_fax' => ['printer', 'fax:'],
		'RSS' => ['rss', ''],
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
		$dd['fltsCols'] = implode(',', array_keys($dd['filters']));
		return $dd;
	}
}