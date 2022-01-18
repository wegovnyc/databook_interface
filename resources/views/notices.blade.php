@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')

	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>
	
	<script>
		function details(r) {
			return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
			  @foreach ((array)$details['details'] as $h=>$f)
				(r["{{ $f }}"] ? ('<tr><td>{{ $h }}:</td><td>' + r["{{ $f }}"] + '</td></tr>') : '') +
			  @endforeach
			'</table>';
		}
				
		var datatable = null
		var dataurl = '{!! $url !!}'
		
		$(document).ready(function() {
			
			/* custom pub_date filter on top-right */
			{{--/*
			$.get("{!! $dates_req_url !!}", function (resp) {
				var select = $('<select class="filter mt-1" style="width:100%;" id="filter-1" name="filter-1" aria-controls="myTable"><option value="" selected>- Publication Date -</option></select>')
					.appendTo($("#pub_date_filter"))
					.on('change', function () {
						var val = $(this).val()
						$('.loading').show()
						datatable.ajax.url(dataurl.replace('pubdate', val)).load(function () {
							$('.loading').hide()
							loadStat()
						});
					});
				select.wrap('<div class="drop_dowm_select"></div>');
				resp['rows'].forEach(function (d, j) {
					select.append(`<option value="${d['PUB_DATE']}" ${ j == 0 ? 'selected' : ''}>${toDashDate(d['PUB_DATE'])}</option>`)
				});
			*/--}}
				loadStat();
				datatable = $('#myTable').DataTable({
					ajax: {
						url: '{!! $url !!}',
						dataSrc: 'rows'
					},
					buttons: [{
						extend: 'colvis',
						"className": 'btn_eyeicon',
						columnText: function ( dt, idx, title ) {
							return (idx+1)+': '+(title ? title : 'details');
						}
					}],
					deferRender: true,
					dom: '<"toolbar container-flex"<"row">>Blfrtip',
					columns: [
						@if ($details['detFlag'])
							{
								"className": 'details-control',
								"orderable": false,
								"data":  null,
								"defaultContent": ''
							},
						@endif
						@foreach ($details['flds'] as $i=>$f)
							@if ($i > 0)
								,
							@endif
							{
							data: {!! $f !!},
							@if (preg_match('~^function ~i', $f))
								type: 'html',
							@endif
							@if ($details['visible'][$i])
								visible: true
							@else
								visible: false
							@endif
							}
						@endforeach
						,
						{
							className: 'record',
							data:  null,
							defaultContent: null,
							visible: false,
							searchable: false
						}
					]
					/*
					,
					createdRow: function(row, data, dataIndex) {
						if (data.GEO_JSON != '') {
							$(row).addClass('have_coords');
						}
					}
					*/

					@if ($details['filters'])
						,
						initComplete: function () {
							this.api().columns([{{ $details['fltsCols'] }}]).every(function (c,a,i) {
								var delim = {!! json_encode($details['fltDelim']) !!};
								var column = this;
								var select = $('<select class="filter" id="filter-' + column[0][0] + '" name="filter-' + column[0][0] + '" aria-controls="myTable"><option value="" selected>- ' + $(column.header()).text() + ' -</option></select>')
									.appendTo($("div.toolbar .row"))
									.on('change', function () {
										var val = $(this).val()
										column
											.search(val ? val : '', false, false)
											.draw();
									});
								select.wrap('<div class="drop_dowm_select col"></div>');
								$('div.toolbar').insertAfter('#myTable_filter');

								var tt = []
								dd = column.data()

								column.data().each(function (d, j) {
									d = typeof d == 'string' ? d.replace(/<[^>]+>/gi, '') : d
									if (c in delim && typeof d == 'string') {
										d.split(delim[c]).forEach(function (v, k) {
											tt.push(v)
										})
									}
									else
										tt.push(d)
								})
								tt = [...new Set(tt)]

								tt.sort().forEach(function (d, j) {
									if (d)
										select.append('<option value="'+d+'">'+d+'</option>')
								});
							});
							/*
							$("div.toolbar .row").append('<button id="map_button" class="btn map_btn col" style="margin:0 20px 0 10px; z-index: 10; max-width: 40px;" onclick="toggleMap();"><img src="/img/map_location.png" alt=""></button>');*/

							@foreach ($details['filters'] as $i=>$v)
								@if ($v)
									setTimeout(function(){
										$('#filter-{{ $i }}').find('[value*="{!! $v !!}"]').prop('selected',true).trigger('change')
									}, 500 + 1000 * {{ $i }});
								@endif
							@endforeach
							/*
							setTimeout(function() {
									//datatable.draw();	// initiate projectsMapDrawFeatures
									//drawProjects('all');
									toggleMap();
								}, 500
							);
							setTimeout(function(){
								initPopovers();
							}, 1000);
							*/
						}
					@endif
					
					@if ($details['order'] ?? null)
						,
						order: {!! json_encode($details['order']) !!}
					@endif
				});
				
				$('.btn_eyeicon').hide();
				
				$('a.toggle-vis').on('click', function (e) {
					e.preventDefault();
					var column = datatable.column($(this).attr('data-column'));
					column.visible(!column.visible());
				});
				
				$('#myTable tbody').on('click', 'td.details-control', function () {
					var tr = $(this).closest('tr');
					var row = datatable.row(tr);

					if (row.child.isShown()) {
						row.child.hide();
						tr.removeClass('shown');
						tr.next('tr').removeClass('child-row');
					}
					else {
						row.child(details(row.data())).show();
						tr.addClass('shown');
						tr.next('tr').addClass('child-row');
						initPopovers();
					}
				});

				$('#myTable_length label').html($('#myTable_length label').html().replace(' entries', ''));
				
				/*
				// if map is displayed updates and draws projects from GEO_JSON field
				datatable.on('draw', function () {
					drawProjects('current');
				});
				*/
				$('#myTable tbody').on('click', 'td:not(.details-control)', function () {
					var mapIsActive = !$('#map_container').attr('style')
					if (!mapIsActive) 
						return;
					var tr = $(this).closest('tr');
					var row = datatable.row(tr);
					r = row.data()
					if (r['GEO_JSON']) {
						var geo_json = JSON.parse(r['GEO_JSON'].replaceAll('""', '"'))
						var pr = geo_json.properties
						fitBounds([[pr.W, pr.S], [pr.E, pr.N]])
					}
				})
				
				// makes sortable html fields like 9.4 years late, $25,764 over
				$.fn.dataTable.ext.type.order['html-pre'] = function (data) {
					var d = data.replace(/>-</g, '>0<');
					d = d.replace(/<span class="(bad)"[^>]*>/g, '-');
					d = d.replace(/[,$]|years|late|<[^>]+>|earl\S+|%/g, '');
					d = d.replace(/NA|NaN|on time/g, '0');
					m = 1
					for (const[rg, tmpM] of [[/K$/g, 1000], [/M$/g, 1000000], [/B$/g, 1000000000]]) {
						if (d.match(rg)) {
							m = tmpM;
							d = d.replace(rg, '');
						}
					}
					d = d.match(/^[-\d\.]+$/g) ? parseFloat(d) * m : d;
					//console.log(data, d);
					return d;
				};
				
		/*	})	*/

		});

		{{--
		function toggleMap() {
			var isActive = !$('#map_container').attr('style')
			var cc = [{{ $details['hide_on_map_open'] }}];
			if (isActive) {
				$('#map_button').show()
				$('#data_container').attr('class', 'col')
				$('.toolbar ').show()
				$('#map_container').hide()
				$('#myTable').dataTable().api().columns(cc).every(function () {
					this.visible(true);
				});
			} else {
				$('#map_button').hide()
				$('#data_container').attr('class', 'col col-6')
				$('#map_container').show()
				projectsMapInit();
				$('#myTable').dataTable().api().columns(cc).every(function () {
					this.visible(false);
				});
				/*
				setTimeout(function() {
						drawProjects('all');
					}, 2500
				);*/
			}
			initPopovers();
		}
		--}}
		
		function loadStat() {
			var uu = {!! json_encode($statUrls) !!}
			for (let sel in uu) {
				$.get(uu[sel], function (resp) {
					var v = resp['rows'][0]['res'] ?? '-'
					$(sel).text(v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","))
				})
			}
			{{--
			setTimeout(function(){
				initPopovers();
			}, 1000);--}}
		}
		{{--/*
		function drawProjects(pages) {	// 'all',     'current'
			var mapIsActive = !$('#map_container').attr('style')
			if (!mapIsActive) 
				return;
			
			var api = $('#myTable').dataTable().api();
			var modifier = {
				order:  'current',  // 'current', 'applied', 'index',  'original'
				page:   pages,      // 'all',     'current'
				search: 'applied',     // 'none',    'applied', 'removed'
			}
			var features = [];
			api.rows('', modifier).data().each(function (r, i) {
				if (r['GEO_JSON']) {
					try {
						geo_json = JSON.parse(r['GEO_JSON'].replaceAll('""', '"'))
						geo_json.properties['AG_ID'] = r['wegov-org-id']
						if (geo_json.geometry.type != 'MultiPolygon') 
							features.push(geo_json)
						else {
							geo_json.geometry.coordinates.forEach(function (c) {
								var subgj = geo_json
								subgj.geometry.type = 'Polygon'
								subgj.geometry.coordinates = c
								features.push(subgj)
							})
						}
					} catch (error) {
						console.error(error);
					}
				}
			});
			console.log(features.length)
			projectsMapDrawFeatures(features);
		}
		*/--}}
	</script>

	<div class="inner_container">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-md-11 organization_data">
					<h4>The City Record Remix</h4>
					<p>The City publishes its “<a href="https://en.wikipedia.org/wiki/Government_gazette" target="_blank">official journal</a>” in print, <a href="https://www1.nyc.gov/site/dcas/about/city-record.page" target="_blank">PDF</a>, a <a href="https://a856-cityrecord.nyc.gov/" target="_blank">website</a> and as <a href="https://data.cityofnewyork.us/City-Government/City-Record-Online/dg92-zbpx/data" target="_blank">open data</a>. We’ve integrated it into WeGov datasets and reorganized its contents to make it easier to understand. Please <a href="https://wegov.nyc/contact/" target="_blank">let us know</a> if you have ideas for how we can improve this resource.</p>
				</div>
				<div class="col-md-1 mt-2" id="org_summary">
					{{--<table class="table-sm stats-table" width="100%">
					<thead>
						<tr>
						<th scope="col" width="50%" class="text-center px-0" data-content="See the project info published on specific dates.">Publication Date&nbsp;<small><i class="bi bi-question-circle-fill ml-1" style="top:-1px;position:relative;"></i></small></th>
						<th scope="col" width="50%" id="pub_date_filter"></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan=2 class="text-right px-0 pt-0 pb-3">
								<button class="type-label my-2 dropdown-toggle" data-toggle="collapse" data-target="#stats_collapse" aria-expanded="true" aria-controls="stats_collapse"><small>Show/Hide Stats</small></button>
							</td>
						</tr>
					</tbody>
					</table>--}}
				</div>
			</div>


			<div id="stats_collapse" class="collapse show mt-2 mb-4">
				<div class="row justify-content-center my-2">
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'publichearings']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center">Public Hearings</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="publichearings1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="publichearings7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="publichearings30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'procurement']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center">Procurement</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="procurement1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="procurement7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="procurement30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'contractawards']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center">Contract Award</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="contractawards1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="contractawards7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="contractawards30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'agencyrules']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center">Agency Rules</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="agencyrules1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="agencyrules7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="agencyrules30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				
				</div>
					
				<div class="row justify-content-center mt-3 mb-4">
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'propertydisposition']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center px-0" style="letter-spacing: -1.5px;">Property Disposition</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="propertydisposition1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="propertydisposition7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="propertydisposition30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'courtnotices']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center">Court Notices</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="courtnotices1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="courtnotices7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="courtnotices30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'changeofpersonnel']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center px-0" style="letter-spacing:-1.25px;font-size:1.85rem;">Changes in Personnel</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="changeofpersonnel1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="changeofpersonnel7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="changeofpersonnel30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'specialmaterials']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h2 class="prj_stat text-center ml-3">Special Materials</h2>
									<p class="text-center ml-3 my-1"><u>New:</u></p>
									<div class="row">
										<div class="col-3 pr-0">Today:&nbsp;<b><span id="specialmaterials1">&nbsp;</span></b></div>
										<div class="col pr-0">7 Days:&nbsp;<b><span id="specialmaterials7">&nbsp;</span></b></div>
										<div class="col-5 pr-0">30 Days:&nbsp;<b><span id="specialmaterials30">&nbsp;</span></b></div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				</div>
				
			</div>
					

			<div class="row justify-content-center py-4">
				<div class="col-md-6 organization_data">
					<h4 class="mb-3">News&nbsp;<a title="Copy News RSS feed link" onclick="copyLinkM(this, 'noticesRSSNews');"><i class="bi bi-rss share_icon_container" data-toggle="popover" data-content="News RSS feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer; top:-3px;"></i></a></h4>
					<textarea id="noticesRSSNews" class="details">{!! route('noticesRSSNews') !!}</textarea>
					
					@foreach (array_slice($news, 0, 6) as $n)
					  <div class="card mb-1">
					    <a href="https://a856-cityrecord.nyc.gov/RequestDetail/{{ $n['RequestID'] }}" class="hoveronly" target="_blank">
						  <div class="card-body py-2">
							<h5 class="card-title mb-0">{{ $n['TypeOfNoticeDescription'] }} <small>{{ $n['StartDate'] }}</small></h5>
							<p class="card-text mb-0">{{ $n['ShortTitle'] }}</p>
							@if ($n['wegov-org-name'])
							  <span onclick="function () { window.location='/organization/{{ $n["wegov-org-id"] }}/notices/all' }" class="badge badge-primary" >{{ $n['wegov-org-name'] }}</span>
							@endif
						  </div>
					    </a>
					  </div>
					@endforeach
					<div class="row justify-content-center">
						<div class="col-md-12 text-center">
							<a type="button" class="type-label my-4" href="{{ route('noticesSection', ['section' => 'all']) }}">See All News</a>
						</div>
					</div>
					
				</div>
				
				<div class="col-md-6 organization_data">
					<h4 class="mb-3">Events&nbsp;<a title="Copy Events iCal feed link" onclick="copyLinkM(this, 'noticesIcalEvents');"><i class="bi bi-calendar-event share_icon_container" data-toggle="popover" data-content="Events iCal feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer; top:-3px;"></i></a></h4>
					<textarea id="noticesIcalEvents" class="details">{!! route('noticesIcalEvents') !!}</textarea>
					<iframe src="https://calendar.google.com/calendar/embed?height=600&wkst=1&bgcolor=%23ffffff&ctz=America%2FNew_York&title=CROL%20Event%20Notices%20via%20WeGovNYC&showTitle=0&mode=AGENDA&showCalendars=0&src=am1kNmNyYWlkOWd0aWllMzZwb2dlb2JqZDVxaGdoMjFAaW1wb3J0LmNhbGVuZGFyLmdvb2dsZS5jb20&color=%23D50000" style="border-width:0" width="100%" height="600" frameborder="0" scrolling="no"></iframe>
					<div class="row justify-content-center">
						<div class="col-md-12 text-center">
							<a type="button" class="type-label my-4" href="{{ route('noticesSection', ['section' => 'events']) }}">See All Events</a>
						</div>
					</div>
				</div>
			</div>
			
				
			<h4 class="mb-2">Auctions</h4>
			<p>Get great deals and help the city raise funds by bidding on items New York City agencies have put up for sale.</p>
			<div class="row justify-content-center py-1">
				@foreach (array_slice($auctions, 0, 3) as $a)
				  <div class="col-md-4 organization_data">
					  @php
						$img = json_decode(str_replace('""', '"', $a['Featured Image']), true);
					  @endphp
					<div class="card">
						<a href="{!! $a['URL'] !!}" target="_blank" class="hoveronly">
							@if ($img[0]['thumbnails']['large']['url'])
								<div style="height: 250px; overflow: hidden; display: block; padding: 0; margin: 20px auto 0; text-align: center;">
									<img src="{{ $img[0]['thumbnails']['large']['url'] }}" alt="{{ $a['Title'] }}" style="max-width: 100%; max-height: 100%; margin: 0 auto; width: inherit;">
								</div>
							@endif
						  <div class="card-body">
							<h6 class="card-title mb-0">{{ $a['Title'] }}</h6>
							<p class="card-text mb-0">Time Left: {{ $a['Time Left'] }}<br/>Current Price: {{ $a['Current Price'] }}</p>
						  </div>
						</a>
					</div>
				  </div>
				@endforeach
			</div>
			<div class="row justify-content-center">
				<div class="col-md-12 text-center">
					<p>* Bid is updated daily so they current price we display may no longer be accurate.</p>
					<a type="button" class="type-label my-4" href="{{ route('auctions') }}">See All Auctions</a>
				</div>
			</div>
			
				
		{{--	
			<div class="row justify-content-center">
				<div class="col-md-12 organization_data">
					<h4>Upcoming Events &nbsp;<a title="Copy Agencies Notices iCal feed link" onclick="copyLinkM(this);"><i class="bi bi-calendar-event share_icon_container" data-toggle="popover" data-content="Agencies Notices iCal feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer; top:-3px;"></i></a></h4>
					<p>Find the time and location of events such as public meetings and hearings about contracts, reports and more.</p>
					<textarea id="details-permalink" class="details">{!! route('noticesIcalEvents') !!}</textarea>
				</div>
			</div>
				
			<div class="row justify-content-center map_right">
				@if ($map ?? null)
					<div id="map_container" class="col-6" style="display:none;">
					<button id="map_button_alt" class="btn btn-outline map_btn" style="margin:0 20px 20px 10px; z-index: 10; max-width: 40px; float:right;" onclick="toggleMap();"><img src="/img/map_location.png" alt=""></button>
						<!-- toggles -->
						<div class="select_district" id="toggles" style="left:0px;">
							<img src="/img/eyes.png" alt="">
							<ul class="inner_district">
								<li class="dropdown">
									<a class="dropdown-toggle" id="toggle_boundries" role="button" aria-haspopup="true" aria-expanded="true">Show District Boundaries</a>
									<div class="dropdown-menu" style="width:100%;padding:0px 0px 0px 10px;">
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="cd-switch">
											<label class="custom-control-label" for="cd-switch">Community Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="ed-switch">
											<label class="custom-control-label" for="ed-switch">Election Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="pp-switch">
											<label class="custom-control-label" for="pp-switch">Police Precincts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="dsny-switch">
											<label class="custom-control-label" for="dsny-switch">Sanitation Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="fb-switch">
											<label class="custom-control-label" for="fb-switch">Fire Battilion<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="sd-switch">
											<label class="custom-control-label" for="sd-switch">School Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="hc-switch">
											<label class="custom-control-label" for="hc-switch">Health Center Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="cc-switch">
											<label class="custom-control-label" for="cc-switch">City Council Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="nycongress-switch">
											<label class="custom-control-label" for="nycongress-switch">Congressional Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="sa-switch">
											<label class="custom-control-label" for="sa-switch">State Assembly Dist...<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="ss-switch">
											<label class="custom-control-label" for="ss-switch">State Senate Districts<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="bid-switch">
											<label class="custom-control-label" for="bid-switch">Business Improvem...<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="nta-switch">
											<label class="custom-control-label" for="nta-switch">Neighborhood Tab...<hr class="border-sample"></label>
										</div>
										<div class="custom-control custom-switch">
											<input type="checkbox" class="custom-control-input" id="zipcode-switch">
											<label class="custom-control-label" for="zipcode-switch">Zip Code<hr class="border-sample"></label>
										</div>
									</div>
								</li>
							</ul>
						</div>
						<!-- /toggles -->
						<div id="map" class="map flex-fill d-flex" style="width:100%;height:100%;border:4px solid #112F4E; position:relative; min-height:800px;"></div>
						<div id="help_us" class="" style="width:100%;min-height:260px;border:1px solid #112F4E; margin-top:24px; padding: 32px;">
							<h4>Help us locate projects</h4>
							<p>NYC’s government doesn’t publish the locations of capital projects (!?), so volunteers are using the information they do publish to determine where the projects are actually located.</p>
							<p><a href="https://www.notion.so/wegovnyc/Volunteer-d751814ef6374dd9b9d10c989bcfa141" class="learn_more" target="_blank">Join Us</a></p>
						</div>
					</div>
				@endif
				
				<div id="data_container" class="col float-left">
					<div class="table-responsive">
						<div class="filter_icon">
							<i class="bi bi-funnel-fill"></i>
						</div>
						<table id="myTable" class="display table-striped table-hover" style="width:100%;">
							<thead>
								<tr>
									@if ($details['detFlag'])
										<th></th>
									@endif
									@foreach ($details['hdrs'] as $name)
										<th>{{ $name }}</th>
									@endforeach
									<th></th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
			<div class="row justify-content-center">
				<div class="col-md-12 text-center">
					<a type="button" class="type-label my-4" href="{{ route('noticesSection', ['section' => 'events']) }}">See All Events</a>
				</div>
			</div>
		--}}
				
		</div>

		<div class="col-md-12">
			<div class="bottom_lastupdate">
				<p class="lead"><img src="/img/info.png" alt=""> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
			</div>
		</div>
	</div>
	
	<script>
		function changeToggle (e) {
			console.log($(e.target).next("label")[0].innerHTML)
			$('#change_district').html($(e.target).next("label")[0].innerHTML);
		}
		$('#toggle_boundries').click( function (e) {
			$(this).next('.dropdown-menu').toggleClass('show');
		})

		$(".filter_icon").click(function() {
			console.log($('.toolbar').is(':visible'))
			if(!$('.toolbar').is(':visible')) {
				$('.filter_icon').addClass('position_change');
			}else {
				$('.filter_icon').removeClass('position_change');
			}
			$(".toolbar").toggle();
		});
	</script>

@endsection
