<div class="inner_container">
	<div id="pos-header" class="org-header">
		<div class="row m-0">
			<div class="col-md-12 org_detailheader org_detailtitle m-0">
				<h4>{{ $titles[0]['Title Description'] }}</h4>
			</div>
		</div>
		
		<div class="row mx-0 my-1">
			<div class="col-3">
				<small class="text-muted">Title Code</small><br />
				<h6>{{ $titles[0]['Title Code'] }}</h6>
			</div>
			<div class="col-9">
			  @if($titles[0]['wegov-org-name'])
				<small class="text-muted">Bargaining Unit</small><br />
				<h6><a href="/organization/{{ $titles[0]['wegov-org-id'] }}">{{ $titles[0]['wegov-org-name'] }}</a></h6>
			  @endif
			</div>
		</div>
		
		<div class="row mx-0 my-1">
			<div class="col-12 px-0">
				<table class="table table-sm">
				  <thead>
					<tr>
					  <th scope="col">Minimum Salary Rate</th>
					  <th scope="col">Maximum Salary Rate</th>
					  <th scope="col">Standard Hours</th>
					  <th scope="col">Assignment Level</th>
					</tr>
				  </thead>
				  <tbody>
				    @foreach ($titles as $title)
						<tr>
						  <td>${{ number_format($title['Minimum Salary Rate']) }}</td>
						  <td>${{ number_format($title['Maximum Salary Rate']) }}</td>
						  <td>{{ $title['Standard Hours'] }}</td>
						  <td>{{ $title['Assignment Level'] }}</td>
						</tr>					
				    @endforeach
				  </tbody>
				</table>
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



