<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Custom\CartoModel;

class ChartUpdateJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chart:updatejson';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$orgs = $model->orgs('WHERE "type" IN (\'City Agency\', \'Elected Office\', \'Boards and Comissions\', \'Classification\', \'Community Board\', \'Official\')');
		$mm = [];
		
		foreach ($orgs as $org)
			$mm[$org['airtable_id']] = $org + ['children' => []];
		$rootId = '';
		foreach ($orgs as $org)
		{
			$parent_id = preg_replace('~[\[\]"]~si', '', $org['child_of']);
			if ($org['id'] == '170000000')
				$rootId = $org['airtable_id'];
			if (!$parent_id)
				continue;
			$mm[$parent_id]['children'][$org['name']] = $org['airtable_id'];
			echo $org['id'] . '; ';
		}
		//file_put_contents(public_path('data/orgChart.test'), print_r($mm, true));
		$rr = json_encode(self::packnode($mm, $rootId));
		file_put_contents(public_path('data/orgChart.json'), $rr);
		//echo $rr;
        return 0;
    }
	
	public function packnode($dd, $key, $familyClass=null)
	{
		$cc = ['170011039' => '#b4f8c8', '170010056' => '#fcb5ac', '170100000' => '#76b947', '170100001' => '#ff9636', '170100007' => '#05e0e9', '170100012' => '#bfd7ed', '170100016' => '#fede00', '170100017' => '#96ad90', '170100030' => '#c8df52', '170100035' => '#ffe9e4', '170100036' => '#647c90'];
		
		$familyClass = $familyClass ?? (($cc[$dd[$key]['id']] ?? null) ? "node_{$dd[$key]['id']}" : null);
		$rr = ['name' => $dd[$key]['name'], 'title' => "<a href=\"/agency/{$dd[$key]['id']}\">{$dd[$key]['id']}</a>", 'className' => $familyClass ?? 'node_def'];
		if ($dd[$key]['children'])
		{
			$children = [];
			uksort($dd[$key]['children'], function ($a, $b) {
				if ($a == 'Mayor\'s Office')
					return -1;
				if ($b == 'Mayor\'s Office')
					return 1;
				return $a <=> $b;
			});
			foreach (array_values($dd[$key]['children']) as $childId)
				$children[] = self::packnode($dd, $childId, $familyClass);
			$rr['children'] = $children;
			if ($children && !$familyClass && ($dd[$key]['id'] != '170000000') && ($dd[$key]['id'] != '170010002'))
				$rr['collapsed'] = true;
		} 
		return $rr;
	}
}
