@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	<div class="container">
		<div class="row justify-content-center">
			<div id="map_container" class="col-12 mb-0" style="min-height:500px!important;">
				<!-- controls -->
				<div id="map-controls">
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
				{{-- <div >
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
				</div> --}}
				<!-- /toggles -->
				<div id="map" class="map flex-fill d-flex" style="width:100%;height:100%;"></div>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="row justify-content-center">
			<div id="section_content" class="col-12 mb-4 p-0 district_section">
				<div class="text-center bottom_text my-3">
					<h3>Click a District to View Data.</h3>
				</div>				
			</div>
		</div>
	</div>
	
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
					//console.log(filter, features, features.length)
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

		$(document).ready(function() {
			orgSectionMapInit({!! json_encode($map) !!}, {!! $type ? "'{$type}'" : null !!});
			
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
	</script>
	
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>


@endsection