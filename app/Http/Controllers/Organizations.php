<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\OrgsDatasets;
use App\Custom\Breadcrumbs;
use App\Custom\CapProjectsBuilder;


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
		$ds = new OrgsDatasets();
        return $org
			? view('organization', [
					'id' => $id,
					'org' => $org,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'activeDropDown' => '',
					'icons' => $ds->socicons,
					'allDS' => $ds->dd,
					'tableStatUrl' => $model->url("SELECT count(*) FROM tablename WHERE \"wegov-org-id\"='{$id}'"),
					'finStatUrls' => [
						'headcount' => $model->url("SELECT sum(\"HEADCOUNT\") FROM headcountactualsfunding WHERE \"wegov-org-id\"='{$id}' AND \"FISCAL YEAR\"=fyear"),
						'as' => $model->url("SELECT sum(\"AMOUNT\") FROM expenseactualsfunding WHERE \"wegov-org-id\"='{$id}' AND \"FISCAL YEAR\"=fyear"),
						'ac' => $model->url("SELECT sum(\"TOTAL AMOUNT\") FROM additionalcostsallocation WHERE \"wegov-org-id\"='{$id}' AND \"FISCAL YEAR\"=fyear"),
					],
					'breadcrumbs' => Breadcrumbs::org($id, $org['name']),
					'crol' => $model->crol($id),
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
		$ds = new OrgsDatasets();
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
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-org-id\"='{$id}'" . ($section == 'crol' ? ' ORDER BY date("StartDate")' : '')),
					'dataset' => $model->dataset($details['fullname']),
					'breadcrumbs' => Breadcrumbs::orgSect($org['id'], $org['name'], $section, $ds->list[$section]),
					'details' => $details,
					'map' => $details['map'] ?? null, //['cc' => 8, 'nta' => 7],
				])
			: abort(404);
    }


    /**
     * Show organization capital projects section.
     *
     * @param  int  	$id
     * @return \Illuminate\View\View
     */
    public function orgProjectSection($id)
    {
		$section = 'capitalprojects';
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$ds = new OrgsDatasets();
		$org = $model->org($id);
		$details = $ds->get($section);
		return $org && $details
			? view('orgprojectsection', [
					'id' => $id,
					'org' => $org,
					'section' => $section,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'activeDropDown' => $ds->menuActiveDD($section),
					'icons' => $ds->socicons,
					'url' => $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-org-id\"='{$id}'" . ($section == 'crol' ? ' ORDER BY date("StartDate")' : '')),
					'dataset' => $model->dataset($details['fullname']),
					'breadcrumbs' => Breadcrumbs::orgSect($org['id'], $org['name'], $section, $ds->list[$section]),
					'details' => $details,
					'map' => true,
					'finStatUrls' => [
						'#budget_totals' => $model->url("SELECT sum(cast(REPLACE(\"BUDG_CURR\", ',', '.') as decimal)) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate'"),
						'#prj_count' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate'"),
						//'#over_budg_count' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate' AND \"ORIG_BUD_AMT\" > 0 AND cast(REPLACE(\"BUDG_DIFF\", ',', '.') as decimal) < 0"),
						//'#delayed_count' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate' AND \"END_DIFF\" <> '-' AND cast(REPLACE(\"END_DIFF\", ',', '.') as decimal) < 0"),
					],
				])
			: abort(404);
    }


    /**
		----- deprecated
     * Show organization capital project.
     *
     * @param  int  	$id
     * @param  string  	$prjId
     * @return \Illuminate\View\View
     */
    public function orgProject($id, $prjId)
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
	
    /**
     * Show capital project.
     *
     * @param  string  	$prjId
     * @return \Illuminate\View\View
     */
    public function project($prjId)
    {
		$section = 'capitalprojects';
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$ds = new OrgsDatasets();
		$data = CapProjectsBuilder::build($model->capitalProjects($prjId), $model->capitalProjectsMilestones($prjId));
		$id = $data['id'];
		$org = $model->org($id);
		$details = $ds->get($section);
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
