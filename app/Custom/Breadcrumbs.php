<?php
Namespace App\Custom;

class Breadcrumbs
{
	static public $root = [
			['https://wegov.nyc', 'Home'],
			['https://wegov.nyc/tools', 'Tools'],
			['/organizations', 'DataBook']
		];
		
	static function root()
	{
		$rr = self::$root;
		$rr[2] = ['', 'OrgDb'];
		return $rr;
	}
	
	static function about()
	{
		return array_merge(self::$root, [['', 'About']]);
	}
	
	static function orgs()
	{
		return array_merge(self::$root, [['', 'Index']]);
	}
	
	static function org($name)
	{
		return array_merge(self::$root, [['', $name]]);
	}
	
	static function orgSect($id, $name, $sect)
	{
		return array_merge(self::$root, [["/organization/{$id}", $name], ['', $sect]]);
	}
}