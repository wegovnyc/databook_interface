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
					#'url' => $model->url("SELECT * FROM {$details['table']} ORDER BY \"PROJECT_ID\", \"PUB_DATE\" DESC"),
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"PUB_DATE\" = 'pubdate'"),
					'dates_req_url' => $model->url("SELECT DISTINCT \"PUB_DATE\" FROM {$details['table']} ORDER BY \"PUB_DATE\" DESC"),
					'details' => $details,
					'dataset' => $model->dataset($details['fullname']),
					'finStatUrls' => [
						'#projects_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate'"),
						'#orig_cost' => $model->url("SELECT sum(\"BUDG_ORIG\") RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate'"),
						'#curr_cost' => $model->url("SELECT sum(cast(REPLACE(\"BUDG_CURR\", ',', '.') as decimal)) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate'"),
						'#over_budg_am' => $model->url("SELECT -sum(cast(\"BUDG_DIFF\" as decimal)) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate' AND cast(\"BUDG_DIFF\" as decimal) < 0"),
						'#long_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate' AND \"DURATION_DIFF\" <> '-' AND cast(\"DURATION_DIFF\" as decimal) < 0"),
						'#over_budg_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate' AND cast(\"BUDG_DIFF\" as decimal) < 0"),
						'#late_start_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate' AND \"START_DIFF\" <> '-' AND cast(REPLACE(\"START_DIFF\", ',', '.') as decimal) < 0"),
						'#late_end_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"PUB_DATE\"='pubdate' AND \"END_DIFF\" <> '-' AND cast(REPLACE(\"END_DIFF\", ',', '.') as decimal) < 0"),
					],
					'map' => true,
				]);
    }		
}
