@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	@include('sub.titleheader', ['active' => $section])

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

			$('#filter-1').find('[value*="20190619"]').prop('selected',true).trigger('change');

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
	</script>
	<div class="inner_container">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-md-12 organization_data">
					<p>{!! nl2br($details['description'] ?? $dataset['Descripton']) !!}</p>
					@if(array_search($section, $menu) === false)
						<h4>{{ $dataset['Name'] }}</h4>
					@endif
					@if ($map ?? null)
						<button id="map_button" class="btn map_btn" style="float:right;" onclick="toggleMap();"><img src="/img/map_location.png"></button>
					@endif
				</div>
			</div>
			<div class="row justify-content-center map_right">
				@if ($map ?? null)
					<div id="map_container" class="col-6" style="display:none;">
						<!-- controls -->
						<div id="map-controls">
							<div class="select_district">
								<img src="/img/map_icon.png">
								<ul class="inner_district">
									<li class="dropdown">
										<a id="change_district" class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="true">Select a District Type</a>
										<div class="dropdown-menu" style="width:100%;padding:0px 0px 0px 0px;">
											@foreach ($map as $code=>$col)
												<div class="custom-control custom-switch dropdown-item pl-3">
													<input type="radio" class="custom-control-input" id="{{ $code }}-filter-switch" name="filter" param="{{ $col }}" onchange="changeToggle(event)">
													<label class="custom-control-label radio_toggle" for="{{ $code }}-filter-switch">
														{{ ['cd'=>'Community Districts', 'cc'=>'City Council Districts', 'nta'=>'Neighborhood Tabulation Areas'][$code] }}
													</label>
												</div>
											@endforeach
										</div>
									</li>
								</ul>
							</div>
						</div>
						<!-- /controls -->

						<!-- toggles -->
						<div class="select_district" id="toggles">
							<img src="/img/eyes.png">
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
						<div id="map" class="map flex-fill d-flex" style="width:100%;height:100%;border:4px solid #112F4E;"></div>
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
