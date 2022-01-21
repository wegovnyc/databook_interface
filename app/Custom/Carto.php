<?php
// Postgre SQL syntax - https://www.tutorialspoint.com/postgresql/postgresql_syntax.htm
// Carto SQL syntax - https://carto.com/developers/sql-api/guides/introduction/
Namespace App\Custom;

class Carto
{
	public $verbose = false;
	public $filelog = false;
	function __construct($entry, $key)
	{
		$this->key = $key;
		$this->entry = $entry;
	}
	
	function req($sql, $raw=false)
	{
		$url = sprintf('%s?api_key=%s', $this->entry, $this->key);
		if ($this->filelog)
			file_put_contents(ROOTDIR . '/carto.log', "======================================\n{$sql}\n", FILE_APPEND);
		$resp = Curl2::exec($url, 'post', [], '', ['q' => $sql]);
		if ($raw)
			return $resp;
		$respJ = json_decode($resp, true);
		if (isset($respJ['rows']))
			return $respJ['rows'];
		if ($respJ['error'] ?? null)
		{
				echo sprintf("Carto model error... \nreq: %s\nerror: %s\n", substr($sql, 0, 2500), print_r($respJ['error'], true));
			return false;
		}
		
		if (($respJ['warnings'] ?? null) && $this->verbose)
			echo 'Carto model warning: ' . print_r($respJ['warnings'], true) . "\n";
		
		return true;
	}
	
	function create($tbl, $dd, $schema=null, $idxFlds=[])
	{
		if (!$this->req(self::createSql($tbl, $dd, $schema)))
			return false;
		
		$v = $this->verbose;
		$this->verbose = false;
		foreach ($idxFlds as $f)
			$this->req(sprintf('CREATE INDEX "%s-%s" ON %s ("%s")', $tbl, strtolower($f), $tbl, $f));
		$this->verbose = $v;
				
		$this->req("SELECT cdb_cartodbfytable('{$tbl}')");
	}
	
	function insert($tbl, $dd)
	{
		$facet = [];
		$hh = [];
		$iter = 0;
		foreach ($dd as $d)
		{
			$facet[] = $d;
			if (!$iter)
				$hh = array_merge(array_fill_keys(array_keys($d), true), $hh);
			if (count($facet) >= 2500)
			{
				$sql = $this->insertSql($tbl, $facet, array_keys($hh));
				if (!$this->req($sql))
					return false;
				$facet = [];
				$iter++;
			}
		}
		$sql = $this->insertSql($tbl, $facet, array_keys($hh));
		if (!$this->req($sql))
			return false;
		return true;
	}
	
	function update($tbl, $dd, $where)
	{
		$sql = self::updateSql($tbl, $dd, $where);
		return $this->req($sql);
	}
	
	function tablesList($tbl='')
	{
		$rr = [];
		foreach ($this->req("SELECT CDB_UserTables('all')") as $d)
			$rr[] = $d['cdb_usertables'];
		return $tbl ? array_search(strtolower($tbl), $rr) !== false : $rr;
	}
	
	function tableSchema($tbl)
	{
		$dd = json_decode($this->req("SELECT * FROM {$tbl} LIMIT 1", true), true);
		return $dd['fields'];
	}
	
	function match($tbl, $dd, $trgSch=null)
	{
		if (!$this->tablesList($tbl))
			return false;
		$trgSch = $trgSch ?? self::analyze($dd);
		$sch = $this->tableSchema($tbl);
		
		if (0)
		{
			echo "---------------\n";
			foreach ($trgSch as $k=>$d)
				echo "{$k};{$d['type']}\n";
			echo "---------------\n";
			foreach ($sch as $k=>$d)
				echo "{$k};{$d['pgtype']}\n";
			echo "---------------\n";
		}
		
		foreach ($trgSch as $f=>$ss)
			if (!isset($sch[$f]))
				return false;
			elseif (($sch[$f]['pgtype'] == 'numeric') && preg_match('~text|varchar~si', $ss['type']))
				return false;
			elseif (preg_match('~varchar~si', $sch[$f]['pgtype']) && ($ss['type'] == 'text'))
				return false;
		return true;
	}

	function url($sql)
	{
		return sprintf('%s?q=%s&api_key=%s', $this->entry, urlencode($sql), $this->key);
	}
	
///////////// srv ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	static function createSql($tbl, $dd, $schema=null)
	{
		$ff = [];
		$schema = $schema ?? self::analyze($dd);
		foreach ($schema as $f=>$ss)
			$ff[] = sprintf('"%s" %s', self::esc($f), $ss['type']);
		return "CREATE TABLE {$tbl} (" . implode(', ', $ff) . ")";
	}

	static function insertSql($tbl, $dd, $hh)
	{
		$tt = [];
		foreach ($dd as $i=>$d)
		{
			$tmp = [];
			foreach ($hh as $h)
				$tmp[] = sprintf("'%s'", self::esc($d[$h] ?? ''));
			$tt[$i] = '(' . implode(', ', $tmp) . ')';
		}
		$hht = self::implodeHdrs($hh);
		return "INSERT INTO {$tbl} ({$hht}) VALUES " . implode(', ', $tt);
	}

	static function updateSql($tbl, $dd, $where)
	{
		$tt = [];
		foreach ($dd as $h=>$v)
			$tt[] = sprintf('"%s" = \'%s\'', self::esc($h), self::esc($v));
		return sprintf('UPDATE %s SET %s %s', $tbl, implode(', ', $tt), $where);
	}

	static function analyze($dd)
	{
		$rr = [];
		foreach ($dd as $d)
			foreach ($d as $k=>$v)
			{
				switch (gettype($v))
				{
					case 'boolean':
					case 'integer':
					case 'double':
						$t = 'numeric';
						break;
					case 'NULL':
						$t = 'string';
						break;
					case 'string':
						if ($v == '')
							$t = 'string';
						else
							$t = preg_match('~^[-+]?\s*\d+\.?\d*$~si', trim($v)) ? 'numeric' : 'string';
						break;
					default:
						$t = 'string';
				}
				if ($t && ($rr[$k]['type'] == 'string'))
					$t = 'string';
					
				if ($t == 'string')
				{
					$rr[$k] = [
						'type' => 'string',
						'min' => min($rr[$k]['min'] ?? 999, strlen($v)),
						'max' => max($rr[$k]['max'] ?? 0, strlen($v))
					];
				} 
				elseif ($t == 'numeric')
					$rr[$k] = [
						'type' => 'numeric',
						'min' => min($rr[$k]['min'] ?? 999, strlen((string)$v)),
						'max' => max($rr[$k]['max'] ?? 0, strlen((string)$v))
					];
				elseif (!isset($rr[$k]))	
					$rr[$k] = [
						'type' => null
					];
			}
			
		foreach ($rr as $f=>$r)
			if (!$r['type'] || ($r['type'] == 'string' && $r['max'] > 25))
				$rr[$f]['type'] = 'text';
			elseif ($r['type'] == 'string')
				$rr[$f]['type'] = sprintf('varchar(%u)', $r['max'] * 1.6);
		
		return $rr;
	}
	
	static function esc($t)
	{
		return preg_replace(['~"~si', "~'~si"], ['""', "''"], $t);
	}
	
	static function implodeHdrs($hh)
	{
		$tmp = [];
		foreach ($hh as $h)
			$tmp[] = sprintf('"%s"', self::esc($h));
		return implode(', ', $tmp);
	}
	
}