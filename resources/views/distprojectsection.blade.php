<div class="inner_container">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-md-12 mt-5 mb-1">
				<h1></h1>
				<h5 id="linked_agency"></h5>
				@if($member['NAME'] ?? null)
					<h5>Memeber: 
						<span style="color:#999999;">
							<a href="https://council.nyc.gov/district-{{ $id }}/" target="_blank">
								<strong style="font-weight: 500;">{{ $member['NAME'] }}</strong>
							</a>
							, {{ $member['POLITICAL PARTY'] }}, {{ $member['BOROUGH'] }}
						</span>
					</h5>
				@endif
				@if($altName)
					<h5 style="color:#999999;">
						<a href="https://popfactfinder.planning.nyc.gov/explorer/cdtas/{{ $altName }}/" target="_blank">
							<strong style="font-weight: 500;">View District Census Data</strong>
						</a>
					</h5>
				@endif
			</div>
		</div>
	</div>
</div>				

@include('sub.distheader', ['active' => $section])

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
			loadFinStat();
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
						
						setTimeout(function(){
							initPopovers();
						}, 1000);

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
		
			// makes sortable html fields like 9.4 years late, $25,764 over, $64.2M over
			$.fn.dataTable.ext.type.order['html-pre'] = function (data) {
				var d = data.replace(/-/g, '');
				d = d.replace(/<span class="(bad)"[^>]*>/g, '-');
				d = d.replace(/[,$]|years|late|<[^>]+>|earl\S+/g, '');
				d = d.replace(/NA|NaN|on time|^-$/g, '0');
				m = 1
				for (const[rg, tmpM] of [[/K$/g, 1000], [/M$/g, 1000000], [/B$/g, 1000000000]]) {
					if (d.match(rg)) {
						m = tmpM;
						d = d.replace(rg, '');
					}
				}
				d = d.match(/[-\d\.]+/g) ? parseFloat(d) * m : d;
				return d;
			};
		
		});
		
		function loadFinStat() {
			var uu = {!! json_encode($finStatUrls) !!}
			var pubdate = $('#filter-1 option:selected').val() ? $('#filter-1 option:selected').val().replaceAll('-', '') : '20210805';
			for (let sel in uu) {
				$.get(uu[sel].replace('pubdate', pubdate), function (resp) {
					var v = resp['rows'][0]['res'] ?? '-'
					if ((['#orig_cost', '#curr_cost', '#over_budg_am'].includes(sel)) && (v != '-')) {
						$(sel).text(toFinShortK(v, 1000))
						$(sel).attr('data-content', toFin(v, 1000))
					}
					else 
						$(sel).text(v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","))
				})
			}
			setTimeout(function(){
				initPopovers();
			}, 1000);
		}
		
		
</script>

<div class="inner_container">
	<div class="container mb-5">
		<div class="row">
			<div class="col-md-9 organization_data">
				<p class="mb-0">{!! nl2br($details['description'] ?? $dataset['Descripton']) !!}</p>
			</div>
			<div class="col-md-3 mt-2" id="org_summary">
				<table class="table-sm stats-table" width="100%">
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
				</table>
			</div>
		</div>
	
		<div id="stats_collapse" class="collapse show mt-2 mb-4">
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
	
		<div class="row justify-content-center">
			<div id="data_container" class="col">
				<div class="table-responsive">
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
</div>


@if ($dataset['Public Note'] ?? null)
	<div class="col-md-12">
		<h4 class="note_bottom">{{ nl2br($dataset['Public Note']) }}</h4>
	</div>
@endif
<div class="inner_container">
	<div class="col-md-12" style="display:none">
		<div class="bottom_lastupdate">
			<p class="lead"><img src="/img/info.png"> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
		</div>
	</div>
</div>