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
        return view('districts', [
					'type' => $type ?? 'cc',
					'id' => $id ?? '1',
					'section' => $section ?? 'nyccouncildiscretionaryfunding',
					'breadcrumbs' => Breadcrumbs::districts(),
					'slist' => $ds->list,
					'map' => ['cc' => 'inherit', 'cd' => 'inherit', 'nta' => 'inherit']
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
				])
			: abort(404);
    }
}
