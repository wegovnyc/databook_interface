<?php
Namespace App\Custom;

class Breadcrumbs
{
	static public $root = [
			//['https://wegov.nyc', 'Home'],
			['https://wegov.nyc/tools', 'Tools'],
			['/', 'DataBook']
		];

	static function root()
	{
		$rr = self::$root;
		//$rr[2] = ['', 'DataBook'];
		return $rr;
	}

	static function about()
	{
		return array_merge(self::$root, [['', 'About']]);
	}

	static function orgs()
	{
		return array_merge(self::$root, [['/agencies', 'Agencies']]);
	}

	static function org($id, $name)
	{
		return array_merge(self::$root, [['/agencies', 'Agencies'], ["/agency/{$id}", $name]]);
	}

	static function orgSect($id, $name, $sect, $sectN)
	{
		return array_merge(self::$root, [['/agencies', 'Agencies'], ["/agency/{$id}", $name], ["/agency/{$id}/{$sect}", $sectN]]);
	}

	static function orgPrj($id, $name, $sect, $sectN, $prjId, $prjN)
	{
		return array_merge(self::orgSect($id, $name, $sect, $sectN), [["/agency/{$id}/{$sect}/{$prjId}", $prjN]]);
	}

	static function districts()
	{
		return array_merge(self::$root, [['/districts', 'Districts']]);
	}

	static function projects()
	{
		return array_merge(self::$root, [['/capitalprojects', 'Capital Projects']]);
	}

}
