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
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$orgs = $model->orgs('WHERE "type" IN (\'Boards and Comissions\', \'City Agency\', \'Classification\', \'Community Board\', \'Elected Office\', \'Official\')');
		#$airtable = new Airtable(config('apis.orgs_doc_id'), config('apis.airtable_key'));
		#$orgs = $airtable->readTableFlat(config('apis.orgs_doc_tbl'), '', '&view=' . config('apis.orgs_doc_view'));
		#file_put_contents('orgs.json', json_encode($orgs));
		$mm = [];
		
		foreach ($orgs as $i=>$org)
		{
			if (is_string($org['child_of']))
			{
				$org['child_of'] = json_decode(str_replace('""', '"', $org['child_of']), true);
				$orgs[$i] = $org;
			}	
			#$mm[$org['_id']] = $org + ['children' => []];
			$mm[$org['airtable_id']] = $org + ['children' => []];
			
		}
		$rootId = '';
		echo "\n===== " . date('Y-m-d H:i:s') . " =====================\n";
		foreach ($orgs as $org)
		{
			#$parent_id = preg_replace('~[\[\]"]~si', '', $org['child_of']);
			$parent_id = ($org['child_of'] ?? null) ? $org['child_of'][0] : null;
			if ($org['id'] == '170000000')
				#$rootId = $org['_id'];
				$rootId = $org['airtable_id'];
			if (!$parent_id)
				continue;
			#$mm[$parent_id]['children'][$org['name']] = $org['_id'];
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
		$cc = [/*'170000000' => '#000', */'170011039' => '#b4f8c8', '170010056' => '#fcb5ac', '170100000' => '#76b947', '170100001' => '#ff9636', '170100007' => '#05e0e9', '170100012' => '#b99095', '170100016' => '#fede00', '170100017' => '#96ad90', '170100030' => '#c8df52', '170100035' => '#ffe9e4', '170100036' => '#647c90',
		'170010010' => '#274472', '170010011' => '#41729f', '170010012' => '#5885af', '170010013' => '#189ab4', '170010014' => '#75e6da', '170010103' => '#a45c40', '170020021' => '#603f8b', '170011000' => '#bfd7ed'
		];
		
		$familyClass = ($cc[$dd[$key]['id']] ?? null) ? "node_{$dd[$key]['id']}" : $familyClass ?? null;
		$forceGray = preg_match('~Classification|Official~si', $dd[$key]['type']) || !$dd[$key]['datasets_count'];
		$rr = ['name' => $forceGray ? "<a>{$dd[$key]['name']}</a>" : "<a href=\"/agency/{$dd[$key]['id']}\">{$dd[$key]['name']}</a>", 
			   'className' => $forceGray ? 'node_def' : ($familyClass ?? 'node_black')
			  ];
		if ($dd[$key]['children'])
		{
			$children = [];
			uksort($dd[$key]['children'], function ($a, $b) {
				$idx = ['Elected County Officials', 'Mayor\'s Office', 'Office of the Comptroller', 'Public Advocate', 'Independent Budget Office', 'City Council'];
				$idx = array_combine(array_values($idx), array_keys($idx));

				$a = $idx[$a] ?? $a;
				$a = preg_match('~\d~', $a) ? (int)preg_replace('~\D~si', '', $a) : $a;

				$b = $idx[$b] ?? $b;
				$b = preg_match('~\d~', $b) ? (int)preg_replace('~\D~si', '', $b) : $b;

				return $a <=> $b;
			});
			foreach (array_values($dd[$key]['children']) as $childId)
				$children[] = self::packnode($dd, $childId, $familyClass);
			$rr['children'] = $children;
		} 
		return $rr;
	}
}
