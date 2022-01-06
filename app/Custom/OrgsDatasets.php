<?php
Namespace App\Custom;

class OrgsDatasets
{
	public $dd = [
		'expensebudgetonnycopendata' => [
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
		'capitalprojects' => [
			'fullname' => 'Capital Project Detail Data - Dollars',
			'table' => 'capitalprojectsdollarscomp',
			'description' => 'This dataset contains capital commitment plan data by project type, budget line and source of funds. The dollar values are in thousands. The dataset is updated three times a year during the Preliminary, Executive and Adopted Capital Commitment Plans.',
			'hdrs' => ['Publication Date', 'Project ID', 'Name', 'Scope', 'Category', 'Borough', 'Current Budget', 'Budget Change (%)', 'Timeline Change'],
			'visible' => [false, true, true, true, true, true, true, true, true],
			'hide_on_map_open' => '0, 5, 6, 7, 8',		// +1 for details fld is already added
			'flds' => [
					'function (r) { return toDashDate(r["PUB_DATE"]) }',
					'function (r) { return `<a href="/capitalprojects/${r.PROJECT_ID}">${r.PROJECT_ID}</a>` }', 
					'"PROJECT_DESCR"', '"SCOPE_TEXT"', '"TYP_CATEGORY_NAME"', 
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
			'filters' => [/*2 => null, */4 => null],
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
		'benefitsapi' => [
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
		'nycgreenbook' => [
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
		'agencypmi' => [
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
		'budgetrequestsregister' => [
			'fullname' => 'Register of Community Board Budget Requests API',
			'table' => 'budgetrequestsregister',
			'hdrs' => ['Publication Date', 'Borough', 'C Board', 'Priority', 'Tracking Code', 'Request', 'Council District', 'NTA'],
			'visible' => [true, true, true, true, true, true, false, false],
			'flds' => [ //'"Publication"', 
						'function (r) { return toDashDate(r["Publication"]) }', 
						'"Borough"', '"Community Board"', '"Priority"', '"Tracking  Code"', '"Request"', '"wegov-cd-id"', '"wegov-nta-code"'], 
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
			'map' => ['cc' => 7, 'nta' => 8],		// +1 if details
		],
		'nycjobs' => [
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
		'facilitydb' => [
			'fullname' => 'NYC Facilities Database',
			'table' => 'facilitydb',
			'hdrs' => ['Name', 'Category', 'Group', 'Subgroup', 'Address', 'Borough', 'Council District', 'NTA'],
			'visible' => [true, true, true, true, true, true, false, false],
			'flds' => ['"facname"', '"facdomain"', '"facgroup"', '"facsubgrp"', '"address"', '"borough"', '"wegov-cd-id"', '"wegov-nta-code"'], 
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
			'map' => ['cc' => 7, 'nta' => 8],			// +1 if details
		],


		'onenycindicators' => [
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
		'locallaw251' => [
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
			'hdrs' => ['Fiscal Year', 'Source', 'Council Member', 'Legal Name of Organization', 'Status', 'Amount ($)', 'Borough', 'Council District', 'NTA'],
			'visible' => [true, true, true, true, true, true, true, true, false],
			'flds' => [
					'"Fiscal Year"', '"Source"', '"Council Member"', 
					'function (r) { return `<a href="https://projects.propublica.org/nonprofits/organizations/${r["EIN"]}" target="_blank">${r["Legal Name of Organization"]}</a>` }',
					'"Status"', '"Amount ($)"', '"Borough"', '"Council District"', '"wegov-nta-code"'
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
			'map' => ['cc' => 8, 'nta' => 9],		// +1 if details
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
					'"From the 2021 Open Data Plan?"', '"URL"', '"Update Frequency"'
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

		'expenseplan' => [
			'fullname' => 'Expense Financial Plan - Exec',
			'table' => 'expenseplan',
			'hdrs' => ['Publication Date', 'Line Number Description', 'Fiscal Year 1', 'Prior Year Actual', 'Year 1 Executive Bud', 'Year 1 Actual', 'Year 1 Forecast', 'Year 2 Estimate', 'Year 3 Estimate', 'Year 4 Estimate', 'Year 5 Estimate'],
			'visible' => [true, true, true, true, true, true, true, true, true, true, true],
			'flds' => ['"Publication Date"', '"Line Number Description"', '"Fiscal Year 1"', '"Prior Year Actual"', '"Year 1 Executive Bud"', '"Year 1 Actual"', '"Year 1 Forecast"', '"Year 2 Estimate"', '"Year 3 Estimate"', '"Year 4 Estimate"', '"Year 5 Estimate"'],
			'filters' => [0 => null, 2 => null],
			//'fltDelim' => [3 => ','],
			'details' => [],
			'description' => 'This dataset contains agency summary level data for PS, OTPS and Total by type of funds. The dollar amount fields are rounded to the thousands. The Executive Budget report, published in April or May, contains previous fiscal year actuals, the current fiscal year Executive budget, eight month actuals and forecast plus four out years of data which coincide with the release of the published financial plan.',
		],
		'headcountactualsfunding' => [
			'fullname' => 'Headcount Actuals By Funding Source',
			'table' => 'headcountactualsfunding',
			'hdrs' => ['Publication Date', 'Fiscal Year', 'Funding', 'Headcount'],
			'visible' => [true, true, true, true],
			'flds' => ['"PUBLICATION DATE"', '"FISCAL YEAR"', '"FUNDING"', '"HEADCOUNT"'],
			'filters' => [0 => null, 1 => null],
			//'fltDelim' => [3 => ','],
			'details' => [],
			'description' => 'Funding of the actual Full-Time and Full-Time equivalent headcount that appears in the Mayor\'s Message Agency Financial tables. This dataset is updated annually.',
		],
		'expenseactualsfunding' => [
			'fullname' => 'Expense Actuals By Funding Source',
			'table' => 'expenseactualsfunding',
			'hdrs' => ['Publication Date', 'Fiscal Year', 'Funding', 'Amount'],
			'visible' => [true, true, true, true],
			'flds' => ['"PUBLICATION DATE"', '"FISCAL YEAR"', '"FUNDING"', '"AMOUNT"'],
			'filters' => [0 => null, 1 => null],
			//'fltDelim' => [3 => ','],
			'details' => [],
			'description' => 'Funding of actual spending that appears in the Mayor\'s Message Agency Financial tables. Dollars are in Thousands. This dataset is updated annually.',
		],
		'additionalcostsallocation' => [
			'fullname' => 'Additional Costs Allocation',
			'table' => 'additionalcostsallocation',
			'hdrs' => ['Publication Date', 'Cost Category', 'Actual\Plan', 'Fiscal Year', 'Total Amount', 'City Amount', 'Intra-City Amount'],
			'visible' => [true, true, true, true, true, true, true],
			'flds' => ['"PUBLICATION DATE"', '"COST CATEGORY"', '"ACTUAL\\\PLAN"', '"FISCAL YEAR"', '"TOTAL AMOUNT"', '"CITY AMOUNT"', '"INTRA-CITY AMOUNT"'],
			'filters' => [0 => null, 1 => null, 3 => null],
			//'fltDelim' => [3 => ','],
			'details' => [],
			'description' => 'Additional agency costs for Pension, Fringe Benefits and Debt Service that are included in the Pensions, Miscellaneous Budget and Debit Service agencies. Dollars are In thousands. This data set is updated annually.',
		],
		'crol' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' ORDER BY date("StartDate")',
			'hdrs' => ['Start Date', 'Request ID', 'Type Of Notice Description', 'Category Description', 'Short Title', 'Section Name', ],
			'visible' => [true, true, true, true, true, true],
			'flds' => ['function (r) { return usToDashDate(r["StartDate"]); }', 
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'"TypeOfNoticeDescription"', '"CategoryDescription"', '"ShortTitle"', '"SectionName"'
				],
			'filters' => [2 => null, 3 => null, 5 => null],
			//'fltDelim' => [3 => ','],
			'details' => [
				'Start Date' => 'StartDate',
				'End Date' => 'EndDate',
				'Due Date' => 'DueDate',
				'PIN' => 'PIN',
				'Additional Description1' => 'AdditionalDescription1',
				'Other Info 1' => 'OtherInfo1',
				'Printout 1' => 'Printout1',
				'Address To Request' => 'AddressToRequest',
				'Contact Name' => 'ContactName',
				'Contact Phone' => 'ContactPhone',
				'Email' => 'Email',
				'Contract Amount' => 'ContractAmount',
				'Special Case Reason Description' => 'SpecialCaseReasonDescription',
				'Selection Method Description' => 'SelectionMethodDescription',
				'Contact Fax' => 'ContactFax',
				'Additional Desctription 2' => 'AdditionalDesctription2',
				'Other Info 2' => 'OtherInfo2',
				'Printout 2' => 'Printout2',
				'Additional Description 3' => 'AdditionalDescription3',
				'Other Info 3' => 'OtherInfo3',
				'Printout 3' => 'Printout3',
				'Vendor Name' => 'VendorName',
				'Vendor Address' => 'VendorAddress',
				'Document Links' => 'DocumentLinks',
			],
			'description' => 'The City Record Online (CROL) is now a fully searchable database of notices published in the City Record newspaper which includes but is not limited to: public hearings and meetings, public auctions and sales, solicitations and awards and official rules proposed and adopted by city agencies.',
			'script' => 'datatable.order([1, "desc"]).draw();',
		],
		'govpublist' => [
			'fullname' => 'Government Publications Listing',
			'table' => 'govpublist',
			'hdrs' => ['Title', 'Sub-Title', 'Subject', 'Description', 'Date Published', 'Report Type', 'Associated Year - Calendar', 'Last Modified'],
			'visible' => [true, true, true, true, true, true, true, true],
			'flds' => ['"Title"', '"Sub-Title"', '"Subject"', '"Description"', '"Date Published"', '"Report Type"', '"Associated Year - Calendar"', '"Last Modified"'],
			'filters' => [2 => null, 5 => null],
			//'fltDelim' => [3 => ','],
			'details' => [
				'Required Report Name' => 'Required Report Name',
				'Additional Creators' => 'Additional Creators',
				'Languages' => 'Languages',
				'Associated Year - Fiscal' => 'Associated Year - Fiscal',
				'Associated Borough' => 'Associated Borough',
				'Associate School District' => 'Associate School District',
				'Associated Community Board District' => 'Associated Community Board District',
				'Associated Place' => 'Associated Place',
				'Filename' => 'Filename',
			],
			'description' => 'Metadata for documents submitted to the Department of Records and Information Services in compliance with Section 1133 of the New York City Charter.',
		],
		'govpubrequired' => [
			'fullname' => 'Government Publication- Required Reports',
			'table' => 'govpubrequired',
			'hdrs' => ['Name', 'Description', 'Frequency', 'Local Law', 'Charter Code', 'Last Published Date'],
			'visible' => [true, true, true, true, true, true, true, true],
			'flds' => [
					'function (r) { return `<a href="${r["See All Reports"]}" target="_blank">${r["Name"]}</a>` }',
					'"Description"', '"Frequency"', '"Local Law"', '"Charter Code"', '"Last Published Date"'
				],
			'filters' => [],
			//'fltDelim' => [3 => ','],
			'details' => [],
			'description' => 'Metadata for documents submitted to the Department of Records and Information services which are required by legislation.',
		],
		'fteheadcount' => [
			'fullname' => 'Full-Time and FTE Headcount including Covered Organizations',
			'table' => 'fteheadcount',
			'hdrs' => ['Publication Date', 'Agency Code', 'Agency Name', 'Fiscal Year', 'Personnel Type', 'Agency Group', 'City Funded Positions', 'Total Funded Positions'],
			'flds' => ['"Publication Date"', '"Agency Code"', '"Agency Name"', '"Fiscal Year"', '"Personnel Type"', '"Agency Group"', '"City Funds"', '"Total Funds"'],
			'visible' => [false, false, false, true, true, true, true, true],
			'filters' => [0 => null, 3 => null],
			'details' => [],
			'description' => 'This dataset contains estimated fiscal year-end headcount information for full-time and full-time equivalent employees (FTE) for Mayoral Agencies and Covered Organizations (updated twice a year). The information is summarized by agency, fiscal year, personnel type and funding. This dataset is updated biannually.',
			'script' => '',
		],
		'positionschedule' => [
			'fullname' => 'Position Schedule',
			'table' => 'positionschedule',
			'hdrs' => ['Publication Date', 'Agency Code', 'Agency Name', 'UA code', 'UA name', 'Title Code Name', 'Scheduled Positions', 'Minimum Salary', 'Mean Salary', 'Maximum Salary', 'Total Spent Annually'],
			'flds' => ['"PUBLICATION DATE"', '"AGENCY CODE"', '"AGENCY NAME"', '"UA CODE"', '"UA NAME"', 
						'function (r) { return `<a href="/titles/${r["TITLE CODE"]}">${r["TITLE CODE NAME"]}</a>` }',
						'"POSITIONS"', 
						'function (r) { return toFin(r["MINMUM SALARY"]) }', 
						'function (r) { return toFin(r["MEAN SALARY"]) }', 
						'function (r) { return toFin(r["MAXMUM SALARY"]) }', 
						'function (r) { return toFin(r["ANNUAL RATE"]) }', 
						
					   ],
			'visible' => [false, false, false, true, true, true, true, true, true, true, true, true],
			'filters' => [0 => null],
			'details' => [],
			'description' => 'Sum of the full-time active positions in a title description published in alphabetical order. The Position Schedule is updated and included in the Departmental Estimates and the Supporting Schedule (updated twice a year). The minimum salary, maximum salary, mean salary and annual rate are to the dollar. This dataset is updated biannually.',
			'script' => '',
		],
		'll18payanddemo' => [
			'fullname' => 'Local Law 18 Pay and Demographics Report - Agency Report Table',
			'table' => 'll18payanddemo',
			'hdrs' => ['Agency Name', 'EEO-4 Job Category', 'Pay Band', 'Employee Status', 'Race', 'Ethnicity', 'Gender', 'Number of Employees'],
			'flds' => ['"Agency Name"', '"EEO-4 Job Category"', 
						'function (r) { 
							var bb = r["Pay Band"].split("-")
							return bb ? toFin(bb[0]) + "-" + toFin(bb[1]) : "";
						}',
					   '"Employee Status"', '"Race"', '"Ethnicity"', '"Gender"', '"Number of Employees"'],
			'visible' => [false, true, true, true, true, true, true, true],
			'filters' => [],
			'details' => [],
			'description' => 'The Agency Report Table aggregates pay and employment characteristics in accordance with the requirements of Local Law 18 of 2019. The Table covers over 180,000 City employees and is a point-in-time snapshot of employees who were either active or on temporary leave (parental leave, military leave, illness, etc.) as of December 31st, 2018. To protect the privacy of employees, the sign “<5” is used instead of the actual number for groups of less than five (5) employees, in accordance with the Citywide Privacy Protection Policies and Protocols.<br/>The Pay and Demographics Report and the list of agencies included is available on the <a href="https://moda-nyc.github.io/Project-Library/projects/pay-and-demographics/">MODA Open Source Analytics Library</a>.',
			'script' => '',
		],
		'civillist' => [
			'fullname' => 'Civil List',
			'table' => 'civillist',
			'hdrs' => ['Calendar Year', 'Agency Code', 'Employee Name', 'Agency Name', 'Title Code', 'Pay Class', 'Salary Rate'],
			'flds' => ['"CALENDAR YEAR"', '"AGENCY CODE"', '"EMPLOYEE NAME"', '"AGENCY NAME"', 
						'function (r) { return `<a href="/titles/${r["TITLE CODE"]}">${r["TITLE CODE"]}</a>` }',
						'"PAY CLASS"', '"SALARY RATE"'
					  ],
			'visible' => [true, false, true, false, true, true, true],
			'filters' => [0 => null, 4 => null, 5 => null],
			'details' => [],
			'description' => 'The Civil List reports the agency code (DPT), first initial and last name (NAME), agency name (ADDRESS), title code (TTL #), pay class (PC), and salary (SAL-RATE) of individuals who were employed by the City of New York at any given time during the indicated year.',
			'script' => 'datatable.order([0, "desc"]).draw();',
			
		],


		'changeofpersonnel' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName"=\'Changes in Personnel\' ORDER BY date("StartDate")',
			'sectionTitle' => 'Change of Personnel',
			'hdrs' => ['Effective Date', 'Provisional Status', 'Title Code', 'Reason For Change', 'Salary', 'Employee Name'],
			'flds' => [
					'function (r) { return usToDashDate(r["AdditionalDescription1"].split(";")[0].replace("Effective Date: ", "")); }', 
					'function (r) { return r["AdditionalDescription1"].split(";")[1].replace("Provisional Status: ", ""); }', 
					'function (r) { 
						var code = r["AdditionalDescription1"].split(";")[2].replace("Title Code: ", "").trim();
						return `<a href="/titles/${code}">${code}</a>`;
					}', 
					'function (r) { return r["AdditionalDescription1"].split(";")[3].replace("Reason For Change: ", ""); }', 
					'function (r) { return toFin(r["AdditionalDescription1"].split(";")[4].replace("Salary: ", "")); }', 
					'function (r) { return r["AdditionalDescription1"].split(";")[5].replace("Employee Name: ", ""); }', 
				], 
			'visible' => [true, true, true, true, true, true],
			'filters' => [3 => null],
			'details' => [],
			'description' => '',
			'script' => 'datatable.order([0, "desc"]).draw();',
		],
		'publichearings' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName" = \'Public Hearings and Meetings\'',
			'sectionTitle' => 'Public Hearings and Meetings',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', 
					'function (r) { return usToDashDate(r["EventDate"]) }',
					'function (r) { 
						var rr = [r["EventStreetAddress1"], r["EventStreetAddress2"], r["EventCity"], r["EventStateCode"], r["EventZipCode"]];
						while (true) {
							var i = rr.indexOf("");
							if (i == -1) {
							  break;
							} else {
							  rr.splice(i, 1);
							}
						  }
						return rr.join(", ")
					}',
				],
			'visible' => [true, true, true, true, true, true, true, true, true, true],
			'filters' => [1 => null, 2 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Start Date' => 'StartDate',
				'End Date' => 'EndDate',
				'Event Building Name' => 'EventBuildingName',
			],
			'description' => '',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'contractawards' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName" = \'Contract Award Hearings\'',
			'sectionTitle' => 'Contract Award Hearings',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', 
					'function (r) { return usToDashDate(r["EventDate"]) }',
				    'function (r) { 
						var rr = [r["EventStreetAddress1"], r["EventStreetAddress2"], r["EventCity"], r["EventStateCode"], r["EventZipCode"]];
						while (true) {
							var i = rr.indexOf("");
							if (i == -1) {
							  break;
							} else {
							  rr.splice(i, 1);
							}
						  }
						return rr.join(", ")
					}',
				],
			'visible' => [true, true, true, true, true, true, true, true, true, true],
			'filters' => [1 => null, 2 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Document Links' => 'DocumentLinks',
				'Start Date' => 'StartDate',
				'End Date' => 'EndDate',
				'Event Building Name' => 'EventBuildingName',
				'Additional Desctription 2' => 'AdditionalDesctription2',
				'Contact Name' => 'ContactName',
				'Contact Phone' => 'ContactPhone',
				'Email' => 'Email',
			],
			'description' => '',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'specialmaterials' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName" = \'Special Materials\'',
			'sectionTitle' => 'Special Materials',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"',
					'function (r) { 
						var rr = [r["EventStreetAddress1"], r["EventStreetAddress2"], r["EventCity"], r["EventStateCode"], r["EventZipCode"]];
						while (true) {
							var i = rr.indexOf("");
							if (i == -1) {
							  break;
							} else {
							  rr.splice(i, 1);
							}
						  }
						return rr.join(", ")
					}',
			],
			'visible' => [true, true, true, true, true, true],
			'filters' => [2 => null, 3 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'End Date' => 'EndDate',
			],
			'description' => '',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'agencyrules' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName" = \'Agency Rules\'',
			'sectionTitle' => 'Agency Rules',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', 
					'function (r) { return usToDashDate(r["EventDate"]) }',
					'function (r) { 
						var rr = [r["EventStreetAddress1"], r["EventStreetAddress2"], r["EventCity"], r["EventStateCode"], r["EventZipCode"]];
						while (true) {
							var i = rr.indexOf("");
							if (i == -1) {
							  break;
							} else {
							  rr.splice(i, 1);
							}
						  }
						return rr.join(", ")
					}',
			],
			'visible' => [true, true, true, true, true, true, true, true, true, true],
			'filters' => [1 => null, 2 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Start Date' => 'StartDate',
				'End Date' => 'EndDate',
				'Document Links' => 'DocumentLinks',
			],
			'description' => '',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'propertydisposition' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName" = \'Property Disposition\'',
			'sectionTitle' => 'Property Disposition',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', 
					'function (r) { return usToDashDate(r["EventDate"]) }',
					'function (r) { 
						var rr = [r["EventStreetAddress1"], r["EventStreetAddress2"], r["EventCity"], r["EventStateCode"], r["EventZipCode"]];
						while (true) {
							var i = rr.indexOf("");
							if (i == -1) {
							  break;
							} else {
							  rr.splice(i, 1);
							}
						  }
						return rr.join(", ")
					}',
			],
			'visible' => [true, true, true, true, true, true, true, true, true, true, true],
			'filters' => [2 => null, 3 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Building Name' => 'EventBuildingName',
				'Document Links' => 'DocumentLinks',
				'End Date' => 'EndDate',
			],
			'description' => '',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'courtnotices' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName" = \'Court Notices\'',
			'sectionTitle' => 'Court Notices',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'"wegov-org-name"', '"ShortTitle"', 
					'function (r) { return usToDashDate(r["EventDate"]) }',
					'function (r) { 
						var rr = [r["EventStreetAddress1"], r["EventStreetAddress2"], r["EventCity"], r["EventStateCode"], r["EventZipCode"]];
						while (true) {
							var i = rr.indexOf("");
							if (i == -1) {
							  break;
							} else {
							  rr.splice(i, 1);
							}
						  }
						return rr.join(", ")
					}',
			],
			'visible' => [true, true, true, true, true, true, true, true, true, true],
			'filters' => [2 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Additional Description 2' => 'AdditionalDescription2',
				'Building Name' => 'EventBuildingName',
				'Document Links' => 'DocumentLinks',
				'End Date' => 'EndDate',
			],
			'description' => '',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'procurement' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT "RequestID", "StartDate", "wegov-org-name", "TypeOfNoticeDescription", "CategoryDescription", "ShortTitle", "SelectionMethodDescription", "AdditionalDescription1", "SpecialCaseReasonDescription", "PIN", "DueDate", "EndDate", "AddressToRequest", "ContactName", "ContactPhone", "Email", "ContractAmount", "ContactFax", "OtherInfo1", "VendorName", "VendorAddress", "Printout1", "DocumentLinks", "EventBuildingName", "EventStreetAddress1" FROM crol WHERE "wegov-org-id"=\'%s\' AND "SectionName" = \'Procurement\'',
			'sectionTitle' => 'Procurement',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Category Description', 'Short Title', 'Selection Method Description'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'"wegov-org-name"', '"TypeOfNoticeDescription"', '"CategoryDescription"', '"ShortTitle"', '"SelectionMethodDescription"'
			],
			'visible' => [true, true, true, true, true, true, true],
			'filters' => [2 => null, 3 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Special Case Reason Description' => 'SpecialCaseReasonDescription',
				'PIN' => 'PIN',
				'Due Date' => 'DueDate',
				'End Date' => 'EndDate',
				'Address To Request' => 'AddressToRequest',
				'Contact Name' => 'ContactName',
				'Contact Phone' => 'ContactPhone',
				'Email' => 'Email',
				'Contract Amount' => 'ContractAmount',
				'Contact Fax' => 'ContactFax',
				'Other Info' => 'OtherInfo1',
				'Vendor Name' => 'VendorName',
				'Vendor Address' => 'VendorAddress',
				'Printout' => 'Printout1',
				'Document Links' => 'DocumentLinks',
				'Building Name' => 'EventBuildingName',
				'Street Address' => 'EventStreetAddress1',
			],
			'description' => '',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],

		/*
		'payrolldata' => [
			'fullname' => 'Citywide Payroll Data (Fiscal Year)',
			'table' => 'payrolldata',
			'hdrs' => ['Fiscal Year', 'Name', 'Title Description', 'Leave Status as of June 30', 'Base Salary', 'Pay Basis', 'OT Hours', 'Total OT Paid', 'Total Other Pay'],
			'flds' => ['"Fiscal Year"', 
					'function (r) { 
						return (r["First Name"] ? r["First Name"] : "") + 
								(r["Mid Init"] ? " " + r["Mid Init"] : "") +  
								(r["Last Name"] ? " " + r["Last Name"] : "");
					}', 
					'"Title Description"', '"Leave Status as of June 30"', '"Base Salary"', '"Pay Basis"', '"OT Hours"', '"Total OT Paid"', '"Total Other Pay"'],
			
			'visible' => [true, true, true, true, true, true, true, true, true, true, true],
			'filters' => [],
			'details' => [
				'Payroll Number' => 'Payroll Number', 
				'Agency Start Date' => 'Agency Start Date', 
				'Work Location Borough' => 'Work Location Borough', 
				'Regular Hours' => 'Regular Hours', 
				'Regular Gross Paid' => 'Regular Gross Paid', 
			],
			'description' => 'Data is collected because of public interest in how the City’s budget is being spent on salary and overtime pay for all municipal employees. Data is input into the City’s Personnel Management System (“PMS”) by the respective user Agencies. Each record represents the following statistics for every city employee: Agency, Last Name, First Name, Middle Initial, Agency Start Date, Work Location Borough, Job Title Description, Leave Status as of the close of the FY (June 30th), Base Salary, Pay Basis, Regular Hours Paid, Regular Gross Paid, Overtime Hours worked, Total Overtime Paid, and Total Other Compensation (i.e. lump sum and/or retro payments). This data can be used to analyze how the City’s financial resources are allocated and how much of the City’s budget is being devoted to overtime. The reader of this data should be aware that increments of salary increases received over the course of any one fiscal year will not be reflected. All that is captured, is the employee’s final base and gross salary at the end of the fiscal year.',
			'script' => '',
		],
		*/
	];

	public $list = [
		'about' => 'About',
		'additionalcostsallocation' => 'Additional Costs Allocation',
		'agencyrules' => 'Agency Rules',
		'locallaw251' => 'Assets',
		'nyccouncildiscretionaryfunding' => 'City Council Discretionary Funding',
		'civillist' => 'Civil List',
		'changeofpersonnel' => 'Change of Personnel',
		'nycgreenbook' => 'Contacts',
		'contractawards' => 'Contract Awards',
		'courtnotices' => 'Court Notices',
		'll18payanddemo' => 'Demographics',
		'expenseactualsfunding' => 'Expense Actuals By Funding Source',
		'expensebudgetonnycopendata' => 'Expense Budget',
		'expenseplan' => 'Expense Plan',
		'facilitydb' => 'Facilities',
		'fteheadcount' => 'Headcount',
		'headcountactualsfunding' => 'Headcount Actuals By Funding Source',
		'agencypmi' => 'Indicators',
		'nycjobs' => 'Jobs',
		'crol' => 'Notices',
		'onenycindicators' => 'OneNYC',
		#'payrolldata' => 'Payroll',
		'positionschedule' => 'Positions',
		'procurement' => 'Procurement',
		'capitalprojects' => 'Projects',
		'propertydisposition' => 'Property Disposition',
		'publichearings' => 'Public Hearings',
		'govpublist' => 'Publications',
		'budgetrequestsregister' => 'Requests',
		'govpubrequired' => 'Required Reports',
		'benefitsapi' => 'Services',
		'specialmaterials' => 'Special Materials',
		'opendatareleasetracker' => 'Tracker',

	];
	/*
		additionalcostsallocation
		agencypmi
		benefitsapi
		budgetrequestsregister
		capitalprojects_cc_idx
		capitalprojects_cd_idx
		capitalprojects_nta_idx
		capitalprojectsdollarscomp
		capitalprojectsmilestones
		ccmembers
		civillist
		crol
		data_sources
		expenseactualsfunding
		expensebudgetonnycopendata
		expenseplan
		facilitydb
		fteheadcount
		govpublist
		govpubrequired
		headcountactualsfunding
		ll18payanddemo
		locallaw251
		nyccivilservicetitles
		nyccouncildiscretionaryfunding
		nycgreenbook
		nycjobs
		onenycindicators
		opendatareleasetracker
		positionschedule
		wegov_orgs
		#payrolldata
	*/
	
	public $menu = [
		'about',
		'Notices' => 
			[
				'publichearings',
				'procurement',
				'contractawards',
				'agencyrules',
				'propertydisposition',
				'courtnotices',
				'changeofpersonnel',
				'specialmaterials',
			],
		'Records & Data' => 
			[
				'govpublist',
				'govpubrequired',
				'opendatareleasetracker',		//Tracker	
				'locallaw251',		//Assets
				//'crol',
			],
		'Finances' => 
			[
				'expensebudgetonnycopendata',
				'nyccouncildiscretionaryfunding',	//nyccouncildiscretionaryfunding
				'expenseplan',	
				'headcountactualsfunding',
				'expenseactualsfunding',
				'additionalcostsallocation'
			],
		'People' => 
			[		
				'nycgreenbook',
				'fteheadcount',
				'positionschedule',
				'll18payanddemo',
				#'payrolldata',
				'changeofpersonnel',
				'civillist',
			],
		'capitalprojects',
		'benefitsapi',
		'Indicators' => 
			[
				'agencypmi',
				'onenycindicators',		//onenycindicators
			],
		'budgetrequestsregister',
		'nycjobs',
		'facilitydb',
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