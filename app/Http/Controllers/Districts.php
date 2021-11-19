<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\DistDatasets;
use App\Custom\Breadcrumbs;


class Districts extends Controller
{
    /**
     * Show districts main view.
     *
     * @return \Illuminate\View\View
     */
    public function main($type=null, $id=null, $section=null)
    {
		$ds = new DistDatasets();
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('districts', [
					//'type' => $type ?? 'cc',
					'type' => $type ?? null,
					'id' => $id ?? null,
					'section' => $section ?? 'nyccouncildiscretionaryfunding',
					'breadcrumbs' => Breadcrumbs::districts(),
					'slist' => $ds->list,
					'map' => ['cc' => 'inherit', 'cd' => 'inherit', 'nta' => 'inherit'],
					'prjUrl' => $model->url('SELECT "GEO_JSON", "wegov-org-id" FROM capitalprojectsdollarscomp WHERE "PUB_DATE" = (SELECT DISTINCT "PUB_DATE" pd FROM capitalprojectsdollarscomp ORDER BY pd DESC LIMIT 1) AND "GEO_JSON" != \'\'')
				]);
    }
	
    /**
     * Show district section.
     *
     * @return \Illuminate\View\View
     */
    public function section($type, $id, $section)
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$ds = new DistDatasets();
		$details = $ds->get($section, $type);
		return $details
			? view('distsection', [
					'type' => $type,
					'id' => $id,
					'section' => $section,
					'slist' => $ds->list,
					'menu' => $ds->menu($type),
					'activeDropDown' => $ds->menuActiveDD($section),
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"{$details['map'][$type]}\"='{$id}' ORDER BY {$details['sort'][0]}, {$details['sort'][1]}" ),
					'dataset' => $model->dataset($details['fullname']),
					'member' => (($type == 'cc') and ($id <> 'undefined')) ? $model->ccMember($id) : [],
					'details' => $details,
					'linkedAgencyUrl' => 
						$type == 'cd'
							? $model->url('SELECT * FROM wegov_orgs WHERE ' . ['cc' => '"cityCouncilDistrictId"', 'cd' => '"communityDistrictId"'][$type] . " = '[\"\"{$id}\"\"]'")
							: '',
					'altName' => $type == 'cd' ? $ds->cdAltName[$id] ?? null : null,
				])
			: abort(404);
    }


    /**
     * Show organization capital projects section.
     *
     * @param  int  	$id
     * @return \Illuminate\View\View
     */
    public function projectSection($type, $id)
    {
		$section = 'capitalprojects';
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$ds = new DistDatasets();
		$details = $ds->get($section, $type);
		return $details
			? view('distprojectsection', [
					'type' => $type,
					'id' => $id,
					'section' => $section,
					'slist' => $ds->list,
					'menu' => $ds->menu($type),
					'activeDropDown' => $ds->menuActiveDD($section),
					'url' => $model->url("SELECT pp.*, i.\"DIST\" FROM {$details['table']} pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}'"),
					'dataset' => $model->dataset($details['fullname']),
					'details' => $details,
					'finStatUrls' => [
						'#projects_no' => $model->url("SELECT count(pp.*) RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate'"),
						'#orig_cost' => $model->url("SELECT sum(\"BUDG_ORIG\") RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate'"),
						'#curr_cost' => $model->url("SELECT sum(cast(REPLACE(\"BUDG_CURR\", ',', '.') as decimal)) RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate'"),
						'#over_budg_am' => $model->url("SELECT -sum(cast(\"BUDG_DIFF\" as decimal)) RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate' AND cast(\"BUDG_DIFF\" as decimal) < 0"),
						'#long_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate' AND \"DURATION_DIFF\" <> '-' AND cast(\"DURATION_DIFF\" as decimal) < 0"),
						'#over_budg_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate' AND cast(\"BUDG_DIFF\" as decimal) < 0"),
						'#late_start_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate' AND \"START_DIFF\" <> '-' AND cast(REPLACE(\"START_DIFF\", ',', '.') as decimal) < 0"),
						'#late_end_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp pp INNER JOIN capitalprojects_{$type}_idx i ON pp.\"PROJECT_ID\"=i.\"PROJECT_ID\" WHERE i.\"DIST\" = '{$id}' AND \"PUB_DATE\"='pubdate' AND \"END_DIFF\" <> '-' AND cast(REPLACE(\"END_DIFF\", ',', '.') as decimal) < 0"),
					],
					'linkedAgencyUrl' => 
						$type == 'nta'
							? ''
							: $model->url('SELECT * FROM wegov_orgs WHERE ' . ['cc' => '"cityCouncilDistrictId"', 'cd' => '"communityDistrictId"'][$type] . " = '[\"\"{$id}\"\"]'"),
				])
			: abort(404);
    }

}
