<?php
Namespace App\Custom;

class Breadcrumbs
{
	static public $root = [
			//['https://wegov.nyc', 'Home'],
			//['https://wegov.nyc/tools', 'Tools'],
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
		return array_merge(self::$root, [['/organizations/directory', 'Organizations']]);
	}

	static function org($id, $name)
	{
		return array_merge(self::$root, [['/organizations/directory', 'Organizations'], ["/organization/{$id}", $name]]);
	}

	static function orgSect($id, $name, $sect, $sectN)
	{
		return array_merge(self::$root, [['/organizations/directory', 'Organizations'], ["/organization/{$id}", $name], ["/organization/{$id}/{$sect}", $sectN]]);
	}

	static function orgPrj($id, $name, $sect, $sectN, $prjId, $prjN)
	{
		return array_merge(self::$root, [['/capitalprojects', 'Capital Projects'], ["/organization/{$id}", $name], ["/organization/{$id}/{$sect}/{$prjId}", $prjId]]);
	}

	static function districts()
	{
		return array_merge(self::$root, [['/districts', 'Districts']]);
	}

	static function auctions()
	{
		return array_merge(self::$root, [['/auctions', 'Auctions']]);
	}

	static function projects()
	{
		return array_merge(self::$root, [['/capitalprojects', 'Capital Projects']]);
	}

	static function titles()
	{
		return array_merge(self::$root, [['/titles', 'Titles']]);
	}

	static function titleSect($id, $name, $sect, $sectN)
	{
		return array_merge(self::$root, [['/titles', 'Titles'], ["/titles/{$id}", $name], ["/titles/{$id}/{$sect}", $sectN]]);
	}

	static function notices()
	{
		return array_merge(self::$root, [['/notices', 'Notices']]);
	}

	static function noticesSect($sect, $sectN)
	{
		return array_merge(self::$root, [['/notices', 'Notices'], ["/notices/{$sect}", $sectN]]);
	}
}
