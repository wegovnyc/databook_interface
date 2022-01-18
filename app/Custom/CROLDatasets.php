<?php
Namespace App\Custom;

class CROLDatasets
{
	public $dd = [
		'publichearings' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Public Hearings and Meetings\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Public Hearings and Meetings',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/publichearings">${r["wegov-org-name"]}</a>` }',
					'"TypeOfNoticeDescription"', '"ShortTitle"', 
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
			'description' => 'Hearings and meetings open to the public.',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'contractawards' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Contract Award Hearings\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Contract Award Hearings',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/contractawards">${r["wegov-org-name"]}</a>` }',
					'"TypeOfNoticeDescription"', '"ShortTitle"', 
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
			'description' => 'Any contract over $100,000 is subject to a public hearing unless excepted by the City Charter or Rules of the Procurement Policy Board.',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'specialmaterials' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Special Materials\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Special Materials',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/specialmaterials">${r["wegov-org-name"]}</a>` }',
					'"TypeOfNoticeDescription"', '"ShortTitle"',
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
			'description' => 'Other category including things like commodity prices and concept papers.',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'agencyrules' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Agency Rules\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Agency Rules',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/agencyrules">${r["wegov-org-name"]}</a>` }',
					'"TypeOfNoticeDescription"', '"ShortTitle"', 
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
			'description' => 'Notices related to propose and adopted rules as well as regulatory agendas.',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'propertydisposition' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Property Disposition\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Property Disposition',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/propertydisposition">${r["wegov-org-name"]}</a>` }',
					'"TypeOfNoticeDescription"', '"ShortTitle"', 
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
			'description' => 'Public auctions and sales of city items ranging including equipment, cars and real estate.',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'courtnotices' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Court Notices\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Court Notices',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Short Title', 'Date', 'Location'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/courtnotices">${r["wegov-org-name"]}</a>` }',
					'"ShortTitle"', 
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
			'description' => 'New York State Supreme Court motions and acquisition notices.',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'procurement' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT "RequestID", "StartDate", "wegov-org-name", "wegov-org-id", "TypeOfNoticeDescription", "CategoryDescription", "ShortTitle", "SelectionMethodDescription", "AdditionalDescription1", "SpecialCaseReasonDescription", "PIN", "DueDate", "EndDate", "AddressToRequest", "ContactName", "ContactPhone", "Email", "ContractAmount", "ContactFax", "OtherInfo1", "VendorName", "VendorAddress", "Printout1", "DocumentLinks", "EventBuildingName", "EventStreetAddress1" FROM crol WHERE "SectionName" = \'Procurement\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Procurement',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Category Description', 'Short Title', 'Selection Method Description'],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["StartDate"]) }',
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/procurement">${r["wegov-org-name"]}</a>` }',
					'"TypeOfNoticeDescription"', '"CategoryDescription"', '"ShortTitle"', '"SelectionMethodDescription"'
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
			'description' => 'Over 100 city agencies post solicitations for goods and services as well as award notices.',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'changeofpersonnel' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT "AdditionalDescription1", "StartDate", "wegov-org-id", "wegov-org-name" FROM crol WHERE "SectionName" = \'Changes in Personnel\' AND NOT "AdditionalDescription1" = \'\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Changes in Personnel',
			'hdrs' => ['Effective Date', 'Agency Name', 'Provisional Status', 'Title Code', 'Reason For Change', 'Salary', 'Employee Name'],
			'flds' => [
					'function (r) { return usToDashDate(r["AdditionalDescription1"].split(";")[0].replace("Effective Date: ", "")); }', 
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/changeofpersonnel">${r["wegov-org-name"]}</a>` }',
					'function (r) { return r["AdditionalDescription1"].split(";")[1].replace("Provisional Status: ", ""); }', 
					'function (r) { 
						var code = r["AdditionalDescription1"].split(";")[2].replace("Title Code: ", "").trim();
						return `<a href="/titles/${code}">${code}</a>`;
					}', 
					'function (r) { return r["AdditionalDescription1"].split(";")[3].replace("Reason For Change: ", ""); }', 
					'function (r) { return toFin(r["AdditionalDescription1"].split(";")[4].replace("Salary: ", "")); }', 
					'function (r) { return r["AdditionalDescription1"].split(";")[5].replace("Employee Name: ", ""); }', 
				], 
			'visible' => [true, true, true, true, true, true, true],
			'filters' => [3 => null],
			'details' => [],
			'description' => 'List of people moving into and out of city government positions.',
			'script' => 'datatable.order([0, "desc"]).draw();',
		],
		'events' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE NOT "EventDate" = \'\' AND SUBSTRING("EventDate" from 7 for 4) = \'pubdate\'',
			'dates_req_sql' => 'SELECT DISTINCT(SUBSTRING("EventDate" from 7 for 4)) yy FROM crol WHERE NOT "EventDate" = \'\' ORDER BY yy DESC',
			'table' => 'crol',
			'hdrs' => ['Request ID', 'Event Date', 'Section Name', 'Type Of Notice Description', 'Agency Name', 'Short Title', ],
			'visible' => [true, true, true, true, true, true, true],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["EventDate"]); }', 
					'"SectionName"', '"TypeOfNoticeDescription"', 
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/events">${r["wegov-org-name"]}</a>` }',
					'"ShortTitle"',
				],
			'filters' => [2 => null, 3 => null, 4 => null],
			'details' => [
				'Description' => 'AdditionalDescription1',
				'Building Name' => 'EventBuildingName',
				'Street Address' => 'EventStreetAddress1',
				'Street Address 2' => 'EventStreetAddress2',
				'City' => 'EventCity',
				'State' => 'EventStateCode',
				'Zip Code' => 'EventZipCode',
			],
			'description' => 'All notices with an event date.',
			'script' => 'datatable.order([2, "asc"]).draw();',
		],
		'all' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'sql' => 'SELECT * FROM crol WHERE "EventDate" = \'\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'dates_req_sql' => 'SELECT DISTINCT(SUBSTRING("StartDate" from 7 for 4)) yy FROM crol WHERE "EventDate" = \'\' ORDER BY yy DESC',
			'hdrs' => ['Start Date', 'Request ID', 'Type Of Notice Description', 'Category Description', 'Agency Name', 'Short Title', 'Section Name', ],
			'visible' => [true, true, true, true, true, true, true],
			'flds' => ['function (r) { return usToDashDate(r["StartDate"]); }', 
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'"TypeOfNoticeDescription"', '"CategoryDescription"', 
					'function (r) { return `<a href="/organization/${r["wegov-org-id"]}/notices/all">${r["wegov-org-name"]}</a>` }',
					'"ShortTitle"', '"SectionName"'
				],
			'filters' => [2 => null, 3 => null, 5 => null, 6 => null],
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
			'description' => 'New York City’s <a href="https://en.wikipedia.org/wiki/Government_gazette" target="_blank">official journal</a> is called “The City Record.” It’s published in print, and online as a <a href="https://www1.nyc.gov/site/dcas/about/city-record.page" target="_blank">PDF</a>, as a <a href="https://a856-cityrecord.nyc.gov/" target="_blank">website</a> and as <a href="https://data.cityofnewyork.us/City-Government/City-Record-Online/dg92-zbpx/data" target="_blank">open data</a>. We’ve used the open data version, which is updated daily, to integrate The City Record’s contents into the WeGov data system. We also created RSS news and ICS event feeds from the data, and created new ways to search and browse this information. Please <a href="https://wegov.nyc/contact/" target="_blank">let us know</a> if you have ideas for how we can improve this resource.',
			'script' => 'datatable.order([1, "desc"]).draw();',
		],
	];

	public $list = [
		'all' => 'All',
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
		'all',
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