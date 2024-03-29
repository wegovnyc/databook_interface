<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\OrgsDatasets;
use App\Custom\UnDatasets;
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
    public function orgsChart($id=null)
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('orgsChart', [
					'url' => $model->url('SELECT * FROM wegov_orgs WHERE "type" IN (\'City Agency\', \'Elected Office\', \'Boards and Comissions\', \'Classification\', \'Community Board\', \'Official\') ORDER BY name'),
					'breadcrumbs' => Breadcrumbs::orgs(),
					'defType' => $_GET['type'] ?? 'City Agency',
					'defTag' => $_GET['tag'] ?? null,
					'defSearch' => $_GET['search'] ?? null,
					'defId' => $id
				]);
    }


    /**
     * Show organizations list.
     *
     * @return \Illuminate\View\View
     */
    public function orgsDirectory()
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('orgsDirectory', [
					'url' => $model->url('SELECT * FROM wegov_orgs WHERE "type" IN (\'City Agency\', \'City Fund\', \'Community Board\', \'Economic Development Organization\', \'Elected Office\', \'State Agency\') ORDER BY name'),
					'breadcrumbs' => Breadcrumbs::orgs(),
					'defType' => $_GET['type'] ?? 'City Agency',
					'defTag' => $_GET['tag'] ?? null,
					'defSearch' => $_GET['search'] ?? null,
				]);
    }


    /**
     * Show organizations list.
     *
     * @return \Illuminate\View\View
     */
    public function orgsAll()
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
        return view('orgsAll', [
					'url' => $model->url('SELECT * FROM wegov_orgs WHERE "type" NOT IN (\'Classification\', \'Official\', \'Public Figure\') ORDER BY name'),
					'breadcrumbs' => Breadcrumbs::orgs(),
					'defType' => $_GET['type'] ?? 'City Agency',
					'defTag' => $_GET['tag'] ?? null,
					'defSearch' => $_GET['search'] ?? null,
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
		if (!$org)
			return abort(404);
		if (preg_match('~Union|Bargaining Unit~si', $org['type']))
			return redirect(route('orgSection', ['id' => $id, 'section' => 'civil-service-titles']));
		
		$ds = new OrgsDatasets();
		return view('organization', [
						'id' => $id,
						'org' => $org,
						'slist' => $ds->list,
						'menu' => $ds->menu,
						'activeDropDown' => '',
						'icons' => $ds->socicons,
						'allDS' => $ds->dd,
						'tableStatUrls' => [
							'reg' => $model->url("SELECT count(*) FROM tablename WHERE \"wegov-org-id\"='{$id}'"),
							'notices' => $model->url("SELECT count(*) FROM crol WHERE \"wegov-org-id\"='{$id}' AND \"SectionName\"='sectionTitle'"),
							'noticesEvents' => $model->url("SELECT count(*) FROM crol WHERE \"wegov-org-id\"='{$id}' AND NOT \"EventDate\" = ''"),
						],
						'finStatUrls' => [
							'headcount' => $model->url("SELECT sum(\"HEADCOUNT\") FROM headcountactualsfunding WHERE \"wegov-org-id\"='{$id}' AND \"FISCAL YEAR\"=fyear"),
							'as' => $model->url("SELECT sum(\"AMOUNT\" * 1000) FROM expenseactualsfunding WHERE \"wegov-org-id\"='{$id}' AND \"FISCAL YEAR\"=fyear"),
							'ac' => $model->url("SELECT sum(\"TOTAL AMOUNT\" * 1000) FROM additionalcostsallocation WHERE \"wegov-org-id\"='{$id}' AND \"FISCAL YEAR\"=fyear"),
						],
						'finStatYear' => 2020,
						'breadcrumbs' => Breadcrumbs::org($id, $org['name']),
						'news' => $model->crolNews($id),
						'events' => $model->crolEvents($id),
						'datasets' => $ds->data_sources($model->carto->req('SELECT * FROM data_sources'), $id),
					]);
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
		$org = $model->org($id);
		$ds = preg_match('~Union|Bargaining Unit~si', $org['type'])
				? new UnDatasets()
				: new OrgsDatasets();
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
					'url' => ($details['sql'] ?? null) 
								? $model->url(sprintf($details['sql'], $id))
								: $model->url("SELECT * FROM {$details['table']} WHERE \"wegov-org-id\"='{$id}'"),
					'dataset' => $model->dataset($details['fullname']),
					'breadcrumbs' => Breadcrumbs::orgSect($org['id'], $org['name'], $section, $ds->list[$section]),
					'details' => $details,
					'map' => $details['map'] ?? null,
				])
			: abort(404);
    }



    /**
     * Show organization notice subsection.
     *
     * @param  int  	$id
     * @param  string  	$subsection
     * @return \Illuminate\View\View
     */
    public function orgNoticesSection($id, $subsection)
	{
		return $this->orgSection($id, "notices/{$subsection}");
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
						'#projects_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate'"),
						'#orig_cost' => $model->url("SELECT sum(\"BUDG_ORIG\") RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate'"),
						'#curr_cost' => $model->url("SELECT sum(cast(REPLACE(\"BUDG_CURR\", ',', '.') as decimal)) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate'"),
						'#over_budg_am' => $model->url("SELECT -sum(cast(\"BUDG_DIFF\" as decimal)) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate' AND cast(\"BUDG_DIFF\" as decimal) < 0"),
						'#long_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate' AND \"DURATION_DIFF\" <> '-' AND cast(\"DURATION_DIFF\" as decimal) < 0"),
						'#over_budg_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate' AND cast(\"BUDG_DIFF\" as decimal) < 0"),
						'#late_start_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate' AND \"START_DIFF\" <> '-' AND cast(REPLACE(\"START_DIFF\", ',', '.') as decimal) < 0"),
						'#late_end_no' => $model->url("SELECT count(*) RES FROM capitalprojectsdollarscomp WHERE \"wegov-org-id\" = {$id} AND \"PUB_DATE\"='pubdate' AND \"END_DIFF\" <> '-' AND cast(REPLACE(\"END_DIFF\", ',', '.') as decimal) < 0"),
					],
				])
			: abort(404);
    }


    /**
     * Show organization capital project.
     *
     * @param  int  	$id
     * @param  string  	$prjId
     * @return \Illuminate\View\View
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
					'dataset' => $model->dataset($details['fullname']),
					'breadcrumbs' => Breadcrumbs::orgPrj($org['id'], $org['name'], $section, $ds->list[$section], $prjId, $data['name']),
					'data' => $data,
					'map' => true,
				])
			: abort(404);
    }
     */
	
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
					'pagetitle' => "{$data['name']} | {$prjId}",
					'org' => $org,
					'section' => $section,
					'slist' => $ds->list,
					'menu' => $ds->menu,
					'activeDropDown' => $ds->menuActiveDD($section),
					'icons' => $ds->socicons,
					'dataset' => $model->dataset($details['fullname']),
					'breadcrumbs' => Breadcrumbs::orgPrj($org['id'], $org['name'], $section, $ds->list[$section], $prjId, $data['name']),
					'data' => $data,
					'map' => true,
				])
			: abort(404);
    }

    /**
     * Show events ical feed.
     *
     * @return \Illuminate\View\View
     */
    public function ical($id)
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$data = $model->carto->req("SELECT * FROM crol WHERE \"wegov-org-id\"='{$id}' AND NOT \"EventDate\" = '' AND DATE(\"EventDate\") >= DATE(NOW() - INTERVAL '1 week') ORDER BY date(\"EventDate\") DESC");
		return response()->view('icalevents', [
					'data' => $data,
					'agencyName' => $data[0]['wegov-org-name'],
					'dataset' => $model->dataset('City Record Online (CROL)'),
				])
				->header('Content-type', 'text/calendar')
			;
    }	

	
    /**
     * Return news rss feed.
     *
     * @return \Illuminate\View\View
     */
    public function rss($id)
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$data = $model->carto->req("SELECT c.* FROM crol c WHERE \"wegov-org-id\"='{$id}' AND \"EventDate\" = '' AND DATE(\"StartDate\") >= DATE(NOW() - INTERVAL '1 week') ORDER BY date(\"StartDate\") DESC");
		return response()->view('rss', [
					'data' => $data,
					'agencyName' => $data[0]['wegov-org-name'],
					'dataset' => $model->dataset('City Record Online (CROL)'),
				])
				->header('Content-type', 'text/xml; charset=utf-8')
				;
    }
	
}
