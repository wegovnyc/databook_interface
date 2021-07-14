<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\ProjectsDatasets;
use App\Custom\Breadcrumbs;


class Projects extends Controller
{
    /**
     * Show districts main view.
     *
     * @return \Illuminate\View\View
     */
    public function main()
    {
		$ds = new ProjectsDatasets();
		$details = $ds->get('main');
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('projects', [
					'breadcrumbs' => Breadcrumbs::projects(),
					//'slist' => $ds->list,
					'url' => $model->url("SELECT * FROM {$details['table']} ORDER BY \"PROJECT_ID\", \"PUB_DATE\" DESC"),
					'details' => $details,
					'dataset' => $model->dataset($details['fullname']),
					'finStatUrls' => [
						'#budget_totals' => $model->url("SELECT sum(cast(REPLACE(\"BUDG_CURR\", ',', '.') as decimal)) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate'"),
						'#prj_count' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate'"),
						//'#over_budg_count' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate' AND cast(REPLACE(\"BUDG_DIFF\", ',', '.') as decimal) < 0"),
						//'#delayed_count' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate' AND \"END_DIFF\" <> '-' AND cast(REPLACE(\"END_DIFF\", ',', '.') as decimal) < 0"),
					],
					'map' => true,
				]);
    }
	

    /**
     * Show capital project.
     *
     * @param  string  	$prjId
     * @return \Illuminate\View\View
     */
    public function orgProject($prjId)
    {
		$section = 'capitalprojects';
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$ds = new OrgsDatasets();
		$org = $model->org($id);
		$details = $ds->get($section);
		$data = CapProjectsBuilder::build($model->capitalProjects($id, $prjId), $model->capitalProjectsMilestones($id, $prjId));
		return $org && $details
			? view('orgproject', [
					'id' => $id,
					'prjId' => $prjId,
					'org' => $org,
					'section' => $section,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'activeDropDown' => $ds->menuActiveDD($section),
					'icons' => $ds->socicons,
					//'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-org-id\"='{$id}'" . ($section == 'crol' ? ' ORDER BY date("StartDate")' : '')),
					'dataset' => $model->dataset($details['fullname']),
					'breadcrumbs' => Breadcrumbs::orgPrj($org['id'], $org['name'], $section, $ds->list[$section], $prjId, $data['name']),
					//'details' => $details,
					'data' => $data,
					'map' => true,
				])
			: abort(404);
    }
}
	
}
