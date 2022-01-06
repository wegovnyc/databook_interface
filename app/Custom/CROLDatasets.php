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
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Contract Award Hearings\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Contract Award Hearings',
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
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Special Materials\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Special Materials',
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
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Agency Rules\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Agency Rules',
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
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Property Disposition\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Property Disposition',
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
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Court Notices\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Court Notices',
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
			'sql' => 'SELECT "RequestID", "StartDate", "wegov-org-name", "TypeOfNoticeDescription", "CategoryDescription", "ShortTitle", "SelectionMethodDescription", "AdditionalDescription1", "SpecialCaseReasonDescription", "PIN", "DueDate", "EndDate", "AddressToRequest", "ContactName", "ContactPhone", "Email", "ContractAmount", "ContactFax", "OtherInfo1", "VendorName", "VendorAddress", "Printout1", "DocumentLinks", "EventBuildingName", "EventStreetAddress1" FROM crol WHERE "SectionName" = \'Procurement\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Procurement',
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
		'changeofpersonnel' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT "AdditionalDescription1", "StartDate" FROM crol WHERE "SectionName" = \'Changes in Personnel\' AND NOT "AdditionalDescription1" = \'\' AND SUBSTRING("StartDate" from 7 for 4) = \'pubdate\'',
			'CROLsection' => 'Changes in Personnel',
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
		'events' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE NOT "EventDate" = \'\' AND SUBSTRING("EventDate" from 7 for 4) = \'pubdate\' ORDER BY date("EventDate") DESC',
			'dates_req_sql' => 'SELECT DISTINCT(SUBSTRING("EventDate" from 7 for 4)) yy FROM crol WHERE NOT "EventDate" = \'\' ORDER BY yy DESC',
			'table' => 'crol',
			'hdrs' => ['Request ID', 'Event Date', 'Section Name', 'Type Of Notice Description', 'Agency Name', 'Short Title', ],
			'visible' => [true, true, true, true, true, true, true],
			'flds' => [
					'function (r) { return `<a href="https://a856-cityrecord.nyc.gov/RequestDetail/${r["RequestID"]}" target="_blank">${r["RequestID"]}</a>` }',
					'function (r) { return usToDashDate(r["EventDate"]); }', 
					'"SectionName"', '"TypeOfNoticeDescription"', '"wegov-org-name"', '"ShortTitle"',
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
			'description' => 'The City Record Online (CROL) is now a fully searchable database of notices published in the City Record newspaper which includes but is not limited to: public hearings and meetings, public auctions and sales, solicitations and awards and official rules proposed and adopted by city agencies.',
			'script' => '',
		],
	];

	public $list = [
		'publichearings' => 'Public Hearings',
		'contractawards' => 'Contract Awards',
		'specialmaterials' => 'Special Materials',
		'agencyrules' => 'Agency Rules',
		'propertydisposition' => 'Property Disposition',
		'courtnotices' => 'Court Notices',
		'procurement' => 'Procurement',
		'changeofpersonnel' => 'Change in Personnel',
		'events' => 'Events',
	];
	
	public $menu = [
		'publichearings',
		'contractawards',
		'specialmaterials',
		'agencyrules',
		'propertydisposition',
		'courtnotices',
		'procurement',
		'changeofpersonnel',
		'events',
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