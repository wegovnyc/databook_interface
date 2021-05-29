@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	@include('sub.orgheader', ['active' => 'about'])

	@php
		$w = $org['Twitter'] || $org['Facebook'] ? 4 : 6;
		$dw = 12 - $w;
	@endphp
	
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
										<option value="{{ date('Y') - 1 }}" selected>{{ date('Y') - 1 }}</option>
										@for($i=date('Y') - 2; $i>=date('Y') - 3; $i--)
											<option value="{{ $i }}">{{ $i }}</option>
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
					<div class="card-text">
						@foreach($crol as $notice)
							<div class="crol_msg mb-4">
								<p><a href="https://a856-cityrecord.nyc.gov/RequestDetail/{{ $notice['RequestID'] }}" target="_blank">{{ $notice['ShortTitle'] }}</a></p>
								<p>{{ $notice['SectionName'] }}</p>
								<p>{{ $notice['StartDate'] }}</p>
							</div>
						@endforeach
					</div>
					<div class="text-center col-md-12">
						<a class="outline_btn" href="{{ route('orgSection', ['id' => $id, 'section' => 'crol']) }}">See More Notices</a>
					</div>
				</div>
			</div>

			@if(($org['Twitter'] ?? null) || ($org['Facebook'] ?? null))
				<div class="col-md-{{ $w }}" id="org_socials">
					<div class="notice_org">
						<h5 class="card-title mb-4">Social Media</h5>
						<!-- <h5 class="card-title mb-4">
							@if($org['Facebook'] ?? null)
								<span id="fb_button" onclick="fb_click();">Facebook</span>
							@endif
							@if(($org['Twitter'] ?? null) && ($org['Facebook'] ?? null))
							-
							@endif
							@if($org['Twitter'] ?? null)
								<span id="tw_button" onclick="tw_click();">Twitter</span>
							@endif
						</h5> -->

						<div class="accordion social_media" id="accordionExample">
						@if($org['Facebook'] ?? null)
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
											.widget-facebook {
												height: 600px;
											}
											.widget-facebook .facebook_iframe {
												border: none;
											}
										</style>
										<script type="text/javascript">
											function setupFBframe(frame) {
												var container = frame.parentNode;

												var facebooklink = "{{ $org['Facebook'] }}";

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
						@if($org['Twitter'] ?? null)
							<div>
								<div id="headingTwo">
									<button class="social_btn collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
										Twitter
									</button>
								</div>
								<div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionExample">
									<div class="card-text" id="tw_content">
										<a class="twitter-timeline" data-height="740" href="{{ $org['Twitter'] }}">&nbsp;</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
									</div>
								</div>
							</div>
							<style>
								.twitter-timeline {height:600px !important}
							</style>
						@endif
						</div>
						
						<!-- @if($org['Twitter'] ?? null)
							<div class="card-text" id="tw_content" style="display:none;">
								<a class="twitter-timeline" data-height="740" href="{{ $org['Twitter'] }}">&nbsp;</a> <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
							</div>
						@endif
						
						@if($org['Facebook'] ?? null)
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
									.widget-facebook {
										height: 740px;
									}
									.widget-facebook .facebook_iframe {
										border: none;
									}
								</style>
								<script type="text/javascript">
									function setupFBframe(frame) {
										var container = frame.parentNode;

										var facebooklink = "{{ $org['Facebook'] }}";

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
						@endif -->
					</div>
				</div>
			@endif

			<div class="col-md-{{ $w }}" id="org_stats">
				<div class="notice_org organization_summary">
					<h5 class="card-title mb-4">Datasets</h5>
					<div class="card-text">
						<table class="table-sm stats-table" style="border:1px solid #000;width:100%">
							<thead>
								<tr>
									<th scope="col">Name</th>
									<th scope="col" style="width:40%"># of Records</th>
								</tr>
							</thead>
							<tbody>
							@foreach($slist as $dsName=>$dsTitle)
								@if($dsName <> 'about')
									<tr>
										<td scope="row"><a href="{{ route('orgSection', ['id' => $id, 'section' => $dsName]) }}">{{ $dsTitle }}</a></td>
										<td id="stats_{{ $dsName }}"></td>
									</tr>
								@endif
							@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
        </div>
	</div>

	<script>
		function loadTableStat(dsName, url) {
			$.get(url, function (resp) {
				console.log(resp)
				//jj = $.parseJSON(resp)
				$('#stats_'+dsName).text(resp['rows'][0]['count'])
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
			
			@foreach(array_keys($slist) as $i=>$dsName)	
				@if($i > 0)
					loadTableStat("{{ $dsName }}", "{!! str_replace('tablename', $allDS[$dsName]['table'], $tableStatUrl) !!}");
				@endif
			@endforeach
			@if($org['Twitter'] ?? null)
				tw_click();
			@else if ($org['Facebook'] ?? null)
				fb_click();
			@endif
			
		})
	</script>
@endsection
