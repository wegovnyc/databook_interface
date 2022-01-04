<?php
Namespace App\Custom;

class CapProjectsBuilder
{
	static function build($prjs, $milestones)
	{
		$dd = [];
		foreach ($prjs as $prj)
		{
			$dd[$prj['PUB_DATE']] = $prj;
		}
		krsort($dd);
		
		foreach ($milestones as $i=>$m)
		{
			if ($dd[$m['PUB_DATE']] ?? null)
			{
				$dd[$m['PUB_DATE']]['ORIG_START'] = min($dd[$m['PUB_DATE']]['ORIG_START'] ?? '20500101', self::ds($m['ORIG_START_DATE']));
				$dd[$m['PUB_DATE']]['ORIG_END'] = max($dd[$m['PUB_DATE']]['ORIG_END'] ?? '20000101', self::ds($m['ORIG_END_DATE']));
				$dd[$m['PUB_DATE']]['CURR_START'] = min($dd[$m['PUB_DATE']]['CURR_START'] ?? '20500101', self::ds($m['TASK_START_DATE']));
				$dd[$m['PUB_DATE']]['CURR_END'] = max($dd[$m['PUB_DATE']]['CURR_END'] ?? '20000101', self::ds($m['TASK_END_DATE']));
				$dd[$m['PUB_DATE']]['milestones'][strtotime($m['ORIG_END_DATE']) + $i] = [
					//'PUB_DATE_F' => self::df($m['PUB_DATE']),
					'ORIG_DATE_F' => str_replace(' 12:00:00 AM', '', $m['ORIG_END_DATE']),
					'CURR_DATE_F' => str_replace(' 12:00:00 AM', '', $m['TASK_END_DATE']),
					'DATE_DIFF' => self::dateDiff(self::ds($m['ORIG_END_DATE']), self::ds($m['TASK_END_DATE'])),
					'TASK_DESCRIPTION' => $m['TASK_DESCRIPTION'],
				];
			}
		}
		
		$rr = [];
		$cLog = [];
		$geo_json = null;
		$name = $id = '';
		$inext = null;
		foreach ($dd as $i=>$d)
		{
			if ($d['milestones'] ?? null)
				ksort($d['milestones']);
			$rr[$i] = [
				'#BORO' => $d['BORO'] ?? '',
				'#MANAGING_AGCY' => $d['MANAGING_AGCY'] ?? '',
				'#PROJECT_ID' => $d['PROJECT_ID'] ?? '',
				'#PROJECT_DESCR' => $d['PROJECT_DESCR'] ?? '',
				'#TYP_CATEGORY_NAME' => $d['TYP_CATEGORY_NAME'] ?? '',
				'#COMMUNITY_BOARD' => $d['COMMUNITY_BOARD'] ?? '',
				'#BUDGET_LINE' => $d['BUDGET_LINE'] ?? '',
				'#DELAY_DESC' => $d['DELAY_DESC'] ?? '',
				'#SITE_DESCR' => $d['SITE_DESCR'] ?? '',
				'#SCOPE_TEXT' => $d['SCOPE_TEXT'] ?? '',
				'PUB_DATE_F' => self::df($d['PUB_DATE'] ?? ''),
				
				'#budget .original' => sprintf('<span data-content="%s">%s</span>', 
						self::budgetRound($d['ORIG_BUD_AMT'], 1000), 
						self::toFinShortK($d['ORIG_BUD_AMT'], 1000)),
				'#budget .current' => sprintf('<span data-content="%s">%s</span>', 
						self::budgetRound($d['CITY_PRIOR_ACTUAL'] + $d['CITY_PLAN_TOTAL'], 1000), 
						self::toFinShortK($d['CITY_PRIOR_ACTUAL'] + $d['CITY_PLAN_TOTAL'], 1000)),
				'#budget .difference' => sprintf('<span data-content="%s">%s</span>', 
						self::budgetRound(($d['CITY_PRIOR_ACTUAL'] + $d['CITY_PLAN_TOTAL']) - $d['ORIG_BUD_AMT'], 1000), 
						self::budgetDiff($d['ORIG_BUD_AMT'], $d['CITY_PRIOR_ACTUAL'] + $d['CITY_PLAN_TOTAL'], 1000)
					),
				
				'#start .original' => ($d['ORIG_START'] ?? null) ? self::df($d['ORIG_START']) : '-',
				'#start .current' => ($d['CURR_START'] ?? null) ? self::df($d['CURR_START']) : '-',
				'#start .difference' => ($d['ORIG_START'] ?? null) ? self::dateDiff($d['ORIG_START'], $d['CURR_START']) : '-',
				
				'#end .original' => ($d['ORIG_END'] ?? null) ? self::df($d['ORIG_END']) : '-',
				'#end .current' => ($d['CURR_END'] ?? null) ? self::df($d['CURR_END']) : '-',
				'#end .difference' => ($d['ORIG_END'] ?? null) ? self::dateDiff($d['ORIG_END'], $d['CURR_END']) : '-',
				
				'#duration .original' => ($d['ORIG_END'] ?? null) ? self::dateDiff($d['ORIG_END'], $d['ORIG_START'], false) . ' years' : '-',
				'#duration .current' => ($d['CURR_END'] ?? null) ? self::dateDiff($d['CURR_END'], $d['CURR_START'], false) . ' years' : '-',
				'#duration .difference' => ($d['ORIG_END'] ?? null) ? self::durationDiff(self::dateDiff($d['ORIG_END'], $d['ORIG_START'], false), self::dateDiff($d['CURR_END'], $d['CURR_START'], false)) : '-',
				
				'milestones' => ($d['milestones'] ?? null) ? array_values($d['milestones']) : [],
			];
			$name = $name ? $name : $d['PROJECT_DESCR'];
			$id = $id ? $id : $d['wegov-org-id'];
			$geo_json = $d['GEO_JSON'] ? $d['GEO_JSON'] : $geo_json;
			
			if ($inext)
			{
				$logT = self::genLog($dd[$i], $dd[$inext]);
				if ($logT)
					$cLog[$i] = $logT;
			}
			$inext = $i;
		}
		/*
		?><pre><?
		print_r($cLog);
		?></pre><?
		*/
		return ['name' => $name, 'items' => $rr, 'geo_feature' => str_replace('""', '"', $geo_json), 'id' => $id, 'cLog' => $cLog];
	}
	
	static function genLog($pdd, $dd)
	{
		$mm = $dd['milestones'] ?? [];
		$rr = [];
		foreach ([
				'BUDG_ORIG' => 'Original Budget',
				'BUDG_CURR' => 'Current Budget',
				'START_ORIG' => 'Original Start',
				'START_CURR' => 'Current Start',
				'END_ORIG' => 'Original End',
				'END_CURR' => 'Current End',
			] as $f=>$t)
			if (($dd[$f] ?? null) <> ($pdd[$f] ?? null))
				$rr[] = self::genLogItem($t, $dd[$f] ?? null, $pdd[$f] ?? null);
		
		$pmm = [];
		foreach ($dd['milestones'] ?? [] as $m)
			$pmm[$m['TASK_DESCRIPTION']] = $m;
		
		foreach ($mm as $m)
		{
			if (!($pmm[$m['TASK_DESCRIPTION']] ?? null))
				$rr[] = ["New milestone '{$m['TASK_DESCRIPTION']}'", ''];
			else 
				foreach ([
						'ORIG_DATE_F' => 'Original',
						'CURR_DATE_F' => 'Current',
					] as $f=>$t)
					if (($m[$f] ?? null) <> ($pmm[$m['TASK_DESCRIPTION']][$f] ?? null))
						#$rr[] = ($pmm[$m['TASK_DESCRIPTION']][$f] ?? null) ? "{$m['TASK_DESCRIPTION']} {$t} changed from {$pmm[$m['TASK_DESCRIPTION']][$f]} to {$m[$f]}" : "{$t} stated to {$m[$f]}";
						$rr[] = self::genLogItem("{$m['TASK_DESCRIPTION']} {$t}", $pmm[$m['TASK_DESCRIPTION']][$f] ?? null, $m[$f] ?? null);
		}
		return $rr;
	}
	
	static function genLogItem($t, $a, $b)
	{
		$is_budg = preg_match('~(Original|Current) Budget~si', $t);
		if ($b)
		{
			if ($is_budg)
			{
				$d = (float)$b - (float)$a;
				$a = self::budgetRound((float)$a, 1000);
				$b = self::budgetRound((float)$b, 1000);
				return [
					"{$t} " . ($d > 0 ? 'increase &#9650; ' : 'decrease &#9660; ') . self::toFinShortK(abs($d), 1000),
					"from {$a} to {$b}"
				];
			} else {
				$d = self::date2float($a) - self::date2float($b);
				return [
					"{$t} " . ($d < 0 ? 'postponed &#9650; ' : 'anticipated &#9660; ') . self::timerangeShort(abs($d * 365)),
					"from {$a} to {$b}"
				];
			}
		}
		else
			["{$t} set to {$b}", ''];
	}
	
	static function df($d)
	{
		return preg_match('~^\d{8}$~', $d) 
				? implode('/', [substr($d,4,2), substr($d,6,2), substr($d,0,4)]) 
				: $d;
	}
	
	static function ds($d)
	{
		return date('Ymd', strtotime($d));
	}
	
	# $dP - date planned, $dA - date actual; format - 20211031
	# if $format == true --> $dP > $dA - good
	static function dateDiff($dP, $dA, $format=true)
	{
		$r = self::date2float($dP) - self::date2float($dA);
		$r = round($r, 1);
		if (!$format)
			return $r;
		switch ($r <=> 0)
		{
			case 0:
				return "<span class='good'>on time</span>";
				break;
			case -1:
				$r = -$r;
				return "<span class='bad'>{$r} years late</span>";
				break;
			case 1:
				return "<span class='good'>{$r} years early</span>";
				break;
		}
	}
	
	static function date2float($d)
	{
		$t = strtotime($d);
		return (int)date('Y', $t) + (float)date('z', $t)/365;
	}
	
	static function durationDiff($dP, $dA, $format=true)
	{
		$r = $dP - $dA;
		$r = round($r, 1);
		if (!$format)
			return $r;
		$p = $dP ? self::perc($dA, $dP) : '';
		switch ($r <=> 0)
		{
			case 0:
				return "<span class='good'>on time</span>";
				break;
			case -1:
				$r = -$r;
				return "<span class='bad'>{$r} years over <small><i>({$p})</i></small></span>";
				break;
			case 1:
				return "<span class='good'>{$r} years below <small><i>({$p})</i></small></span>";
				break;
		}
	}
	
	static function budgetDiff($b1, $b2, $m=1)
	{
		$d = (int)$b1 - (int)$b2;
		$df = self::toFinShortK(abs($d), $m);
		if (abs($d) < 250)
			$d = 0;
		$p = $b1 ? self::perc($b2, $b1) : '';
		switch ($d <=> 0)
		{
			case 0:
				return "<span class='good'>Fit</span>";
			case -1:
				return "<span class='bad'>{$df} over <small><i>({$p})</i></small></span>";
			case 1:
				return "<span class='good'>{$df} below <small><i>({$p})</i></small></span>";
		}
	}

	static function budgetRound($b, $m=1)
	{
		return '$' . number_format((int)$b * $m);
	}
	
	static function perc($c, $p)
	{
		return number_format(abs($c/$p - 1) * 100) . '%';
	}
	
	static function toFinShortK($b, $m=1)
	{
		$b = (int)$b * $m;
		if ($b < 1000)
			return '$' . number_format($b);
		$units = [0 => 'K', 1 => 'M', 2 => 'B'];
		for ($u = 0; $u <= 2; $u++)
		{
			$b = $b / 1000;
			if ($b < 1000)
			{
				if ($b >= 100)
					return '$' . number_format($b) . $units[$u];
				elseif ($b >= 10)
					return '$' . number_format($b, 1) . $units[$u];
				else if ($b >= 1)
					return '$' . number_format($b, 2) . $units[$u];
			}
		}
		return '$' . number_format($b) . $units[$u - 1];
	}
	
	static function timerangeShort($d)
	{
		if (abs($d) > 365)
			return number_format($d / 365, 1) . ' years';
		if (abs($d) > 30)
			return number_format($d / 30, 1) . ' months';
		return number_format($d, 0) . ' days';
	}
}
