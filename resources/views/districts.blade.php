@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
<div class="inner_container">
	<div class="container">
		<div class="row justify-content-center">
			<div id="map_container" class="col-12 mb-0" style="min-height:500px!important;">
				<!-- controls -->
				<div id="map-controls">

					<div class="select_district">
						<div class="input-group search_input search_twitter">
							<input id="addrSearch" type="text" class="form-control" placeholder="Enter address to find districts" aria-label="Enter address to find districts" aria-describedby="searchBtn">
							<div class="input-group-append">
								<button class="input-group-text" id="searchBtn" onclick="addrSearch();" data-toggle="popover" data-content="" data-placement="right" data-trigger="manual"><i class="bi bi-search"></i></button>
							</div>
						</div>
					</div>
					
					<div class="select_district">
						<img src="/img/map_icon.png" alt="" title="">
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
					<img src="/img/eyes.png" alt="" title="">
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
				<div id="map" class="map flex-fill d-flex" style="width:100%;height:100%;"></div>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="row justify-content-center">
			<div id="section_content" class="col-12 mb-4 p-0 district_section">
				{{--
				<div class="text-center bottom_text my-3">
					<h3>Click a District to View Data.</h3>
				</div>				
				--}}
			</div>
		</div>
	</div>
</div>
	<script src="https://typeahead.js.org/releases/latest/typeahead.bundle.js"></script>
	<script>
	
		var globfilter = []
		var defSection = '{{ $section }}'
		var defId = '{{ $id }}'

		function getBounds(coords, bounds) {		// recursively walks over multilevel object calculating leaves-points coords
			if (typeof coords[0][0] == 'object')
				return coords.reduce(function (bounds, subcoords) {
						return getBounds(subcoords, bounds)
					}, 
					bounds
				)
			else {
				return coords.reduce(function (bounds, coord) {
							return bounds.extend(coord);
						}, (typeof bounds == 'undefined') ? new mapboxgl.LngLatBounds(coords[0], coords[0]) : bounds
					);
			}
		}
		
		function mapAction(filter, type, sect) {
			$('.loading').show()
			if (sect == 'inherit')
				if ($('li.nav-item.active .dsmenu').length) {
					sect = $('li.nav-item.active .dsmenu').attr('id').replace('dsmenu-', '')
				} else {
					sect = '{{ array_keys($slist)[0] }}'
				}
			globfilter = filter
			
			//console.log(filter, type, sect)
			
			$.get(`/districtXHR/${type}/${filter[2]}/${sect}`, function (html) {
				$('#section_content').html(html)

				window.setTimeout(function (){
					var features = map.querySourceFeatures(type, {
						filter: filter
					});
					console.log(filter, features, features.length)
					if (features.length) {
						var title = features[0].properties['nameCol']
						var center = getBounds(features[0].geometry.coordinates).getCenter()
						//console.log(title, center)
						tt = {'cc': 'City Council District ', 'cd': 'Community District ', 'nta': ''}
						$('#section_content h1').html(tt[type]+title)
						$('.loading').hide()
						window.setTimeout(function (){
							map.flyTo({
								center: center,
								speed: 0.4
							});
							}, 1000
						)
					} else {					
						$('.loading').hide()
					}
				}, 1000);
			})
		}


		function addrSearchPopover(msg) {
			$('#searchBtn').attr('data-content', msg)
			$('#searchBtn').popover('show')
			setTimeout(function(){
				$('#searchBtn').popover('hide')
			}, 3000);
		}


		function addrSearch() {
			var addr = $('#addrSearch').val()
			if (!addr || (addr.length < 6)) {
				addrSearchPopover('Please enter valid address')
				return
			}
			
			$.ajax({
				url: 'https://api.nyc.gov/geo/geoclient/v1/search.json',
				data: {input: addr},
				headers: {'Ocp-Apim-Subscription-Key': '{{ config('apis.geoclient_key') }}'},
				success: function (dd) {
					if (dd.status != 'OK') {
						addrSearchPopover('Not found, please try again')
						return
					}
					r = dd.results[0].response
					var addr = `${r.houseNumber} ${r.firstStreetNameNormalized}, ${r.uspsPreferredCityName}, USA`.replace('  ', ' ').replace(' ,', '')
					/*<h4 style="font-size:18px;">${dd.input.toUpperCase()}</h4>*/
					var description = `
						<h4 style="font-size:18px;">${addr}</h4>
						<table><tbody>
							<tr><th scope="row">Community District</th>
								<td>
									<a href="/districts/cd/${r.communityDistrict}/nyccouncildiscretionaryfunding">${r.communityDistrict}</a>
									<a id="cd-agency" style="display:none;" target="_blank"><i class="bi bi-link-45deg"></i></a>
									<a id="cd-url" style="display:none;" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
								</td>
							</tr>
							<tr><th scope="row">City Council District</th>
								<td>
									<a href="/districts/cc/${r.cityCouncilDistrict.replace(/^0+/g, '')}/nyccouncildiscretionaryfunding">${r.cityCouncilDistrict}</a> 
									<a id="cc-agency" style="display:none;" target="_blank"><i class="bi bi-link-45deg"></i></a>
									<a id="cc-url" style="display:none;" target="_blank"><i class="bi bi-box-arrow-up-right"></i></a>
								</td>
							</tr>
							<tr><th scope="row">Neighborhood (NTA)</th><td><a href="/districts/nta/${r.nta}/nyccouncildiscretionaryfunding">${r.ntaName}</a></td></tr>
							<tr><th scope="row">Zip Code</th><td>${r.zipCode}</td></tr>
							<tr><th scope="row">Election District</th><td>${r.electionDistrict}</td></tr>
							<tr><th scope="row">State Assembly District</th><td>${r.assemblyDistrict}</td></tr>
							<tr><th scope="row">State Senate District</th><td>${r.stateSenatorialDistrict}</td></tr>
							<tr><th scope="row">Congressional District</th><td>${r.congressionalDistrict}</td></tr>
							<tr><th scope="row">Police Precinct</th><td>${r.policePrecinct}</td></tr>
							<tr><th scope="row">Sanitation District</th><td>${r.sanitationDistrict}</td></tr>
							<tr><th scope="row">Fire Battilion</th><td>${r.fireBattalion}</td></tr>
							<tr><th scope="row">School District</th><td>${r.communitySchoolDistrict}</td></tr>
							<tr><th scope="row">Health Center District</th><td>${r.healthCenterDistrict}</td></tr>
						</tbody></table>`
						
					map.fitBounds([
						[r.longitude - 0.002,r.latitude - 0.0005], // southwestern corner of the bounds
						[r.longitude + 0.002,r.latitude + 0.0035] // northeastern corner of the bounds
					])
					
					if (popup)
						popup.remove()

					popup = new mapboxgl.Popup()
						.setLngLat([r.longitude,r.latitude])
						.setHTML(description)
						.addTo(map)
						
					$.get('{!! $cdAgencyUrl !!}'.replace('%40%40%40', r.communityDistrict.replace(/^0+/g, '')), function (cd) {
						$('#cd-agency').attr('href', '/agency/' + cd['rows'][0]['id'])
						$('#cd-agency').show()
						if (cd['rows'][0]['url']) {
							$('#cd-url').attr('href', cd['rows'][0]['url'])
							$('#cd-url').show()
						}
					})
					$.get('{!! $ccAgencyUrl !!}'.replace('%40%40%40', r.cityCouncilDistrict.replace(/^0+/g, '')), function (cc) {
						$('#cc-agency').attr('href', '/agency/' + cc['rows'][0]['id'])
						//$('#cc-agency').show()
						if (cc['rows'][0]['url']) {
							$('#cc-url').attr('href', cc['rows'][0]['url'])
							$('#cc-url').show()
						}
					})
				}
			});
		}

		$(document).ready(function() {
			orgSectionMapInit({!! json_encode($map) !!}, {!! $type ? "'{$type}'" : "''" !!});
			
			map.on('load', function() {
				$.get('{!! $prjUrl !!}', function (jj) {
					var features = []
					jj.rows.forEach(function (j) {
						try {
							geo_json = JSON.parse(j['GEO_JSON'].replaceAll('""', '"'))
							geo_json.properties['AG_ID'] = j['wegov-org-id']
							features.push(geo_json)
						} catch (error) {
							console.error(error);
						}
					})
					projectsMapDrawFeatures(features, false);
				});
			})
		})

		function changeToggle (e) {
			//console.log($(e.target).next("label")[0].innerHTML)
			$('#change_district').html($(e.target).next("label")[0].innerHTML);
			//console.log($(e.target))
			
			var type = $(e.target).attr('id').replace('-filter-switch', '')
			//var id = defId ? defId : {!! json_encode(['cc' => '1', 'cd' => '101', 'nta' => 'BK09']) !!}[type]
			var id = defId
			defId = null
			
			var section = defSection ? defSection : 'inherit'
			defSection = null

			if (id) {
				//console.log(type, id, section)
				var tmpfilter = ['in', filtFields[type], id]
				mapAction(tmpfilter, type, section);
				map.setFilter(type+'FH', tmpfilter);
			}
		}

		$('#toggle_boundries').click( function (e) {
			$(this).next('.dropdown-menu').toggleClass('show');
		})


		var autocomplete = new Bloodhound({
		  datumTokenizer: Bloodhound.tokenizers.whitespace,
		  queryTokenizer: Bloodhound.tokenizers.whitespace,
		  //prefetch: './resources/namesearchAutocomplete.json'
		  remote: {
			url: 'https://geosearch.planninglabs.nyc/v1/autocomplete?text=%QUERY',
			wildcard: '%QUERY',
			transform: function (resp) {
			  var rr = []
			  resp.features.forEach(function (f) {
				  rr.push(f['properties']['label'].replace('NY, ', ''))
			  })
			  return rr
			}
		  }
		});
		$('#addrSearch').typeahead(null, {
		  name: 'autocomplete',
		  limit: 16,
		  source: autocomplete
		});
		
		autocomplete.clearPrefetchCache();
		autocomplete.initialize(true);


	</script>
	
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>


@endsection