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
				<div class="col-md-{{ $w }}" id="org_news">
					<div class="notice_org">
						<h5 class="card-title mb-4">
							Notices&nbsp;<a title="Copy Agency News RSS feed link" onclick="copyLinkM(this, 'orgRSSNews');"><i class="bi bi-rss share_icon_container" data-toggle="popover" data-content="Agency News RSS feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer; top:-2px;"></i></a>
						</h5>
						@if ($news)
							<div class="card-text">
								@foreach($news as $n)
								  <div class="card mb-1">
									<a href="https://a856-cityrecord.nyc.gov/RequestDetail/{{ $n['RequestID'] }}" class="hoveronly" target="_blank">
									  <div class="card-body py-2">
										<h5 class="card-title mb-0">{{ $n['TypeOfNoticeDescription'] }} <small>{{ $n['StartDate'] }}</small></h5>
										<p class="card-text mb-0">{{ $n['ShortTitle'] }}</p>
										@if ($n['wegov-org-name'])
										  <span class="badge badge-primary" >{{ $n['SectionName'] }}</span>
										@endif
									  </div>
									</a>
								  </div>
								
								@endforeach
							</div>
							<div class="text-center col-md-12">
								<a class="outline_btn" href="{{ route('orgNoticeSection', ['id' => $id, 'subsection' => 'all']) }}">See More News</a>
							</div>
						@endif
					</div>
				</div>

				<div class="col-md-{{ $w }}" id="org_events">
					<div class="notice_org">
						<h5 class="card-title mb-4">
							Events&nbsp;<a title="Copy Agency Events iCal feed link" onclick="copyLinkM(this);"><i class="bi bi-calendar-event share_icon_container" data-toggle="popover" data-content="Agency Events iCal feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer; top:-2px;"></i></a>
						</h5>
						@if ($events)
							<div class="card-text">
								<div id="calendar">				
								</div>
								<img id="loading-calendar" src="/ical/images/ajax-loader.gif"/>				
							</div>
							<div class="text-center col-md-12">
								<a class="outline_btn" href="{{ route('orgNoticeSection', ['id' => $id, 'subsection' => 'events']) }}">See More Events</a>
							</div>
						@else
							<div class="text-center col-md-12">
								No upcoming events
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
									#org_socials .card-text  {overflow: auto;height: 545px; margin-bottom: 10px;}
									#org_socials .card-text iframe  {overflow: auto;height: 535px !important;border: 1px solid #e1e0e0 !important;}
								</style>
							@else
								<style>
									#org_socials .card-text  {overflow: auto;height: 600px;}
									#org_socials .card-text iframe  {overflow: auto;height: 590px !important;border: 1px solid #e1e0e0 !important;}
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
							@endif
							</div>
							
						</div>
					</div>
				@endif
			</div>
			
			<div class="row mb-4">
				<div id="data_container_accordion" class="col-12 accordion">
				
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
	
	<!-- calendar -->
	
	<!-- Moment -->
	<script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.12.0/moment.min.js"></script>
	<script src="/ical/js/moment-timezone-with-data.min.js"></script>
	<!-- Fullcalendar -->
	<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.8.2/fullcalendar.js"></script>-->
	<script src="/ical/js/fullcalendar.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.8.2/locale-all.js"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.8.2/fullcalendar.min.css" rel="stylesheet" type="text/css">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.8.2/fullcalendar.print.min.css" rel="stylesheet" type="text/css" media="print">
	<!-- qtip2 -->
	<script src="//cdn.jsdelivr.net/qtip2/3.0.3/jquery.qtip.min.js"></script>
	<link href="//cdn.jsdelivr.net/qtip2/3.0.3/jquery.qtip.min.css" rel="stylesheet" type="text/css">
	<!-- PNotify & Animate-->
	<script src="/ical/js/pnotify.min.js"></script>
	<link href="/ical/css/pnotify.min.css" rel="stylesheet" type="text/css">
	<link href="/ical/css/animate.css" rel="stylesheet" type="text/css">
	<!-- Mozilla-comm/ical -->
	<script src="/ical/js/ical.js"></script>
	<!-- icalendar2fullcalendar -->
	<script src="/ical/js/ical_events.js"></script>
	<script src="/ical/js/ical_fullcalendar.js"></script>
	<!-- app  -->
	<script src="/ical/js/app.js"></script>
	<link href="/ical/css/app.css" rel="stylesheet" type="text/css">
	<link rel="canonical" href="https://getbootstrap.com/docs/4.6/components/modal/">
	<!-- /calendar -->
	
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
			for (let k in uu) {
				$.get(uu[k].replace('fyear', year), function (resp) {
					var v = resp['rows'][0]['sum'] ?? '-'
					currency = k == 'headcount' ? '' : '$'
					v = v != '-' ? currency + intWithCommas(v) : v
					$('#summary_'+k).text(v)
				})
			}
		}
		
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
				}
			});
			
			set_calendar("{!! route('orgIcalEvents', ['id' => $id]) !!}");

		})
	</script>
@endsection
