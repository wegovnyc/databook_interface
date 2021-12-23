<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\PosDatasets;
use App\Custom\Breadcrumbs;


class Positions extends Controller
{

    /**
     * Show districts main view.
     *
     * @return \Illuminate\View\View
     */
    public function main()
    {
		$ds = new PosDatasets();
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('positions', [
					'breadcrumbs' => Breadcrumbs::positions(),
					'slist' => $ds->list,
					'url' => $model->url('SELECT * FROM wegov_caploc_civil_titles ORDER BY "Title Description"'),
					'defSearch' => $_GET['search'] ?? null,
					'defUnion' => $_GET['union'] ?? '',
				]);
    }
	
    /**
     * Show district section.
     *
     * @return \Illuminate\View\View
     */
    public function section($id, $section)
    {
		$ds = new PosDatasets();
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$pos = $model->pos($id);
		$details = $ds->get($section);
		return $details && $pos
			? view('possection', [
					'id' => $id,
					'section' => $section,
					'pos' => $pos,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'breadcrumbs' => Breadcrumbs::posSect($pos['Title Code'], $pos['Title Description'], $section, $ds->list[$section]),
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-service-title-id\"='{$id}' ORDER BY {$details['sort'][0]}" ),
					'dataset' => $model->dataset($details['fullname']),
					'details' => $details,
				])
			: abort(404);
    }
}
