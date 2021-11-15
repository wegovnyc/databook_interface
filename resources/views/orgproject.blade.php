@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	@include('sub.orgheader', ['active' => $section])
	<div class="inner_container">
		<div class="container">
			<div class="row justify-content-center">
				<div class="col-md-12 organization_data">
					<p>{!! $dataset['Descripton'] !!}</p>
					@if(array_search($section, $menu) === false)
						<h4>{{ $dataset['Name'] }}</h4>
					@endif
				</div>
			</div>
			<div class="row justify-content-center">
				<div class="col-md-8 organization_data py-0">
					<h2>{{ $data['name'] }}</h2>
				</div>
				
				<div class="col-md-2 col-sm-4 organization_data justify-content-center">
					<h5 class="mt-2" data-content="See the project info published on specific dates.">Publication Date&nbsp;<small><i class="bi bi-question-circle-fill ml-1 pb-1" style="top:-1px;position:relative;"></i></small></h5>
				</div>
				<div class="col-md-2 col-sm-4 organization_data">
					<select id="pub_date_filter" style="width:100%;" class="filter" onchange="showPrj();">
						@foreach ($data['items'] as $date=>$row)
							<option value="{{ $date }}" @if($date == array_keys($data['items'])[0]) selected @endif>{{ $row['PUB_DATE_F'] }}</option>
						@endforeach
					</select>
				</div>
			</div>
			<div class="row justify-content-center">

				<div id="capproject_profile" class="col-md-8 col-sm-12">
					<div class="table-responsive">
						<table width="100%" class="mb-5">
							<thead>
								<tr>
									<th scope="col">Summary</th>
									<th scope="col">Original</th>
									<th scope="col">Current</th>
									<th scope="col">Change (#)</th>
								</tr>
							</thead>
							<tbody>
								<tr id="budget">
									<th scope="row">Budget</th>
									<td class="original"></td>
									<td class="current"></td>
									<td class="difference"></td>
								</tr>
								<tr id="start">
									<th scope="row">Start</th>
									<td class="original"></td>
									<td class="current"></td>
									<td class="difference"></td>
								</tr>
								<tr id="end">
									<th scope="row">End</th>
									<td class="original"></td>
									<td class="current"></td>
									<td class="difference"></td>
								</tr>
								<tr id="duration">
									<th scope="row">Duration</th>
									<td class="original"></td>
									<td class="current"></td>
									<td class="difference"></td>
								</tr>
							</tbody>
						</table>
						<table width="100%" class="mb-5" id="project_details">
							<tbody>
								<tr>
									<th scope="row">Project ID</th>
									<td id="PROJECT_ID"></td>
								</tr>
								<tr>
									<th scope="row">Borough</th>
									<td id="BORO"></td>
								</tr>
								<tr>
									<th scope="row">Managed By</th>
									<td id="MANAGING_AGCY"></td>
								</tr>
								<tr>
									<th scope="row">10-year Plan Category</th>
									<td id="TYP_CATEGORY_NAME"></td>
								</tr>
								<tr>
									<th scope="row">Budget Lines</th>
									<td id="BUDGET_LINE"></td>
								</tr>
								<tr>
									<th scope="row">Community Districts Served</th>
									<td id="COMMUNITY_BOARD"></td>
								</tr>
								<tr>
									<th scope="row">Explanation for Delay</th>
									<td id="DELAY_DESC"></td>
								</tr>
								<tr>
									<th scope="row">Scope Summary</th>
									<td id="SCOPE_TEXT"></td>
								</tr>
								<tr>
									<th scope="row">Site Description</th>
									<td id="SITE_DESCR"></td>
								</tr>

							</tbody>
						</table>
					</div>


					<h4>Timeline</h4>
					<div class="table-responsive">
						<table width="100%" class="mb-4" id="project_timeline">
							<thead>
								<tr>
									<th scope="col">Phase</th>
									<th scope="col">Original</th>
									<th scope="col">Current</th>
									<th scope="col">Change</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</div>

					<div class="my-3">
						<div id="disqus_thread"></div>
						<script>
							/**
							*  RECOMMENDED CONFIGURATION VARIABLES: EDIT AND UNCOMMENT THE SECTION BELOW TO INSERT DYNAMIC VALUES FROM YOUR PLATFORM OR CMS.
							*  LEARN WHY DEFINING THESE VARIABLES IS IMPORTANT: https://disqus.com/admin/universalcode/#configuration-variables    */
							/*
							var disqus_config = function () {
							this.page.url = PAGE_URL;  // Replace PAGE_URL with your page's canonical URL variable
							this.page.identifier = PAGE_IDENTIFIER; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
							};
							*/
							(function() { // DON'T EDIT BELOW THIS LINE
							var d = document, s = d.createElement('script');
							s.src = 'https://databook-wegov-nyc.disqus.com/embed.js';
							s.setAttribute('data-timestamp', +new Date());
							(d.head || d.body).appendChild(s);
							})();
						</script>
						<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>				
					</div>
				</div>

				<div class="col-md-4 col-sm-12 p-0">
					<div id="map_container" style="float:none;">
						<!-- toggles -->
						<div class="select_district" id="toggles" style="top:5px; left:0px; display:none;">
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
						<div id="map" class="map flex-fill d-flex" style="width:100%;height:100%;border:2px solid #112F4E;"></div>
					</div>
					<p class="suggest_button mt-4"><a href="https://airtable.com/shrWWa3rNJFGSFObd?prefill_project_id={{ $prjId }}" class="learn_more" target="_blank">Suggest a Change</a></p>
					@if ($data['cLog'])
						<div class="my-3">
							<h4>Change Log</h4>
							<ul style="list-style-type: none; padding-inline-start: 20px;">
								@foreach ($data['cLog'] as $d=>$ll)
									<li>
										<b>{{ implode('/', [substr($d, 4, 2), substr($d, 6, 2), substr($d, 0, 4)]) }}</b>
										<ul>
										@foreach ($ll as $l)
											<li data-content="{{ $l[1] }}">
											{!! $l[0] !!}
											</li>
										@endforeach
									</ul></li>
								@endforeach
							</ul>
						</div>
					@endif
				</div>

			</div>
		</div>

		<div class="col-md-12">
			<div class="bottom_lastupdate">
				<p class="lead"><img src="/img/info.png" alt=""> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
				<!--<p>{!! nl2br($org['description']) !!}</p>-->
			</div>
		</div>
	</div>
	<script>

		function showPrj() {
			var data={!! json_encode($data['items']) !!}
			var pub_date = $('#pub_date_filter option:selected').val()
			var dd = data[pub_date]
			for (k in dd) {
				if (dd.hasOwnProperty(k) && (k[0] == '#'))
					$(k).html(dd[k])
			}
			var timeline = $('#project_timeline tbody')
			timeline.html('')
			dd['milestones'].forEach(function (m) {
				timeline.append(`<tr><th scope="row">${m['TASK_DESCRIPTION']}</th><td class="original">${m['ORIG_DATE_F']}</td><td class="current">${m['CURR_DATE_F']}</td><td class="difference">${m['DATE_DIFF']}</td></tr>`)
			})
			initPopovers();
		}

		$(document).ready(function () {
			//$('h5[data-toggle="tooltip"]').tooltip()
			
			showPrj();

			@if ($data['geo_feature'])
				var feature = {!! $data['geo_feature'] !!}
				//console.log(feature)

				mapboxgl.accessToken = 'pk.eyJ1Ijoic291bmRwcmVzcyIsImEiOiJjazY1OTF3cXIwbjZyM3BtcGt3Y3F2NjZwIn0.3hmCJsl0_oBUpoVsNJKZjQ';

				map = new mapboxgl.Map({
					container: 'map',
					style: 'mapbox://styles/mapbox/light-v10',
					center: [-73.99255747855759,40.58992167435116],
					zoom: 10
				});
				map.addControl(new mapboxgl.NavigationControl());

				map.on('load', function () {
					map.addSource('route', {
							"type": "geojson",
							"data": {
								"type": "FeatureCollection",
								"features": [feature]
								//"features": [ {"type":"Feature","geometry":{"type":"Point","coordinates":[-73.934832,40.68313]}}]
							}
						});

					map.addLayer({
						'id': 'streets',
						'type': 'line',
						'source': 'route',
						'layout': {
							'line-join': 'round',
							'line-cap': 'round'
						},
						'paint': {
							//'line-color': '#ff7c7c',
							'line-color': '#53777a',
							'line-width': 8
						},
						'filter': ['==', '$type', 'LineString']
					});

					map.addLayer({
						'id': 'markers',
						'type': 'circle',
						'source': 'route',
						'paint': {
							'circle-radius': 8,
							//'circle-color': '#ff7c7c'
							'circle-color': '#53777a'
						},
						'filter': ['==', '$type', 'Point']
					});
					
					for (const [code, clr] of Object.entries(zones)) {
						setBoundary(code, clr, clr);
					}
					$('#toggles').show();

					var bounds = [[feature.properties.W, feature.properties.S], [feature.properties.E, feature.properties.N]];
					map.fitBounds(bounds);
				});
			@else
				$('#map').attr('class', 'no-geo');
				$('#map').html('<iframe class="airtable-embed" src="https://airtable.com/embed/shreZusmuYwJNl76Q?prefill_project_id={{ $prjId }}&backgroundColor=blue" frameborder="0" onmousewheel="" width="100%" height="100%" style="background: transparent;"></iframe>');
				$('.suggest_button').remove();
			@endif
			
			$('#toggle_boundries').click( function (e) {
				$(this).next('.dropdown-menu').toggleClass('show');
			})
			
		})
	</script>
<style>
	#map_container #map {height: 800px !important;}
</style>
@endsection


