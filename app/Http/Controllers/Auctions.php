<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\AuctionsDatasets;
use App\Custom\Breadcrumbs;


class Auctions extends Controller
{
    /**
     * Show auctions main view.
     *
     * @return \Illuminate\View\View
     */
    public function main()
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$ds = new AuctionsDatasets();
		$details = $ds->get('auctions');
        return $details
			? view('auctions', [
					'url' => $model->url($details['sql']),
					'breadcrumbs' => Breadcrumbs::auctions(),
					'details' => $details,
				])
			: abort(404);
    }
}
