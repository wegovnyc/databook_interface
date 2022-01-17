<?php
Namespace App\Custom;

class CartoModel
{
	function __construct($entry, $key)
	{
		$this->carto = new Carto($entry, $key);
		//print_r([$entry, $key]);
	}

	function orgs($where='ORDER BY name')
	{
		$dd = $this->carto->req("SELECT * FROM wegov_orgs {$where}");
		return $this->map($dd);
	}

	function org($id)
	{
		#$dd = $this->carto->req("SELECT * FROM wegov_orgs WHERE id = '{$id}'");
		$dd = $this->carto->req("SELECT org.*, p.id AS parent_id, p.name AS parent_name, p.type AS parent_type FROM wegov_orgs org LEFT JOIN wegov_orgs p ON p.airtable_id = regexp_replace(org.child_of, '[\[\]\"]', '', 'g') WHERE org.id = '{$id}'");
		return $this->map($dd)[0] ?? [];
	}
	
	function titles($id)
	{
		$dd = $this->carto->req("SELECT * FROM nyccivilservicetitles WHERE \"Title Code\" = '{$id}' ORDER BY \"Assignment Level\"");
		return $dd ?? [];
	}
	
	function dataset($name)
	{
		$dd = $this->carto->req("SELECT * FROM data_sources WHERE \"Name\" LIKE '{$name}'");
		return $this->map($dd)[0] ?? [];
	}
	
	function ccMember($id)
	{
		$dd = $this->carto->req("SELECT * FROM ccmembers WHERE \"wegov-cd-id\" = {$id}");
		return $this->map($dd)[0] ?? [];
	}
	
	function crolNews($id=null)
	{
		$where = $id ? "\"wegov-org-id\" = '{$id}' AND " : '';
		$dd = $this->carto->req("SELECT \"StartDate\", \"EndDate\", \"SectionName\", \"ShortTitle\", \"RequestID\" , \"TypeOfNoticeDescription\", \"wegov-org-name\", \"wegov-org-id\" FROM crol WHERE {$where} \"EventDate\" = '' AND date(\"StartDate\") >= date(NOW() - INTERVAL '1 WEEK') order by date(\"StartDate\") DESC LIMIT 9");
		return $this->map($dd) ?? [];
	}
	
	function crolEvents($id)
	{
		$dd = $this->carto->req("SELECT \"StartDate\", \"EndDate\", \"SectionName\", \"ShortTitle\", \"RequestID\" FROM crol WHERE \"wegov-org-id\" = '{$id}' AND NOT \"EventDate\" = '' AND date(\"EventDate\") >= current_date order by date(\"EventDate\") LIMIT 9");
		return $this->map($dd) ?? [];
	}
	
	function capitalProjects($pId=null)
	{
		$where = $pId ? "WHERE \"PROJECT_ID\" = '{$pId}'" : '';
		$dd = $this->carto->req("SELECT * FROM capitalprojectsdollarscomp {$where} order by \"PUB_DATE\" DESC, \"PROJECT_ID\"");
		return $dd;
	}
	
	function capitalProjectsMilestones($pId)
	{
		$dd = $this->carto->req("SELECT * FROM capitalprojectsmilestones WHERE \"PROJECT_ID\" = '{$pId}' order by \"PUB_DATE\" DESC");
		return $this->map($dd) ?? [];
	}
	
	function map($dd)
	{
		foreach ($dd as $i=>$d)
			foreach (['logo', 'tags', 'communityDistrict', 'communityDistrictName', 'communityDistrictId', 'cityCouncilDistrict', 'cityCouncilDistrictName', 'cityCouncilDistrictId'] as $f)
				if ($d[$f] ?? null)
					$dd[$i][$f] = json_decode(str_replace(['""', '""'], ['"', "'"], $d[$f]), true);
		return $dd;
	}
	
	function url($sql)
	{
		return $this->carto->url($sql);
	}
}