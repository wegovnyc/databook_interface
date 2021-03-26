<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\Datasets;
use App\Custom\Breadcrumbs;


class Organizations extends Controller
{
    /**
     * Show organizations list.
     *
     * @return \Illuminate\View\View
     */
    public function root()
    {
        return view('root', [
					'breadcrumbs' => Breadcrumbs::root(),
				]);
    }


    /**
     * Show organizations list.
     *
     * @return \Illuminate\View\View
     */
    public function about()
    {
        return view('about', [
					'breadcrumbs' => Breadcrumbs::about(),
				]);
    }


    /**
     * Show organizations list.
     *
     * @return \Illuminate\View\View
     */
    public function list()
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('organizations', [
					'url' => $model->url('SELECT * FROM wegov_orgs WHERE "Type" IN (\'City Agency\', \'City Fund\', \'Community Board\', \'Economic Development Organization\', \'Elected Office\', \'State Agency\') ORDER BY name'),
					'breadcrumbs' => Breadcrumbs::orgs(),
					'defType' => $_GET['type'] ?? null ? $_GET['type'] : 'City Agency',
					'defTag' => $_GET['tag'] ?? null,
				]);
    }


    /**
     * Show organization profile - section about.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function orgAbout($id)
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$org = $model->org($id);
		$ds = new Datasets();
        return $org
			? view('organization', [
					'id' => $id,
					'org' => $org,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'activeDropDown' => '',
					'icons' => $ds->socicons,
					'breadcrumbs' => Breadcrumbs::org($org['name']),
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
    public function orgSection($id, $section)
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$ds = new Datasets();
		$org = $model->org($id);
		$details = $ds->get($section);
		return $org && $details
			? view('orgsection', [
					'id' => $id,
					'org' => $org,
					'section' => $section,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'activeDropDown' => $ds->menuActiveDD($section),
					'icons' => $ds->socicons,
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-org-id\"={$id}"),
					'dataset' => $model->dataset($details['fullname']),
					'breadcrumbs' => Breadcrumbs::orgSect($org['id'], $org['name'], $ds->list[$section]),
					'details' => $details,
				])
			: abort(404);
    }
}
