@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	@include('sub.orgheader', ['active' => $section])

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
			
			<div class="col-md-2 col-sm-4 organization_data justify-content-center pl-5">
				<h5 class="mt-2" data-toggle="tooltip" data-placement="bottom" title="See the project info published on specific dates.">Publication Date<h5>
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
                                <th scope="col">Change</th>
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
										<li>
										{{ $l }}
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
            <p class="lead"><img src="/img/info.png" alt="" title=""> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
            <!--<p>{!! nl2br($org['description']) !!}</p>-->
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
		}

		$(document).ready(function () {
			$('h5[data-toggle="tooltip"]').tooltip()
			
			showPrj();

			@if ($data['geo_feature'])
				var feature = {!! $data['geo_feature'] !!}
				console.log(feature)

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
							'line-color': '#ff7c7c',
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
							'circle-color': '#ff7c7c'
						},
						'filter': ['==', '$type', 'Point']
					});

					var bounds = [[feature.properties.W, feature.properties.S], [feature.properties.E, feature.properties.N]];
					map.fitBounds(bounds);
				});
			@else
				$('#map').attr('class', 'no-geo');
				$('#map').html('<iframe class="airtable-embed" src="https://airtable.com/embed/shreZusmuYwJNl76Q?prefill_project_id={{ $prjId }}&backgroundColor=blue" frameborder="0" onmousewheel="" width="100%" height="100%" style="background: transparent;"></iframe>');
				$('.suggest_button').remove();
			@endif
		})
	</script>
<style>
	#map_container #map {height: 800px !important;}
</style>
@endsection


