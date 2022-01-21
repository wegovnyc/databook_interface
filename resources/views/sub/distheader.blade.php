<div class="inner_container">
	<div class="navbar-expand-lg org_headermenu mt-3">
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#submenu_nav" aria-controls="submenu_nav" aria-expanded="true" aria-label="Toggle navigation">
			<p class="m-0">District Menu</p>
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
							<a class="nav-link dsmenu active" id="dsmenu-{{ $sect }}" onclick="mapAction(globfilter, '{{ $type }}', '{{ $sect }}');" style="cursor:pointer;">{{ $slist[$sect] }}</a>
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
									<a class="dropdown-item" href="{{ route('orgSection', ['id' => $id, 'section' => $subsect]) }}">{{ $slist[$subsect] }}</a>
								@endforeach
							</div>
						</li>
					@endif
				@endforeach
				
				<li class="share_icon_container" data-toggle="popover" data-content="Link copied to clipboard" placement="left" trigger="manual">
					<textarea id="details-permalink" class="details">{!! route('districtsPreset', compact(['type', 'id', 'section'])) !!}</textarea>
					<span id="details-addr"></span> 
					<a title="Share direct link" onclick="copyLink();">
						<i class="bi bi-share"></i>
					</a>
				</li>
			</ul>
		</div>
	</div>
</div>