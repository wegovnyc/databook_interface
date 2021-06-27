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
			<div class="col-md-9 organization_data py-0">
				<h6>NYC Capital Project</h6>
				<h2>{{ $data['name'] }}</h2>
			</div>
			<div class="col-md-3 organization_data">
				<select id="pub_date_filter" style="width:60%;" class="filter mt-1" onchange="showPrj();">
					@foreach ($data['items'] as $date=>$row)
						<option value="{{ $date }}" @if($date == array_keys($data['items'])[0]) selected @endif>{{ $row['PUB_DATE_F'] }}</option>
					@endforeach
				</select>
			</div>
		</div>
		<div class="row justify-content-center map_right">
			@if ($map ?? true)
				<div id="map_container" class="col-4">
				  {{--
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
				  --}}
					<div id="map" class="map flex-fill d-flex" style="width:100%;height:100%;border:4px solid #112F4E;"></div>
				</div>
			@endif
			
			<div id="capproject_profile" class="col-8 float-left">
			
				<table width="100%" class="mb-5">
					<thead>
						<tr>
							<th scope="col">Summary</th>
							<th scope="col">Original</th>
							<th scope="col">Current</th>
							<th scope="col">Difference</th>
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
				
				<h4>Timeline</h4>
				<table width="100%" class="mb-4" id="project_timeline">
					<thead>
						<tr>
							<th scope="col">Phase</th>
							<th scope="col">Original</th>
							<th scope="col">Current</th>
							<th scope="col">Difference</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
				
			</div>
		</div>
	</div>

	{{--
	@if ($dataset['Public Note'] ?? null)
        <div class="col-md-12">
            <h4 class="note_bottom">{{ nl2br($dataset['Public Note']) }}</h4>
		</div>
	@endif
	--}}
    <div class="col-md-12">
        <div class="bottom_lastupdate">
            <p class="lead"><img src="/img/info.png" alt="" title=""> This data comes from <a href="{{ $dataset['Citation URL'] }}" target="_blank">{{ $dataset['Name'] }}</a><span class="float-right" style="font-weight: 300;"><i>Last updated {{ explode(' ', $dataset['Last Updated'])[0] }}</i></span></p>
            <!--<p>{!! nl2br($org['description']) !!}</p>-->
        </div>
	</div>

	{{--
		<pre>
		@php
			print_r($data);
		@endphp
		</pre>
	--}}

	<script>
		/*		// map basics
		function changeToggle (e) {
			console.log($(e.target).next("label")[0].innerHTML)
			$('#change_district').html($(e.target).next("label")[0].innerHTML);
		}
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
		*/
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
			showPrj();
		})
	</script>

@endsection
