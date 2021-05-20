@extends('layout')

@section('menubar')
	@include('sub.menubar')
@endsection

@section('content')
	<div class="container">
		<div class="row justify-content-center">
			<div id="map_container" class="col-12 mb-0">
				<!-- controls -->
				<div id="map-controls">
					<h3>Area filters</h3>
					@foreach ($map as $code=>$col)
						<div class="custom-control custom-switch">
						  <input type="radio" class="custom-control-input" id="{{ $code }}-filter-switch" name="filter" param="{{ $col }}">
						  <label class="custom-control-label" for="{{ $code }}-filter-switch">
							{{ ['cd'=>'Community Districts', 'cc'=>'City Council Districts', 'nta'=>'Neighborhood Tabulation Areas'][$code] }}
						  </label>
						</div>
					@endforeach
				</div>
				<!-- /controls -->
				<!-- toggles -->
				<div id="toggles">
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
				<!-- /toggles -->
				<div id="map" class="map flex-fill d-flex" style="width:100%;height:100%;border:1px black dotted;"></div>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="row justify-content-center">
			<div id="section_content" class="col-12 mb-4 p-0">
			</div>
		</div>
	</div>
	
	<script>
	
		var globfilter = []

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
					sect = {{ array_keys($slist)[0] }}
				}
			globfilter = filter
			
			console.log(filter, type, sect)
			
			$.get(`/districtXHR/${type}/${filter[2]}/${sect}`, function (html) {
				$('#section_content').html(html)
				
				var features = map.querySourceFeatures(type, {
					filter: filter
				});
				console.log(features, features.length)
				if (features.length) {
					var title = features[0].properties['nameCol']
					var center = getBounds(features[0].geometry.coordinates).getCenter()
					console.log(title, center)
					$('#section_content h1').html(type== 'cc' ? 'CCD '+title : title)
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
			})
		}

		$(document).ready(function() {
			mapInit({!! json_encode($map) !!}, {!! $type ? "'{$type}'" : null !!});

			map.on('load', function() {
				window.setTimeout(function (){
					//	enable filter by def
					//if ($('#map-controls div:nth-child(2) input:checked').length)
						//$('#map-controls div:nth-child(2) input').click();
					var tmpfilter = ['in', filtFields['{{ $type }}'], '{{ $id }}']
					console.log(tmpfilter)
					mapAction(tmpfilter, '{{ $type }}', '{{ $section }}');
					map.setFilter('{{ $type }}FH', tmpfilter);
					}, 3000
				)
			})
		})
	</script>
	
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/buttons.colVis.min.js"></script>
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.6.5/css/buttons.dataTables.min.css"/>


@endsection