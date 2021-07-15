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
			'fltDelim' => [3 => ','],
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
			'hdrs' => ['Publication Date', 'Borough', 'C Board', 'Priority', 'Tracking Code', 'Request'], 
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
			'hdrs' => ['Job ID', 'Title', 'Job Category', 'Salary From', 'Salary To', 'Last Updated'],
			'flds' => [
					'function (r) { return `<a href="https://a127-jobs.nyc.gov/index_new.html?keyword=${r["Job ID"]}">${r["Job ID"]}</a>` }', 
					//'"Job ID"', 
					'"Business Title"', 
					'"Job Category"', 
					//'"Salary Range From"', 
					'function (r) { return toFin(r["Salary Range From"]) }',
					//'"Salary Range To"', 
					'function (r) { return toFin(r["Salary Range To"]) }',
					//'"Posting Updated"', 
					'function (r) { return usToDashDate(r["Posting Updated"]) }', 
				], 
			'visible' => [true, true, true, true, true, true],
			'filters' => [2 => null],
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
			'description' => 'This dataset contains current job postings available on the City of New York’s <a href="http://www.nyc.gov/html/careers/html/search/search.shtml"> official jobs site</a>. Internal postings available to city employees and external postings available to the general public are included.',
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
			'description' => 'The Facilities Database (FacDB) captures the locations and descriptions of public and private facilities ranging from the provision of social services, recreation, education, to solid waste management.',
		],


		'onenyc-indicators' => [
			'fullname' => 'OneNYC Indicators',
			'table' => 'onenycindicators',
			'hdrs' => ['Vision', 'Goal', 'Indicator', 'Report Year', 'Indicator Value', 'Measurement Type', 'Target Value', 'Target Year'],
			'visible' => [false, true, true, true, true, true, true, true, true],
			'flds' => ['"Vision"', '"Goal"', '"Indicator"', '"Report Year"', '"Indicator Value"', '"Measurement Type"', '"Target Value"', '"Target Year"'], 
			'filters' => [],
			//'fltDelim' => [3 => ','],
			'details' => [],
			'description' => 'Annual Agency Performance Metrics',
		],
		'assets' => [
			'fullname' => 'Local Law 251 of 2017: Published Data Asset Inventory',
			'table' => 'locallaw251',
			'hdrs' => ['Name', 'Type', 'Category', 'Open Data Plan', 'Last Updated', 'Visits', 'Row Count', 'Column Count', 'URL'],
			'visible' => [true, true, true, true, true, true, false, false, false],
			'flds' => [
					'function (r) { return `<a href="${r["URL"]}" target="_blank">${r["Name"]}</a>` }', 
					'"Type"', '"Category"', '"Legislative Compliance: Dataset from the Open Data Plan?"', '"Last Data Updated Date (UTC)"', '"Visits"', '"Row Count"', '"Column Count"', '"URL"'
			],
			'filters' => [1 => null, 2 => null, 3 => null],
			//'fltDelim' => [3 => ','],
			'details' => [
				'Description' => 'Description',
				'Update: Date Made Public' => 'Update: Date Made Public',
				'Update: Update Frequency' => 'Update: Update Frequency',
				'Legislative Compliance: Can Dataset Feasibly Be Automated?' => 'Legislative Compliance: Can Dataset Feasibly Be Automated?',
				'Update: Automation' => 'Update: Automation',
				'Legislative Compliance: Has Data Dictionary?' => 'Legislative Compliance: Has Data Dictionary?',
				'Legislative Compliance: Contains Address?' => 'Legislative Compliance: Contains Address?',
				'Legislative Compliance: Geocoded?' => 'Legislative Compliance: Geocoded?',
				'Legislative Compliance: Exists Externally? (LL 110/2015)' => 'Legislative Compliance: Exists Externally? (LL 110/2015)',
				'Legislative Compliance: External Frequency (LL 110/2015)' => 'Legislative Compliance: External Frequency (LL 110/2015)',
				'Legislative Compliance: Removed Records?' => 'Legislative Compliance: Removed Records?',
				'UID' => 'UID',
			],
			'description' => 'As per Local Law 251 of 2017, the Open Data plan is required to include the following comprehensive information on each dataset on the Open Data Portal:
- Most recent update date;
- URL;
- Whether it complies with data retention standard (which mandates that row-level data be maintained on the dataset);
- Whether it has a data dictionary;
- Whether it meets the geocoding standard, does not meet the geocoding, or is ineligible for the geospatial standard;
- Whether updates to the dataset are automated;
- Whether updates to the dataset “feasibly can be automated”.
-----
For a list of all datasets that were included on all the NYC Open Data plans (2013-2020) and their current release status, please refer to NYC Open Data Release Tracker.',
		],
		'nyccouncildiscretionaryfunding' => [
			'fullname' => 'New York City Council Discretionary Funding',
			'table' => 'nyccouncildiscretionaryfunding',
			'hdrs' => ['Fiscal Year', 'Source', 'Council Member', 'Legal Name of Organization', 'Status', 'Amount ($)', 'Borough', 'Council District'],
			'visible' => [true, true, true, true, true, true, true, true],
			'flds' => [
					'"Fiscal Year"', '"Source"', '"Council Member"', 
					'function (r) { return `<a href="https://projects.propublica.org/nonprofits/organizations/${r["EIN"]}" target="_blank">${r["Legal Name of Organization"]}</a>` }',
					'"Status"', '"Amount ($)"', '"Borough"', '"Council District"'
				], 
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
		],
		'opendatareleasetracker' => [
			'fullname' => 'NYC Open Data Release Tracker',
			'table' => 'opendatareleasetracker',
			'hdrs' => ['Name', 'Original Plan Date', 'Latest Plan Date', 'Release Status', 'Release Date', '2020 Open Data Plan', 'URL', 'Frequency'],
			'visible' => [true, true, true, true, true, true, false, false],
			'flds' => [
					'function (r) { return `<a href="${r["URL"]}" target="_blank">${r["Dataset Name"]}</a>` }',
					'function (r) { return usToDashDate(r["Original Plan Date"]) }', //'"Original Plan Date"', 
					'function (r) { return usToDashDate(r["Latest Plan Date"]) }', //'"Latest Plan Date"', 
					'"Release Status"', 
					'function (r) { return usToDashDate(r["Release Date"]) }', //'"Release Date"',
					'"From the 2020 Open Data Plan?"', '"URL"', '"Update Frequency"'
			], 
			'filters' => [3 => null, 5 => null],
			//'fltDelim' => [3 => ','],
			'details' => [
				'Description' => 'Dataset Description',
				'UID' => 'U ID',
				'Agency Notes' => 'Agency Notes',
			],
			'description' => 'A list of all datasets that were included on all the NYC Open Data plans (2013-2019) and their current release status. For a comprehensive information on each dataset on the Open Data Portal, please refer to Local Law 251 of 2017: Published Data Asset Inventory.',
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
		'onenyc-indicators' => 'OneNYC',
		'assets' => 'Assets',
		'nyccouncildiscretionaryfunding' => 'City Council Discretionary',
		'opendatareleasetracker' => 'Tracker',
	];
	
	public $menu = [
		'about',
		'Finances' => 
			[
				'expense-budget',
				'nyccouncildiscretionaryfunding',	//nyccouncildiscretionaryfunding
			],
		'capital-projects',
		'services',
		'people',
		'Indicators' => 
			[
				'indicators-mmr',
				'onenyc-indicators',		//onenycindicators
			],
		'requests',
		'jobs',
		'facilities',
		'Data' => 
			[
				'opendatareleasetracker',		//Tracker	
				'assets',		//Assets
			],
	];
	
	public function menuActiveDD($sect)
	{
		foreach ($this->menu as $h=>$items)
			if (is_array($items) && (array_search($sect, $items) !== false))
				return $h;
		return '';
	}
	
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
		
		$fltDel = [];
		foreach ((array)($dd['fltDelim'] ?? []) as $i=>$v)
			$fltDel[$i + $inc] = $v;
		$dd['fltDelim'] = $fltDel;
		
		$dd['fltsCols'] = implode(',', array_keys($dd['filters']));
		return $dd;
	}
}