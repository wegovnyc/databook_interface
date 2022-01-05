@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	<div class="inner_container">
		<div id="pos-header" class="org-header">
			<div class="row mx-2">
				<div class="col-md-9 org_detailheader">
					<h4>{{ $slist[$section] }}</h4>
					<p>{{ $details['description'] }}</p>
				</div>
				<div class="col-md-3 mt-2" id="org_summary">
					<table class="table-sm stats-table" width="100%">
					<thead>
						<tr>
						<th scope="col" width="50%" class="text-center px-0" data-content="See the project info published on specific dates.">Start Date&nbsp;<small><i class="bi bi-question-circle-fill ml-1" style="top:-1px;position:relative;"></i></small></th>
						<th scope="col" width="50%" id="pub_date_filter"></th>
						</tr>
					</thead>
					{{--<tbody>
						<tr>
							<td colspan=2 class="text-right px-0 pt-0 pb-3">
								<button class="type-label my-2 dropdown-toggle" data-toggle="collapse" data-target="#stats_collapse" aria-expanded="true" aria-controls="stats_collapse"><small>Show/Hide Stats</small></button>
							</td>
						</tr>
					</tbody>--}}
					</table>
				</div>
			</div>
		</div>

		<div class="navbar-expand-lg org_headermenu mt-3 mb-5">
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#submenu_nav" aria-controls="submenu_nav" aria-expanded="true" aria-label="Toggle navigation">
				<p class="m-0">Notices Menu</p>
			</button>
			<div class="collapse navbar-collapse" id="submenu_nav">
				<ul class="nav navbar navbar-expand-lg navbar-light submenu_org">
					@foreach ($menu as $h=>$sect)
						@if (is_string($sect))
							@if ($section == $sect)
								<li class="nav-item active">
							@else
								<li class="nav-item">
							@endif
								<a class="nav-link active" href="{{ route('noticesSection', ['section' => $sect]) }}">{{ $slist[$sect] }}</a>
							</li>
						@else
							@if ($activeDropDown == $h)
								<li class="nav-item dropdown active">
							@else
								<li class="nav-item dropdown">
							@endif
								<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">{{ $h }}</a>
								<div class="dropdown-menu">
									@foreach ($sect as $subsect)
										<a class="dropdown-item" href="{{ route('titleSection', ['section' => $subsect]) }}">{{ $slist[$subsect] }}</a>
									@endforeach
								</div>
							</li>
						@endif
					@endforeach
				</ul>
			</div>
		</div>
	</div>



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
		/*
		function toggleMap() {
			var isActive = $('#map_button').attr('class') == 'btn btn-outline map_btn'
			if (isActive) {
				$('#map_button').attr('class', 'btn map_btn')
				$('#data_container').attr('class', 'col')
				$('.toolbar ').css('display', 'inline-block')
				$('#map_container').hide()
			} else {
				$('#data_container').attr('class', 'col col-6')
                const divHeight = $('#data_container').height()
                console.log(divHeight, '3' , $('#map_container').attr('style'));
                $('#map_container').css("min-height", divHeight+'px')
				$('#map_button').attr('class', 'btn btn-outline map_btn')
				$('#map_container').show()
			}
		}

		function mapAction(filter, code, col) {
			if (filter.length == 2)
				datatable.columns([col]).search('').draw()
			else
				datatable.columns([col]).search(filter[2]).draw()
		}
		*/
		var datatable = null
		var dataurl = '{!! $url !!}'
		
		$(document).ready(function() {
			/* custom pub_date filter on top-right */
			$.get("{!! $dates_req_url !!}", function (resp) {
				var select = $('<select class="filter mt-1" style="width:100%;" id="filter-1" name="filter-1" aria-controls="myTable"><option value="" selected>- Publication Date -</option></select>')
					.appendTo($("#pub_date_filter"))
					.on('change', function () {
						var val = $(this).val()
						$('.loading').show()
						datatable.ajax.url(dataurl.replace('pubdate', val)).load(function () {
							$('.loading').hide()
						});
					});
				select.wrap('<div class="drop_dowm_select"></div>');
				resp['rows'].forEach(function (d, j) {
					select.append(`<option value="${d['yy']}" ${ j == 0 ? 'selected' : ''}>${d['yy']}</option>`)
				});


				datatable = $('#myTable').DataTable({
					ajax: {
						url: dataurl.replace('pubdate', resp['rows'][0]['yy']),
						dataSrc: 'rows'
					},
					buttons: [{
						extend: 'colvis',
						className: 'btn_eyeicon',
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
							@if ($details['visible'][$i])
								visible: true
							@else
								visible: false
							@endif
							}
						@endforeach
					],

					@if ($details['filters'])
						initComplete: function () {
							this.api().columns([{{ $details['fltsCols'] }}]).every(function (c,a,i) {
								var delim = {!! json_encode($details['fltDelim']) !!};
								var column = this;
								var select = $('<select class="filter" id="filter-' + column[0][0] + '" name="filter-' + column[0][0] + '" aria-controls="myTable"><option value="" selected>- ' + $(column.header()).text() + ' -</option></select>')
									//.appendTo($(column.footer()).empty())
									.appendTo($("div.toolbar .row"))
									.on('change', function () {
										/*var val = $.fn.dataTable.util.escapeRegex(
											$(this).val()
										);*/
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

							@foreach ($details['filters'] as $i=>$v)
								@if ($v)
									setTimeout(function(){
										$('#filter-{{ $i }}').find('[value*="{!! $v !!}"]').prop('selected',true).trigger('change')
									}, 500 + 1000 * {{ $i }});
								@endif
							@endforeach

							@if ($details['script'] ?? null)
								{!! $details['script'] !!}
							@endif
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
			});
		});
	</script>
	<div class="inner_container">
		<div class="container">
		{{--<div class="row justify-content-center">
				<div class="col-md-12 organization_data">
					<p>{!! nl2br($details['description'] ?? $dataset['Descripton']) !!}</p>
					@if(array_search($section, $menu) === false)
						<h4>{{ $dataset['Name'] }}</h4>
					@endif
					@if ($map ?? null)
						<button id="map_button" class="btn map_btn" style="float:right;" onclick="toggleMap();"><img src="/img/map_location.png"></button>
					@endif
				</div>
			</div>--}}
			<div class="row justify-content-center map_right">
				<div id="data_container" class="col-12">
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
				<p class="lead"><img src="/img/info.png"> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
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
