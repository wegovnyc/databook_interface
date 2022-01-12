@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	@include('sub.orgheader', ['active' => 'about'])

	@php
		$w = $org['twitter'] || $org['facebook'] ? 4 : 6;
		$dw = 12 - $w;
	@endphp
	<div class="inner_container">	
		<div class="container py-2">
			<div class="row mb-5">
				<div class="col-md-{{ $dw }}">
					@if ($org['description'] == '')
						<h1 class="display-4">...</h1>
					@else
					{{--<h1 class="display-4">About</h1>--}}
						<p class="lead mt-4">
							{!! nl2br($org['description']) !!}
						</p>
					@endif
					@if ($org['url'])
						<div class="float-left mr-4">
							<a href="{!! $org['url'] !!}" class="float-left no-underline" target="_blank">
								<span class="type-label">Website</span>
							</a>
						</div>					
					@endif
					
				</div>
				<div class="col-md-{{ $w }} mt-3" id="org_summary">
					<div class="card organization_summary">
						<div class="card-body">
							<div class="card-text">
								<table class="table-sm stats-table" width="100%">
								<thead>
									<tr>
									<th scope="col">Summary</th>
									<th scope="col">
										<select style="width:100%;" class="filter" onchange="loadFinStat();" id="fin_stat_select">
											<option value="">Year</option>
											@for($i=date('Y') - 1; $i>=date('Y') - 3; $i--)
												<option value="{{ $i }}" @if($i == $finStatYear) selected @endif>{{ $i }}</option>
											@endfor
										</select>
									</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td scope="row">Headcount</td>
										<td id="summary_headcount" class="pl-3"></td>
									</tr>
									<tr>
										<td scope="row">Actual Spending</td>
										<td id="summary_as" class="pl-3"></td>
									</tr>
									<tr>
										<td scope="row">Additional Cost</td>
										<td id="summary_ac" class="pl-3"></td>
									</tr>
								</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row mb-4">
				<div class="col-md-{{ $w }}" id="org_crol">
					<div class="notice_org">
						<h5 class="card-title mb-4">
							Notices
						</h5>
						@if ($news)
							<div class="card-text">
								@foreach($news as $notice)
									<div class="crol_msg mb-4">
										<p><a href="https://a856-cityrecord.nyc.gov/RequestDetail/{{ $notice['RequestID'] }}" target="_blank">{{ $notice['ShortTitle'] }}</a></p>
										<p>{{ $notice['SectionName'] }}</p>
										<p>{{ $notice['StartDate'] }}</p>
									</div>
								@endforeach
							</div>
							<div class="text-center col-md-12">
								<a class="outline_btn" href="{{ route('orgNoticeSection', ['id' => $id, 'subsection' => 'all']) }}">See More News</a>
							</div>
						@endif
					</div>
				</div>

				<div class="col-md-{{ $w }}" id="org_crol">
					<div class="notice_org">
						<h5 class="card-title mb-4">
							Events
						</h5>
						@if ($events)
							<div class="card-text">
								@foreach($events as $notice)
									<div class="crol_msg mb-4">
										<p><a href="https://a856-cityrecord.nyc.gov/RequestDetail/{{ $notice['RequestID'] }}" target="_blank">{{ $notice['ShortTitle'] }}</a></p>
										<p>{{ $notice['SectionName'] }}</p>
										<p>{{ $notice['StartDate'] }}</p>
									</div>
								@endforeach
							</div>
							<div class="text-center col-md-12">
								<a class="outline_btn" href="{{ route('orgNoticeSection', ['id' => $id, 'subsection' => 'events']) }}">See More Events</a>
							</div>
						@endif
					</div>
				</div>

				@if(($org['twitter'] ?? null) || ($org['facebook'] ?? null))
					<div class="col-md-{{ $w }}" id="org_socials">
						<div class="notice_org">
							<h5 class="card-title mb-4">Social Media</h5>
							@if(($org['twitter'] ?? null) && ($org['facebook'] ?? null))
								<style>
									#org_socials .card-text  {overflow: auto;height: 630px;}
									#org_socials .card-text iframe  {overflow: auto;height: 620px !important;border: 1px solid #e1e0e0 !important;}
								</style>
							@else
								<style>
									#org_socials .card-text  {overflow: auto;height: 700px;}
									#org_socials .card-text iframe  {overflow: auto;height: 690px !important;border: 1px solid #e1e0e0 !important;}
								</style>
							@endif


							<div class="accordion social_media" id="accordionExample">
							@if($org['facebook'] ?? null)
								<div>
									<div id="headingOne">
										<button class="social_btn" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
											Facebook
										</button>
									</div>
									<div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordionExample">
										<div class="card-text" id="fb_content">
											<aside class="widget--facebook--container">
												<div class="widget-facebook">
													<iframe id="facebook_iframe" class="facebook_iframe"></iframe>
												</div>
											</aside>
											<style type="text/css">
												.widget--facebook--container {
													padding: 0px;
												}
												/* .widget-facebook {
													height: 600px;
												} */
												.widget-facebook .facebook_iframe {
													border: none;
												}
											</style>
											<script type="text/javascript">
												function setupFBframe(frame) {
													var container = frame.parentNode;

													var facebooklink = "{{ $org['facebook'] }}";

													var containerWidth = container.offsetWidth;
													var containerHeight = container.offsetHeight;

													var src =
													"https://www.facebook.com/plugins/page.php" +
													"?href="+facebooklink+
													"&tabs=timeline" +
													"&width=" +
													containerWidth +
													"&height=" +
													containerHeight +
													"&small_header=true" +
													"&adapt_container_width=true" +
													"&hide_cover=false" +
													"&hide_cta=true" +
													"&show_facepile=true" +
													"&appId";

													frame.width = containerWidth;
													frame.height = containerHeight;
													frame.src = src;
												}

												/* begin Document Ready                                             
												############################################ */

												document.addEventListener('DOMContentLoaded', function() {
													var facebookIframe = document.querySelector('#facebook_iframe');
													setupFBframe(facebookIframe);
													
													/* begin Window Resize                                            
													############################################ */
													
													// Why resizeThrottler? See more : https://developer.mozilla.org/ru/docs/Web/Events/resize
													(function() {
													window.addEventListener("resize", resizeThrottler, false);

													var resizeTimeout;

													function resizeThrottler() {
														if (!resizeTimeout) {
														resizeTimeout = setTimeout(function() {
															resizeTimeout = null;
															actualResizeHandler();
														}, 66);
														}
													}

													function actualResizeHandler() {
														document.querySelector('#facebook_iframe').removeAttribute('src');
														setupFBframe(facebookIframe);
													}
													})();
													/* end Window Resize
													############################################ */
												});
											</script>
										</div>
									</div>
								</div>
							@endif
							@if($org['twitter'] ?? null)
								<div>
									<div id="headingTwo">
										<button class="social_btn collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
											Twitter
										</button>
									</div>
									<div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
										<div class="card-text" id="tw_content">
											<a class="twitter-timeline" data-height="740" href="{{ $org['twitter'] }}">&nbsp;</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
										</div>
									</div>
								</div>
								<style>
									/* .twitter-timeline {height:600px !important} */
								</style>
							@endif
							</div>
							
						</div>
					</div>
				@endif
			</div>
			
			<div class="row mb-4">
				<div id="data_container_accordion" class="col-12 accordion">
				{{--<h4 class="card-title mb-4 mt-4">
						About the Data
				</h4>--}}
				
					<div class="accordion social_media" id="accordionThree">
						<div>
							<div id="headingThree">
								<button class="social_btn" type="button" data-toggle="collapse" data-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
									This agency’s profile has <span id="total_records"></span> records from <span id="total_datasets"></span> datasets. Click here to learn more.
								</button>
							</div>
							<div id="collapseThree" class="collapse hide" aria-labelledby="headingOne" data-parent="#accordionThree">
								<div class="card-text table-responsive">
									<table id="myTable" class="display table-hover table-borderless" style="width:100%;">
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/rowgroup/1.1.4/js/dataTables.rowGroup.min.js"></script>
	<script>
		var datasets = {!! json_encode($datasets) !!}
		var datatable = null

		function loadTableStat(dsName, url) {
			var datatable = $('#myTable').DataTable();
			$.get(url, function (resp) {
				if (resp['rows'][0]['count']) {
					$('#stats_'+dsName).text(resp['rows'][0]['count'])
					$('#total_records').text(Number($('#total_records').text()) + resp['rows'][0]['count'])
					$('#total_datasets').text(Number($('#total_datasets').text()) + 1)
				} else {
					datasets.forEach(function (d, i) {
						if (d[5].indexOf('stats_'+dsName) != -1) {
							//console.log(i, dsName, d[5].indexOf('stats_'+dsName))
							datasets.splice(i, 1)
							datatable.row(i).remove()
                            datatable.draw();
						}
					})
					
				}
			})
		}
		
		function loadFinStat() {
			var uu = {!! json_encode($finStatUrls) !!}
			var year = $('#fin_stat_select option:selected').val()
			console.log(year)
			for (let k in uu) {
				$.get(uu[k].replace('fyear', year), function (resp) {
					console.log(resp)
					//jj = $.parseJSON(resp)
					var v = resp['rows'][0]['sum'] ?? '-'
					currency = k == 'headcount' ? '' : '$'
					v = v != '-' ? currency + intWithCommas(v) : v
					$('#summary_'+k).text(v)
				})
			}
		}
		
		// function tw_click() {
		// 	console.log('tw_click');
		// 	$('#fb_button').removeClass('active')
		// 	$('#tw_button').addClass('active')
		// 	$('#fb_content').hide()
		// 	$('#tw_content').show()
		// }
		
		// function fb_click() {
		// 	console.log('fb_click');
		// 	$('#tw_button').removeClass('active')
		// 	$('#fb_button').addClass('active')
		// 	$('#tw_content').hide()
		// 	$('#fb_content').show()
		// }
		$(document).ready(function () {
			loadFinStat();
			
			datatable = $('#myTable').DataTable({
				data: datasets,
				paging: false,
				columns: [
					{ title: "Name" },
					{ title: "Label" },
					{ title: "Description" },
					{ 
						title: "Section", 
						visible: false 
					},
					{ title: "Last Updated" },
					{ title: "Agency Records" }
				],
				order: [],
				rowGroup: { dataSrc: 3 },
				dom: 'rtp',
				initComplete: function () {
					@foreach(array_keys($slist) as $i=>$dsName)	
						@if($i > 0)
							loadTableStat(
								"{{ str_replace('/', '_', $dsName) }}", 
								@if ($dsName == 'notices/events')
									"{!! $tableStatUrls['noticesEvents'] !!}"
								@elseif ($allDS[$dsName]['sectionTitle'] ?? null)
									"{!! str_replace('sectionTitle', $allDS[$dsName]['sectionTitle'], $tableStatUrls['notices']) !!}"
								@else
									"{!! str_replace('tablename', $allDS[$dsName]['table'], $tableStatUrls['reg']) !!}"
								@endif
							);
						@endif
					@endforeach
					/*
					@if($org['twitter'] ?? null)
						tw_click();
					@elseif ($org['facebook'] ?? null)
						fb_click();
					@endif
					*/
				}
			});

			/*
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
			*/
		})
	</script>
@endsection
