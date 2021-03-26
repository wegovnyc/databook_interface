<div id="org-header" class="org-header">
    <div class="row m-0">
        @if ($org['Logo'][0]['url'] ?? null)
        <div class="col-md-2">
            <div class="inside_orglogo">
                <img class="org-logo" src="{{ $org['Logo'][0]['url'] }}" />
            </div>
        </div>
        @endif
        <div class="col-md-10 org_detailtitle">
            <h4>{{ $org['name'] }}</h4>
            <div class="icon_orgsocial">
                @foreach ($icons as $f=>$pp)
					@if ($org[$f] ?? null)
						<div class="icon">
                            <a href="{{ $pp[1] }}{{ $org[$f] }}" target="_blank">
                                <i class="bi-{{ $pp[0] }}"></i>
                            </a>
						</div>
					@else
						<div class="icon" style="background:#DEDEDE;">
							<i class="bi-{{ $pp[0] }}"></i>
						</div>
					@endif
                @endforeach
            </div>
            <div class="float-right">
                @if ($org['Type'] ?? null)
                    <div class="float-left mr-4">
                        <!--<p class="text-types">Type:</p>
						<p class="text-types" style="line-height:inherit;padding-right:inherit;"><a title="Type"><i class="bi-funnel" style="color:black;"></i></p>-->
                        <a href="{{ route('orgs') }}?type={{ urlencode($org['Type']) }}" class="float-left no-underline">
                            <span class="badge badge-info">{{ $org['Type'] }}</span>
                            <!--<span class="tag-label">{{ $org['Type'] }}</span>-->
                        </a>
                    </div>
                @endif
                @if ($org['tags'] ?? null)
                    <div class="float-left">
                        <!--<p class="text-types">Tags:</p>-->
						<p class="text-types" style="line-height:inherit;padding-right:inherit;"><a title="Tags"><i class="bi-tags" style="color:black;"></i></a></p>
						<!--<a title="Tags" style="display:block;"><i class="bi-tags" style="color:black;"></i>-->
                        @foreach ((array)$org['tags'] as $tag)
							<!--<span class="badge badge-info">{{ $tag }}</span>-->
							<span class="tag-label">
								<a href="{{ route('orgs') }}?tag={{ urlencode($tag) }}" class="no-underline">
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

<div class="navbar-expand-lg org_headermenu">
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
									<a class="dropdown-item active" href="{{ route('orgProfile', ['id' => $id]) }}">{{ $slist[$subsect] }}</a>
								@else
									<a class="dropdown-item active" href="{{ route('orgSection', ['id' => $id, 'section' => $subsect]) }}">{{ $slist[$subsect] }}</a>
								@endif
							@endforeach
						</div>
					</li>
				@endif
			@endforeach
		</ul>
    </div>
</div>



