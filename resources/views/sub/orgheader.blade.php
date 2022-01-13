<div class="inner_container">
	<div id="org-header" class="org-header">
		<div class="row m-0">
			@if ($org['logo'][0]['url'] ?? null)
			<div class="col-md-2">
				<div class="inside_orglogo">
					<img class="org-logo" src="{{ $org['logo'][0]['url'] }}" />
				</div>
			</div>
			@endif
			<div class="col-md-10 org_detailheader">
				<div class="row">
					<div class="col-md-8">
						<div class="org_detailtitle m-0">
							<h4>{{ $org['name'] }}</h4>
							@if ($org['communityDistrictName'])
								<br/><p>Representing <a href="{{ route('districtsPreset', [
													'type' => 'cd',
													'id' => $org['communityDistrictId'][0],
													'section' => 'nyccouncildiscretionaryfunding',
												])
											}}">
									{{ trim($org['communityDistrictName'][0]) }}
								</a></p>
							@endif
							@if ($org['cityCouncilDistrictName'])
								<br/><p>Representing <a href="{{ route('districtsPreset', [
													'type' => 'cc',
													'id' => $org['cityCouncilDistrictId'][0],
													'section' => 'nyccouncildiscretionaryfunding',
												])
											}}">
									{{ trim($org['cityCouncilDistrictName'][0]) }}
								</a></p>
							@endif
							@if ($org['parent_id'])
								@if (preg_match('~Classification|Official~si', $org['parent_type']))
									<br/><p>Reports to <a>
								@else
									<br/><p>Reports to <a href="{{ route('orgProfile', ['id' => $org['parent_id']]) }}">
								@endif
										{{ trim($org['parent_name']) }}
									</a>
									<a href="{{ route('orgsChartFocus', ['id' => $org['id']]) }}" class="">
										<i class="bi-diagram-3-fill" style="font-size: 1.2rem;margin-left: 10px;"></i>
									</a>
								</p>
							@endif
						</div>
						<div class="icon_orgsocial">
							@foreach ($icons as $f=>$pp)
								@if ($f <> 'ical')	
									<div class="icon">
										@if ($org[$f] ?? null)
											<a href="{{ $pp[1] }}{{ $org[$f] }}" target="_blank">
												<i class="bi-{{ $pp[0] }}"></i>
											</a>
										@else
											<i class="bi-{{ $pp[0] }}"></i>
										@endif
									</div>
								@else
									<div class="icon">
										<a onclick="copyLinkM(this);">
											<i class="bi-{{ $pp[0] }} share_icon_container pl-0" data-toggle="popover" data-content="Agency Notices iCal feed link copied to clipboard" placement="left" trigger="manual" style="cursor: pointer;"></i>
										</a>
										<textarea id="details-permalink" class="details">{!! route('orgIcalEvents', ['id' => $id]) !!}</textarea>
									</div>
								@endif
							@endforeach
						</div>
					</div>
					<div class="col-md-4 text-right">
						@if ($org['type'] ?? null)
							<a href="{{ route('orgs') }}?type={{ urlencode($org['type']) }}" class="no-underline" style="display: inline-block;">
								<span class="type-label" style="display: inherit;">{{ $org['type'] }}</span>
							</a>
						@endif
						@if ($org['tags'] ?? null)
							<div style="display: inline-block;text-align: left;">
								<p class="text-types" style="padding-right:0px;float:none;display: inherit;"><a title="Tags"><i class="bi-tags" style="color:black;"></i></a></p>
								@foreach ((array)$org['tags'] as $tag)
									<span class="tag-label" style="padding-left:0px;margin:0px">
										<a href="{{ route('orgs') }}?tag={{ urlencode($tag) }}" class="no-underline"  style="padding-left:0px;margin:0px">
											{{ $tag }}
										</a>
									</span>
								@endforeach
							</div>
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="navbar-expand-lg org_headermenu mt-3">
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#submenu_nav" aria-controls="submenu_nav" aria-expanded="true" aria-label="Toggle navigation">
			<p class="m-0">Organization Menu</p>
		</button>
		@if ($menu ?? null)
			<div class="collapse navbar-collapse" id="submenu_nav">
				<ul class="nav navbar navbar-expand-lg navbar-light submenu_org">
					@foreach ($menu as $h=>$sect)
						@if (is_string($sect))
							@if ($active == $sect)
								<li class="nav-item active">
							@else
								<li class="nav-item">
							@endif
							@if ($sect == 'about')
								<a class="nav-link active" href="{{ route('orgProfile', ['id' => $id]) }}">{{ $slist[$sect] }}</a>
							@else
								<a class="nav-link active" href="{{ route('orgSection', ['id' => $id, 'section' => $sect]) }}">{{ $slist[$sect] }}</a>
							@endif
							</li>
						@else
							@if ($activeDropDown == $h)
								<li class="nav-item dropdown active">
							@else
								<li class="nav-item dropdown">
							@endif
								<a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">{{ $h }}</a>
								<div class="dropdown-menu">
									@foreach ($sect as $subsect)
										@if ($subsect == 'about')
											<a class="dropdown-item" href="{{ route('orgProfile', ['id' => $id]) }}">{{ $slist[$subsect] }}</a>
										@else
											<a class="dropdown-item" href="{{ route('orgSection', ['id' => $id, 'section' => $subsect]) }}">{{ $slist[$subsect] }}</a>
										@endif
									@endforeach
								</div>
							</li>
						@endif
					@endforeach
				</ul>
			</div>
		@endif
	</div>
</div>



