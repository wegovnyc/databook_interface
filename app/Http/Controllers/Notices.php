<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Custom\CartoModel;
use App\Custom\CROLDatasets;
use App\Custom\Breadcrumbs;


class Notices extends Controller
{
    /**
     * Show notices main view.
     *
     * @return \Illuminate\View\View
     */
    public function main()
    {
		$ds = new CROLDatasets();
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$details = $ds->get('events');
        return view('notices', [
					'breadcrumbs' => Breadcrumbs::notices(),
					'slist' => $ds->list,
					'url' => $model->url('SELECT * FROM crol WHERE NOT "EventDate" = \'\' AND DATE("EventDate") >= current_date ORDER BY date("EventDate")'),
					'details' => $details,
					'dataset' => $model->dataset($details['fullname']),
					'news' => $model->crolNews(),
					'auctions' => $model->carto->req('SELECT * FROM auctions WHERE date("Auction Ends") > date(now()) ORDER BY "Auction Ends" LIMIT 3'),
					'statUrls' => [
						'#publichearings1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Public Hearings and Meetings\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#publichearings7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Public Hearings and Meetings\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#publichearings30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Public Hearings and Meetings\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),

						'#contractawards1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Contract Award Hearings\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#contractawards7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Contract Award Hearings\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#contractawards30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Contract Award Hearings\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),
						
						'#specialmaterials1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Special Materials\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#specialmaterials7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Special Materials\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#specialmaterials30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Special Materials\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),

						'#agencyrules1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Agency Rules\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#agencyrules7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Agency Rules\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#agencyrules30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Agency Rules\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),

						'#propertydisposition1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Property Disposition\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#propertydisposition7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Property Disposition\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#propertydisposition30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Property Disposition\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),

						'#courtnotices1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Court Notices\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#courtnotices7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Court Notices\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#courtnotices30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Court Notices\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),

						'#procurement1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Procurement\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#procurement7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Procurement\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#procurement30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Procurement\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),

						'#changeofpersonnel1' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Changes in Personnel\' AND NOT "AdditionalDescription1" = \'\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 days\')'),
						'#changeofpersonnel7' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Changes in Personnel\' AND NOT "AdditionalDescription1" = \'\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'7 days\')'),
						'#changeofpersonnel30' => $model->url('SELECT COUNT(*) RES FROM crol WHERE NOT "StartDate" = \'\' AND "SectionName" = \'Changes in Personnel\' AND NOT "AdditionalDescription1" = \'\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'30 days\')'),
					]
				]);
    }
	
    /**
     * Show notice section.
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
					'dates_req_url' => $model->url(
						($details['dates_req_sql'] ?? null)
						? $details['dates_req_sql']
						: "SELECT DISTINCT(SUBSTRING(\"StartDate\" from 7 for 4)) yy FROM crol WHERE \"SectionName\" = '{$details['CROLsection']}' ORDER BY yy DESC"
					),
					'dataset' => $model->dataset($details['fullname']),
					'details' => $details,
				])
			: abort(404);
    }
	
    /**
     * Return events ical feed.
     *
     * @return \Illuminate\View\View
     */
    public function ical()
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$data = $model->carto->req('SELECT * FROM crol WHERE NOT "EventDate" = \'\' AND DATE("EventDate") >= DATE(NOW() - INTERVAL \'1 week\') ORDER BY date("EventDate") DESC');
		return response()->view('icalevents', [
					'data' => $data,
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
    public function rss()
    {
		$model = new CartoModel(config('apis.carto_entry'), config('apis.carto_key'));
		$data = $model->carto->req('SELECT c.* FROM crol c WHERE "EventDate" = \'\' AND DATE("StartDate") >= DATE(NOW() - INTERVAL \'1 week\') ORDER BY date("StartDate") DESC');
		return response()->view('rss', [
					'data' => $data,
					'dataset' => $model->dataset('City Record Online (CROL)'),
				])
				->header('Content-type', 'text/xml; charset=utf-8')
				;
    }
}