<?php
Namespace App\Custom;

class CROLDatasets
{
	public $dd = [
		/*'crol' => [
			'fullname' => 'City Record Online (CROL)',
			'table' => 'crol',
			'hdrs' => ['Start Date', 'Request ID', 'Type Of Notice Description', 'Category Description', 'Short Title', 'Section Name', ],
			'visible' => [true, true, true, true, true, true],
			'flds' => ['function (r) { return usToDashDate(r["StartDate"]); }', 
					'"RequestID"', '"TypeOfNoticeDescription"', '"CategoryDescription"', '"ShortTitle"', '"SectionName"'
				],
			'filters' => [2 => null, 3 => null, 5 => null],
			'details' => [
				'Start Date' => 'StartDate',
				'End Date' => 'EndDate',
			],
			'description' => 'The City Record Online (CROL) is now a fully searchable database of notices published in the City Record newspaper which includes but is not limited to: public hearings and meetings, public auctions and sales, solicitations and awards and official rules proposed and adopted by city agencies.',
			'script' => 'datatable.order([1, "desc"]).draw();',
		],
		*/
		'publichearings' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Public Hearings and Meetings\'',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Street Address 1', 'Street Address 2', 'City', 'State Code', 'Zip Code'],
			'flds' => ['"RequestID"', '"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', '"EventDate"', '"EventStreetAddress1"', '"EventStreetAddress2"', '"EventCity"', '"EventStateCode"', '"EventZipCode"'],
			'visible' => [true, true, true, true, true, true, true, true, true, true],
			'filters' => [1 => null, 2 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Start Date' => 'StartDate',
				'End Date' => 'EndDate',
				'Event Building Name' => 'EventBuildingName',
			],
			'description' => 'Description',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'contractawards' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Contract Award Hearings\'',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Street Address 1', 'Street Address 2', 'City', 'State Code', 'Zip Code'],
			'flds' => ['"RequestID"', '"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', '"EventDate"', '"EventStreetAddress1"', '"EventStreetAddress2"', '"EventCity"', '"EventStateCode"', '"EventZipCode"'],
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
			'description' => 'Description',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'specialmaterials' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Special Materials\'',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Short Title'],
			'flds' => ['"RequestID"', '"StartDate"', '"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"'],
			'visible' => [true, true, true, true, true],
			'filters' => [2 => null, 3 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Street Address 1' => 'EventStreetAddress1',
				'Street Address 2' => 'EventStreetAddress2',
				'City' => 'EventCity',
				'State Code' => 'EventStateCode',
				'Zip Code' => 'EventZipCode',
				'End Date' => 'EndDate',
			],
			'description' => 'Description',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'agencyrules' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Agency Rules\'',
			'hdrs' => ['Request ID', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Street Address 1', 'Street Address 2', 'City', 'State Code', 'Zip Code'],
			'flds' => ['"RequestID"', '"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', '"EventDate"', '"EventStreetAddress1"', '"EventStreetAddress2"', '"EventCity"', '"EventStateCode"', '"EventZipCode"'],
			'visible' => [true, true, true, true, true, true, true, true, true, true],
			'filters' => [1 => null, 2 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Start Date' => 'StartDate',
				'End Date' => 'EndDate',
				'Document Links' => 'DocumentLinks',
			],
			'description' => 'Description',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'propertydisposition' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Property Disposition\'',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Short Title', 'Date', 'Street Address 1', 'Street Address 2', 'City', 'State Code', 'Zip Code'],
			'flds' => ['"RequestID"', '"StartDate"', '"wegov-org-name"', '"TypeOfNoticeDescription"', '"ShortTitle"', '"EventDate"', '"EventStreetAddress1"', '"EventStreetAddress2"', '"EventCity"', '"EventStateCode"', '"EventZipCode"'],
			'visible' => [true, true, true, true, true, true, true, true, true, true, true],
			'filters' => [2 => null, 3 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Building Name' => 'EventBuildingName',
				'Document Links' => 'DocumentLinks',
				'End Date' => 'EndDate',
			],
			'description' => 'Description',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'courtnotices' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Court Notices\'',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Short Title', 'Date', 'Street Address 1', 'Street Address 2', 'City', 'State Code', 'Zip Code'],
			'flds' => ['"RequestID"', '"StartDate"', '"wegov-org-name"', '"ShortTitle"', '"EventDate"', '"EventStreetAddress1"', '"EventStreetAddress2"', '"EventCity"', '"EventStateCode"', '"EventZipCode"'],
			'visible' => [true, true, true, true, true, true, true, true, true, true],
			'filters' => [2 => null],
			'details' => [
				'Additional Description' => 'AdditionalDescription1',
				'Additional Description 2' => 'AdditionalDescription2',
				'Building Name' => 'EventBuildingName',
				'Document Links' => 'DocumentLinks',
				'End Date' => 'EndDate',
			],
			'description' => 'Description',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'procurement' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Procurement\'',
			'hdrs' => ['Request ID', 'Start Date', 'Agency Name', 'Type Of Notice Description', 'Category Description', 'Short Title', 'Selection Method Description'],
			'flds' => ['"RequestID"', '"StartDate"', '"wegov-org-name"', '"TypeOfNoticeDescription"', '"CategoryDescription"', '"ShortTitle"', '"SelectionMethodDescription"'],
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
			'description' => 'Description',
			'script' => 'datatable.order([1, "asc"]).draw();',
		],
		'changeofpersonnel' => [
			'fullname' => 'City Record Online (CROL)',
			'sql' => 'SELECT * FROM crol WHERE "SectionName" = \'Changes in Personnel\'',
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
			'description' => 'Description',
			'script' => 'datatable.order([0, "desc"]).draw();',
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