<div class="inner_container">
	<div id="pos-header" class="org-header">
		<div class="row m-0">
			<div class="col-md-12 org_detailheader org_detailtitle m-0">
				<h4>{{ $title['Title Description'] }}</h4>
			</div>
		</div>
		
		<div class="row mx-0 my-1">
			<div class="col-3">
				<small class="text-muted">Title Code</small><br />
				<h6>{{ $title['Title Code'] }}</h6>
			</div>
			<div class="col-9">
			  @if($title['wegov-org-name'])
				<small class="text-muted">Union</small><br />
				<h6>{{ $title['wegov-org-name'] }}</h6>
			  @endif
			</div>
		</div>
		
		<div class="row mx-0 my-1">
			<div class="col-3">
				<small class="text-muted">Minimum Salary Rate</small><br />
				<h6>{{ $title['Minimum Salary Rate'] }}</h6>
			</div>
			<div class="col-3">
				<small class="text-muted">Maximum Salary Rate</small><br />
				<h6>{{ $title['Maximum Salary Rate'] }}</h6>
			</div>
			<div class="col-3">
				<small class="text-muted">Standard Hours</small><br />
				<h6>{{ $title['Standard Hours'] }}</h6>
			</div>
			<div class="col-3">
				<small class="text-muted">Assignment Level</small><br />
				<h6>{{ $title['Assignment Level'] }}</h6>
			</div>
		</div>

	</div>

	<div class="navbar-expand-lg org_headermenu mt-3">
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#submenu_nav" aria-controls="submenu_nav" aria-expanded="true" aria-label="Toggle navigation">
			<p class="m-0">Position Menu</p>
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
							<a class="nav-link active" href="{{ route('titleSection', ['id' => $id, 'section' => $sect]) }}">{{ $slist[$sect] }}</a>
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
									<a class="dropdown-item" href="{{ route('titleSection', ['id' => $id, 'section' => $subsect]) }}">{{ $slist[$subsect] }}</a>
								@endforeach
							</div>
						</li>
					@endif
				@endforeach
			</ul>
		</div>
	</div>
</div>



