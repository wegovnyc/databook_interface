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
			<div class="org_detailtitle">
				<h4>{{ $org['name'] }}</h4>
				@if ($org['communityDistrictName'])
					<p>Representing <a href="{{ route('districtsPreset', [
										'type' => 'cd',
										'id' => $org['communityDistrictId'][0],
										'section' => 'nyccouncildiscretionaryfunding',
									])
								}}">
						{{ trim($org['communityDistrictName'][0]) }}
					</a></p>
				@endif
				@if ($org['cityCouncilDistrictName'])
					<p>Representing <a href="{{ route('districtsPreset', [
										'type' => 'cc',
										'id' => $org['cityCouncilDistrictId'][0],
										'section' => 'nyccouncildiscretionaryfunding',
									])
								}}">
						{{ trim($org['cityCouncilDistrictName'][0]) }}
					</a></p>
				@endif
			</div>
            <div class="icon_orgsocial">
                @foreach ($icons as $f=>$pp)
                    <div class="icon">
                        @if ($org[$f] ?? null)
                            <a href="{{ $pp[1] }}{{ $org[$f] }}" target="_blank">
                                <i class="bi-{{ $pp[0] }}"></i>
                            </a>
                        @else
                            <i class="bi-{{ $pp[0] }}"></i>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="float-right mt-1">
                @if ($org['type'] ?? null)
                    <div class="float-left mr-4">
                        {{-- <p class="text-types">Type:</p>
						<p class="text-types" style="line-height:inherit;padding-right:inherit;"><a title="Type"><i class="bi-funnel" style="color:black;"></i></p> --}}
                        <a href="{{ route('orgs') }}?type={{ urlencode($org['type']) }}" class="float-left no-underline">
                            <span class="type-label">{{ $org['type'] }}</span>
                        </a>
                    </div>
                @endif
                @if ($org['tags'] ?? null)
                    <div class="float-left">
                        {{-- <p class="text-types">Tags:</p> --}}
						<p class="text-types" style="line-height:inherit;padding-right:inherit;"><a title="Tags"><i class="bi-tags" style="color:black;"></i></a></p>
						<!--<a title="Tags" style="display:block;"><i class="bi-tags" style="color:black;"></i>-->
                        @foreach ((array)$org['tags'] as $tag)
                            {{-- <a href="{{ route('orgs') }}?tag={{ urlencode($tag) }}" class="float-left">
                                <span class="badge badge-info">{{ $tag }}</span>
                            </a> --}}
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

<div class="navbar-expand-lg org_headermenu mt-3">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#submenu_nav" aria-controls="submenu_nav" aria-expanded="true" aria-label="Toggle navigation">
        <p class="m-0">Organization Menu</p>
    </button>
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
</div>



