<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\TitlesDatasets;
use App\Custom\Breadcrumbs;


class Titles extends Controller
{

    /**
     * Show districts main view.
     *
     * @return \Illuminate\View\View
     */
    public function main()
    {
		$ds = new TitlesDatasets();
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('titles', [
					'breadcrumbs' => Breadcrumbs::titles(),
					'slist' => $ds->list,
					'url' => $model->url('SELECT * FROM nyccivilservicetitles ORDER BY "Title Code"'),
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
		$ds = new TitlesDatasets();
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$titles = $model->titles($id);
		$details = $ds->get($section);
		return $details && $titles
			? view('titlesection', [
					'id' => $id,
					'section' => $section,
					'titles' => $titles,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'breadcrumbs' => Breadcrumbs::titleSect($titles[0]['Title Code'], $titles[0]['Title Description'], $section, $ds->list[$section]),
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-service-title-id\"='{$id}' ORDER BY {$details['sort'][0]}" ),
					'dataset' => $model->dataset($details['fullname']),
					'details' => $details,
				])
			: abort(404);
    }
}
