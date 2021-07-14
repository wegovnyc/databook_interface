@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')

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
				},

				@if ($details['filters'])
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
				var d = data.replace(/<span class="bad">/g, '-');
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
					if ((sel == '#budget_totals') && (v != '-'))
						$(sel).text(toFin(v))
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
			<div class="col-md-8 organization_data">
				<h4>{{ $details['name'] }}</h4>
				<p>{!! nl2br($details['description']) !!}</p>
			</div>
			<div class="col-md-4 my-3" id="org_summary">
				<div class="card organization_summary">
					<div class="card-body">
						<div class="card-text">
							<table class="table-sm stats-table" width="100%">
							  <thead>
								<tr>
								  <th scope="col" width="50%">Summary</th>
								  <th scope="col" width="50%" id="pub_date_filter"></th>
								</tr>
							  </thead>
							  <tbody>
								  <tr>
									  <td scope="row">Capital Budget</td>
									  <td id="budget_totals" class="pl-3"></td>
								  </tr>
								  <tr>
									  <td scope="row">Active Projects</td>
									  <td id="prj_count" class="pl-3"></td>
								  </tr>
							  </tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			{{--
			<div class="col-md-3 organization_data" >
				<button id="map_button" class="btn map_btn" style="margin:24px 16px 0 0; float:right; z-index: 100;" onclick="toggleMap();"><img src="/img/map_location.png" alt="" title=""></button>
			</div>
			--}}
			
		</div>
		<div class="row justify-content-center map_right">
			@if ($map ?? null)
				<div id="map_container" class="col-6" style="display:none;">
					<button id="map_button_alt" class="btn btn-outline map_btn" style="margin:0 20px 20px 10px; z-index: 10; max-width: 40px; float:right;" onclick="toggleMap();"><img src="/img/map_location.png" alt="" title=""></button>
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

    <div class="col-md-12">
        <div class="bottom_lastupdate">
            <p class="lead"><img src="/img/info.png" alt="" title=""> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
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
