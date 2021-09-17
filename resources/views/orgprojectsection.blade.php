@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	@include('sub.orgheader', ['active' => $section])

	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>

	<script>
		function details(d) {
			return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">'+
			  @foreach ((array)$details['details'] as $h=>$f)
				(d["{{ $f }}"] ? '<tr><td>{{ $h }}:</td><td>'+d["{{ $f }}"]+'</td></tr>' : '') +
			  @endforeach
			'</table>';
		}

		var datatable = null
		$(document).ready(function() {
			$('th[data-toggle="tooltip"]').tooltip()
			
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
                ],
				createdRow: function(row, data, dataIndex) {
					if (data.GEO_JSON != '') {
						$(row).addClass('have_coords');
					}
				}

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
								select.append('<option value="'+d+'">'+d+'</option>')
							});
						});
						$("div.toolbar .row").append('<button id="map_button" class="btn map_btn col" style="margin:0 20px 0 10px; z-index: 10; max-width: 40px;" onclick="toggleMap();"><img src="/img/map_location.png" alt="" title=""></button>');

						@foreach ($details['filters'] as $i=>$v)
							@if ($v)
								setTimeout(function(){
									$('#filter-{{ $i }}').find('[value*="{!! $v !!}"]').prop('selected',true).trigger('change')
								}, 500 + 1000 * {{ $i }});
							@endif
						@endforeach


						/* custom pub_date filter on top-right */
						this.api().columns([1]).every(function (c,a,i) {
							var delim = {!! json_encode($details['fltDelim']) !!};
							var column = this;
							var select = $('<select class="filter mt-1" style="width:100%;" id="filter-' + column[0][0] + '" name="filter-' + column[0][0] + '" aria-controls="myTable"><option value="" selected>- ' + $(column.header()).text() + ' -</option></select>')
								.appendTo($("#pub_date_filter"))
								.on('change', function () {
									var val = $(this).val()
									column
										.search(val ? val : '', false, false)
										.draw();
									loadFinStat();
								});
							select.wrap('<div class="drop_dowm_select"></div>');
							//$('div.toolbar').insertAfter('#myTable_filter');

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
								select.append('<option value="'+d+'">'+d+'</option>')
							});
						});

						setTimeout(function(){
							$('#filter-1 option:last-child').prop('selected',true).trigger('change')
						}, 500);						
					}
				@endif

				@if ($details['order'])
					,
					order: {!! json_encode($details['order']) !!}
				@endif
			});

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
				}
			});

			$('#myTable_length label').html($('#myTable_length label').html().replace(' entries', ''));

			// if map is displayed updates and draws projects from GEO_JSON field
			datatable.on('draw', function () {
				drawProjects('current');
            });
			
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
				var d = data.replace(/-/g, '');
				d = d.replace(/<span class="(bad)">/g, '-');
				d = d.replace(/[,$]|years|late|<[^>]+>|earl\S+/g, '');
				d = d.replace(/NA|NaN|on time|^-$/g, '0');
				d = d.match(/[-\d\.]+/g) ? parseFloat(d) : d;
				//console.log(data, d);
				return d;
			};
		});


		function toggleMap() {
			var isActive = !$('#map_container').attr('style')
			var cc = [{{ $details['hide_on_map_open'] }}];
			if (isActive) {
				//$('#map_button').attr('class', 'btn map_btn')
				$('#map_button').show()
				$('#data_container').attr('class', 'col')
				$('.toolbar ').show()
				$('#map_container').hide()
				$('#myTable').dataTable().api().columns(cc).every(function () {
					this.visible(true);
				});
			} else {
				//$('#map_button').attr('class', 'btn btn-outline map_btn')
				$('#map_button').hide()
				$('#data_container').attr('class', 'col col-6')
				$('#map_container').show()
				projectsMapInit();
				$('#myTable').dataTable().api().columns(cc).every(function () {
					this.visible(false);
				});
				setTimeout(function() {
						//datatable.draw();	// initiate projectsMapDrawFeatures
						drawProjects('all');
					}, 3000
				);
			}
		}
		
		function loadFinStat() {
			var uu = {!! json_encode($finStatUrls) !!}
			var pubdate = $('#filter-1 option:selected').val().replaceAll('-', '');
			for (let sel in uu) {
				$.get(uu[sel].replace('pubdate', pubdate), function (resp) {
					var v = resp['rows'][0]['res'] ?? '-'
					console.log(['#orig_cost', '#curr_cost', '#over_budg_am'].includes(sel))
					if ((['#orig_cost', '#curr_cost', '#over_budg_am'].includes(sel)) && (v != '-')) {
						$(sel).text(toFinShort(v))
						$(sel).attr('title', toFin(v))
					}
					else 
						$(sel).text(v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","))
				})
			}
		}
		
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
			//api.cells('.record', modifier).data().each(function (r, i) {
			api.rows('', modifier).data().each(function (r, i) {
				if (r['GEO_JSON']) {
					try {
						geo_json = JSON.parse(r['GEO_JSON'].replaceAll('""', '"'))
						geo_json.properties['AG_ID'] = r['wegov-org-id']
						features.push(geo_json)
					} catch (error) {
						console.error(error);
					}
				}
			});
			console.log(features.length)
			projectsMapDrawFeatures(features);
		}
		
	</script>


	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-9 organization_data">
				<p>{!! nl2br($details['description'] ?? $dataset['Descripton']) !!}</p>
				@if(array_search($section, $menu) === false)
					<h4>{{ $dataset['Name'] }}</h4>
				@endif	
			</div>
			<div class="col-md-3 mt-2" id="org_summary">
				<table class="table-sm stats-table" width="100%">
				  <thead>
					<tr>
					  <th scope="col" width="50%" class="text-center px-0" data-toggle="tooltip" data-placement="bottom" title="See the project info published on specific dates.">Publication Date&nbsp;<small><i class="bi bi-question-circle-fill ml-1" style="top:-1px;position:relative;"></i></small></th>
					  <th scope="col" width="50%" id="pub_date_filter"></th>
					</tr>
				  </thead>
				  <tbody>
					  <tr>
						<td colspan=2 class="text-right px-0 pt-0 pb-3">
						{{--<small>Updated data about capital projects is usually published three times a year. You can see how the data between dates changes by selecting different dates.
							</small>
						<br/>--}}
							<button class="type-label my-2 dropdown-toggle" data-toggle="collapse" data-target="#stats_collapse" aria-expanded="false" aria-controls="stats_collapse"><small>Show/Hide Stats</small></button>
						</td>
					  </tr>
				  </tbody>
				</table>
			</div>
		</div>
		
		<div id="stats_collapse" class="collapse mt-2 mb-4">
			<div class="row justify-content-center my-2">
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Number of Projects
								<h2 id="projects_no" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Original Cost
								<h2 id="orig_cost" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Current Cost
								<h2 id="curr_cost" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Amount Over Budget
								<h2 id="over_budg_am" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			
			</div>
				
			<div class="row justify-content-center mt-3 mb-4">
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Running Long
								<h2 id="long_no" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Over Budget
								<h2 id="over_budg_no" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Starting Late
								<h2 id="late_start_no" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			
				<div class="col-md-3">
					<div class="card">
						<div class="card-body">
							<div class="card-text text-center">
								Ending Late
								<h2 id="late_end_no" class="prj_stat">&nbsp;</h2>
							</div>
						</div>
					</div>
				</div>
			</div>
			
		</div>
				
			
		<div class="row justify-content-center map_right">
			@if ($map ?? null)
				<div id="map_container" class="col-6" style="display:none;">
					<button id="map_button_alt" class="btn btn-outline map_btn" style="margin:0 20px 20px 10px; z-index: 10; max-width: 40px; float:right;" onclick="toggleMap();"><img src="/img/map_location.png" alt="" title=""></button>
					<!-- toggles -->
					<div class="select_district" id="toggles" style="left:0px;">
						<img src="/img/eyes.png" alt="" title="">
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
	</div>


	@if ($dataset['Public Note'] ?? null)
        <div class="col-md-12">
            <h4 class="note_bottom">{{ nl2br($dataset['Public Note']) }}</h4>
		</div>
	@endif
    <div class="col-md-12">
        <div class="bottom_lastupdate">
            <p class="lead"><img src="/img/info.png" alt="" title=""> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
            <!--<p>{!! nl2br($org['description']) !!}</p>-->
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
		
		$('#toggle_boundries').click( function (e) {
			$(this).next('.dropdown-menu').toggleClass('show');
		})
	</script>

@endsection
