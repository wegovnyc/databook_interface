<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\CROLDatasets;
use App\Custom\Breadcrumbs;


class Notices extends Controller
{
    /**
     * Show districts main view.
     *
     * @return \Illuminate\View\View
     */
    public function main()
    {
		$ds = new CROLDatasets();
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
    public function section($section)
    {
		$ds = new CROLDatasets();
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$details = $ds->get($section);
		return $details
			? view('noticessection', [
					'section' => $section,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'breadcrumbs' => Breadcrumbs::noticesSect($section, $ds->list[$section]),
					'url' => $model->url($details['sql']),
					'dataset' => $model->dataset($details['fullname']),
					'details' => $details,
				])
			: abort(404);
    }
}
