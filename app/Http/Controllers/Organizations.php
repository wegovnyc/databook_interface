<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\Datasets;

class Organizations extends Controller
{
    /**
     * Show organizations list.
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
		$model = new CartoModel(config('carto.entry'), config('carto.key'));
        return view('organizations', ['url' => $model->url('SELECT * FROM wegov_orgs ORDER BY name')]);
    }


    /**
     * Show organization profile - section about.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function about($id)
    {
		$model = new CartoModel(config('carto.entry'), config('carto.key'));
		$org = $model->org($id);
		$ds = new Datasets();
        return $org 
			? view('organization', [
					'id' => $id, 
					'org' => $org,
					'slist' => $ds->list,
					'icons' => $ds->socicons, 
				])
			: abort(404);
    }


    /**
     * Show organization profile section.
     *
     * @param  int  	$id
     * @param  string  	$section
     * @return \Illuminate\View\View
     */
    public function section($id, $section)
    {
		$model = new CartoModel(config('carto.entry'), config('carto.key'));
		$ds = new Datasets();
		$org = $model->org($id);
		$details = $ds->get($section);
		return $org && $details
			? view('orgsection', [
					'id' => $id, 
					'org' => $org, 
					'section' => $section,
					'slist' => $ds->list, 
					'icons' => $ds->socicons, 
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-org-id\"={$id}"),
					'dataset' => $model->dataset($details['fullname']),
					'details' => $details
				])
			: abort(404);
    }
}