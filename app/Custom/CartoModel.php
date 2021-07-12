<?php
Namespace App\Custom;

class CartoModel
{
	function __construct($entry, $key)
	{
		$this->carto = new Carto($entry, $key);
		//print_r([$entry, $key]);
	}

	function orgs()
	{
		$dd = $this->carto->req('SELECT * FROM wegov_orgs ORDER BY name');
		return $this->map($dd);
	}

	function org($id)
	{
		$dd = $this->carto->req("SELECT * FROM wegov_orgs WHERE id = '{$id}'");
		return $this->map($dd)[0] ?? [];
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
	
	function crol($id)
	{
		$dd = $this->carto->req("SELECT \"StartDate\", \"EndDate\", \"SectionName\", \"ShortTitle\", \"RequestID\" FROM crol WHERE \"wegov-org-id\" = '{$id}' order by date(\"StartDate\") DESC LIMIT 9");
		return $this->map($dd) ?? [];
	}
	
	function capitalProjects($id=null, $pId=null)
	{
		$where = ($id && $pId) ? "WHERE \"wegov-org-id\" = '{$id}' AND \"PROJECT_ID\" = '{$pId}'" : '';
		$dd = $this->carto->req("SELECT * FROM capitalprojectsdollarscomp {$where} order by \"PUB_DATE\" DESC, \"PROJECT_ID\"");
		return $dd;
	}
	
	function capitalProjectsMilestones($id, $pId)
	{
		$dd = $this->carto->req("SELECT * FROM capitalprojectsmilestones WHERE \"wegov-org-id\" = '{$id}' AND \"PROJECT_ID\" = '{$pId}' order by \"PUB_DATE\" DESC");
		return $this->map($dd) ?? [];
	}
	
	function map($dd)
	{
		foreach ($dd as $i=>$d)
			foreach (['Logo', 'tags'] as $f)
				if ($d[$f] ?? null)
					$dd[$i][$f] = json_decode(str_replace(['""', '""'], ['"', "'"], $d[$f]), true);
		return $dd;
	}
	
	function url($sql)
	{
		return $this->carto->url($sql);
	}
}