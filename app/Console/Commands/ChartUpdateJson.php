<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Custom\CartoModel;
use App\Custom\Airtable;

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
		#$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		#$orgs = $model->orgs('WHERE "type" IN (\'City Agency\', \'Elected Office\', \'Boards and Comissions\', \'Classification\', \'Community Board\', \'Official\')');
		$airtable = new Airtable(config('apis.orgs_doc_id'), config('apis.airtable_key'));
		$orgs = $airtable->readTableFlat(config('apis.orgs_doc_tbl'), '', '&view=' . config('apis.orgs_doc_view'));
		
		$mm = [];
		
		foreach ($orgs as $org)
			$mm[$org['_id']] = $org + ['children' => []];
		$rootId = '';
		echo "\n===== " . date('Y-m-d H:i:s') . " =====================\n";
		foreach ($orgs as $org)
		{
			#$parent_id = preg_replace('~[\[\]"]~si', '', $org['child_of']);
			$parent_id = ($org['child_of'] ?? null) ? $org['child_of'][0] : null;
			if ($org['id'] == '170000000')
				$rootId = $org['_id'];
			if (!$parent_id)
				continue;
			$mm[$parent_id]['children'][$org['name']] = $org['_id'];
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
		$cc = [/*'170000000' => '#000', */'170011039' => '#b4f8c8', '170010056' => '#fcb5ac', '170100000' => '#76b947', '170100001' => '#ff9636', '170100007' => '#05e0e9', '170100012' => '#b99095', '170100016' => '#fede00', '170100017' => '#96ad90', '170100030' => '#c8df52', '170100035' => '#ffe9e4', '170100036' => '#647c90',
		'170010010' => '#274472', '170010011' => '#41729f', '170010012' => '#5885af', '170010013' => '#189ab4', '170010014' => '#75e6da', '170010103' => '#a45c40', '170020021' => '#603f8b', '170011000' => '#bfd7ed'
		];
		
		$familyClass = ($cc[$dd[$key]['id']] ?? null) ? "node_{$dd[$key]['id']}" : $familyClass ?? null;
		$rr = ['name' => "<a href=\"/agency/{$dd[$key]['id']}\">{$dd[$key]['name']}</a>", 
			   'className' => $familyClass ?? ($dd[$key]['id'] == '170000000' ? "node_170000000" : 'node_def')
			  ];
		if ($dd[$key]['children'])
		{
			$children = [];
			uksort($dd[$key]['children'], function ($a, $b) {
			if (($a == 'Mayor\'s Office') || ($b == 'District Attorneys'))
					return -1;
				if (($b == 'Mayor\'s Office') || ($a == 'District Attorneys'))
					return 1;
				return $a <=> $b;
			});
			foreach (array_values($dd[$key]['children']) as $childId)
				$children[] = self::packnode($dd, $childId, $familyClass);
			$rr['children'] = $children;
			#if ($children && !$familyClass && ($dd[$key]['id'] != '170000000') && ($dd[$key]['id'] != '170010002'))
			#if ($children && ($dd[$key]['id'] == '170010009'))
			#	$rr['collapsed'] = true;
		} 
		return $rr;
	}
}
