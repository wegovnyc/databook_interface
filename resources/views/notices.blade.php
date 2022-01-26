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

						@foreach ($details['filters'] as $i=>$v)
							@if ($v)
								setTimeout(function(){
									$('#filter-{{ $i }}').find('[value*="{!! $v !!}"]').prop('selected',true).trigger('change')
								}, 500 + 1000 * {{ $i }});
							@endif
						@endforeach
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
				return d;
			};
		});

		function loadStat() {
			var uu = {!! json_encode($statUrls) !!}
			for (let sel in uu) {
				$.get(uu[sel], function (resp) {
					var v = resp['rows'][0]['res'] ?? '-'
					$(sel).text(v.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","))
				})
			}
		}
	</script>

	<div class="inner_container">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-md-11 organization_data">
					<h4>The City Record Remix</h4>
					<p>New York City’s “<a href="https://en.wikipedia.org/wiki/Government_gazette" target="_blank">official journal</a>” is called “The City Record.” It’s published in print, and online as a <a href="https://www1.nyc.gov/site/dcas/about/city-record.page" target="_blank">PDF</a>, as a <a href="https://a856-cityrecord.nyc.gov/" target="_blank">website</a> and as <a href="https://data.cityofnewyork.us/City-Government/City-Record-Online/dg92-zbpx/data" target="_blank">open data</a>. We’ve used the open data version, which is updated daily, to integrate The City Record’s contents into the WeGov data system. We also created RSS news and ICS event feeds from the data, and created new ways to search and browse this information. Please <a href="https://wegov.nyc/contact/" target="_blank">let us know</a> if you have ideas for how we can improve this resource.</p>
				</div>
				<div class="col-md-1 mt-2" id="org_summary">
				</div>
			</div>


			<div id="stats_collapse" class="collapse show mt-2 mb-4">
				<div class="row justify-content-center my-2">
					<div class="col-md-3">
						<div class="card">
						  <a href="{{ route('noticesSection', ['section' => 'publichearings']) }}" class="hoveronly">
							<div class="card-body">
								<div class="card-text">
									<h5 class="prj_stat">Public Hearings</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="publichearings1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="publichearings7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="publichearings30">&nbsp;</span></p>
											</div>	
										</div>
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
									<h5 class="prj_stat">Procurement</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="procurement1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="procurement7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="procurement30">&nbsp;</span></p>
											</div>	
										</div>
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
									<h5 class="prj_stat">Contract Award</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="contractawards1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="contractawards7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="contractawards30">&nbsp;</span></p>
											</div>	
										</div>
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
									<h5 class="prj_stat">Agency Rules</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="agencyrules1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="agencyrules7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="agencyrules30">&nbsp;</span></p>
											</div>	
										</div>
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
									<h5 class="prj_stat">Property Disposition</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="propertydisposition1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="propertydisposition7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="propertydisposition30">&nbsp;</span></p>
											</div>	
										</div>
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
									<h5 class="prj_stat">Court Notices</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="courtnotices1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="courtnotices7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="courtnotices30">&nbsp;</span></p>
											</div>	
										</div>
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
									<h5 class="prj_stat">Changes in Personnel</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="changeofpersonnel1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="changeofpersonnel7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="changeofpersonnel30">&nbsp;</span></p>
											</div>	
										</div>
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
									<h5 class="prj_stat">Special Materials</h5>
									{{--<p style="color:#000; font-weight:bold; margin-bottom:5px">New:</p>--}}
									<div class="row">
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>Today</h4>
												<p><span id="specialmaterials1">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>7 Days</h4>
												<p><span id="specialmaterials7">&nbsp;</span></p>
											</div>	
										</div>
										<div class="col-md-4 responsive_card p-1">
											<div class="inner_card text-center">
												<h4>30 Days</h4>
												<p><span id="specialmaterials30">&nbsp;</span></p>
											</div>	
										</div>
									</div>
								</div>
							</div>
						  </a>	
						</div>
					</div>
				</div>
				
			</div>
					

			<div class="row justify-content-center">
				<div class="col-md-6 organization_data">
					<h4 class="mb-3  p-0">News&nbsp;<a title="Copy News RSS feed link" onclick="copyLinkM(this, 'noticesRSSNews');"><i class="bi bi-rss share_icon_container" data-toggle="popover" data-content="News RSS feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer; top:-3px;"></i></a></h4>
					<textarea id="noticesRSSNews" class="details">{!! route('noticesRSSNews') !!}</textarea>
					
					@foreach (array_slice($news, 0, 6) as $n)
					  <div class="card mb-1">
					    <a href="https://a856-cityrecord.nyc.gov/RequestDetail/{{ $n['RequestID'] }}" class="hoveronly" target="_blank">
						  <div class="card-body py-2">
							<h5 class="card-title mb-0">{{ $n['TypeOfNoticeDescription'] }} <small>{{ $n['StartDate'] }}</small></h5>
							<p class="card-text mb-0">{{ $n['ShortTitle'] }}</p>
							@if ($n['wegov-org-name'])
							  <span class="badge badge-primary" >{{ $n['wegov-org-name'] }}</span>
							@endif
						  </div>
					    </a>
					  </div>
					@endforeach
					<div class="row justify-content-center">
						<div class="col-md-12 text-center">
							<a type="button" class="outline_btn" href="{{ route('noticesSection', ['section' => 'all']) }}">See All News</a>
						</div>
					</div>
					
				</div>
				
				<div class="col-md-6 organization_data">
					<h4 class="mb-3  p-0">Events&nbsp;<a title="Copy Events iCal feed link" onclick="copyLinkM(this, 'noticesIcalEvents');"><i class="bi bi-calendar-event share_icon_container" data-toggle="popover" data-content="Events iCal feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer; top:-3px;"></i></a></h4>
					<textarea id="noticesIcalEvents" class="details">{!! route('noticesIcalEvents') !!}</textarea>
					<iframe src="https://calendar.google.com/calendar/embed?height=600&wkst=1&bgcolor=%23ffffff&ctz=America%2FNew_York&title=CROL%20Event%20Notices%20via%20WeGovNYC&showTitle=0&mode=AGENDA&showCalendars=0&src=am1kNmNyYWlkOWd0aWllMzZwb2dlb2JqZDVxaGdoMjFAaW1wb3J0LmNhbGVuZGFyLmdvb2dsZS5jb20&color=%23D50000" style="border-width:0" width="100%" height="580" frameborder="0" scrolling="no"></iframe>
					<div class="row justify-content-center">
						<div class="col-md-12 text-center">
							<a type="button" class="outline_btn" href="{{ route('noticesSection', ['section' => 'events']) }}">See All Events</a>
						</div>
					</div>
				</div>
			</div>
			
				
			<div class="row justify-content-center py-1">
				<div class="col-md-12 organization_data">
					<h4 class="mb-2 p-0">Auctions</h4>
					<p class="p-0">Get great deals and help the city raise funds by bidding on items New York City agencies put up for sale.</p>
				</div>
				@foreach (array_slice($auctions, 0, 3) as $a)
				  <div class="col-md-4 organization_data">
					  @php
						$img = json_decode(str_replace('""', '"', $a['Featured Image']), true);
					  @endphp
					<div class="card">
						<a href="{!! $a['URL'] !!}" target="_blank" class="hoveronly">
							@if ($img[0]['thumbnails']['large']['url'] ?? $img[0]['url'] ?? null)
								<div style="height: 250px; overflow: hidden; display: block; margin: 20px; text-align: center;    background: #f0f0f0;">
									<img src="{{ $img[0]['thumbnails']['large']['url'] ?? $img[0]['url'] }}" alt="{{ $a['Title'] }}" style="max-width: 100%; max-height: 100%;width:auto; margin: 0 auto;">
								</div>
							@endif
							<div class="card-body pt-0 text-center">
								<h6 class="card-title mb-2" style="color:#000;font-weight:bold">{{ $a['Title'] }}</h6>
								<p class="card-text mb-0" style="color:#000;">Time Left: {{ $a['Time Left'] }}<br/>Current Price: {{ $a['Current Price'] }}</p>
							</div>
						</a>
					</div>
				  </div>
				@endforeach
			</div>
			<div class="row justify-content-center">
				<div class="col-md-12 text-center">
					<p>* Bid is updated daily so the current price we display may no longer be accurate.</p>
					<a type="button" class="outline_btn mb-3" href="{{ route('auctions') }}">See All Auctions</a>
				</div>
			</div>
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
		
		$('.clickable').click(function(e) {
			var url = $(this).attr('onclick_url');
			console.log(e, url)
			window.location.href = url;
			e.stopPropagation();
		})
		
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
