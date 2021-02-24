<div id="org-header" class="org-header">
	<div style="display: flex;justify-content: space-between;">
		<div>
			@if ($org['Logo'][0]['url'] ?? null)
				<div class="d-inline">
					<img class="org-logo" src="{{ $org['Logo'][0]['url'] }}" />
				</div>
			@endif
			<div class="d-inline">
			  <h4 class="d-inline">{{ $org['name'] }}</h4>
			  <div class="d-inline">
				  @foreach ($icons as $f=>$pp)
					<div class="d-inline px-2 icon">
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
			</div>
		</div>
		<div style="margin: auto; margin-right: 20px; top: -2px; position:relative; text-align: right;">
		  @if ($org['Type'] ?? null)
			<div class="d-inline px-1">
			  Type:
			</div>
			<div class="d-inline px-1">
			  <a href="{{ route('orgs') }}?type={{ urlencode($org['Type']) }}">
			    <span class="badge badge-info">{{ $org['Type'] }}</span>
			  </a>
			</div>
		  @endif
		  @if ($org['tags'] ?? null)
			<div class="d-inline px-1">
			  Tags:
			</div>
			@foreach ((array)$org['tags'] as $tag)
			  <a href="{{ route('orgs') }}?tag={{ urlencode($tag) }}">
			    <div class="d-inline px-1">
				  <span class="badge badge-info">{{ $tag }}</span>
			    </div>
			  </a>
			@endforeach
		  @endif
		</div>
	</div>
</div>


<ul class="nav navbar navbar-expand-lg navbar-light org-header">
	@foreach ($slist as $sect=>$name)
		@if ($active == $sect)
			<li class="nav-item active">
		@else
			<li class="nav-item">
		@endif
		@if ($sect == 'about')
			<a class="nav-link active" href="{{ route('orgProfile', ['id' => $id]) }}">{{ $name }}</a>
		@else
			<a class="nav-link active" href="{{ route('orgSection', ['id' => $id, 'section' => $sect]) }}">{{ $name }}</a>
		@endif
	  </li>
	@endforeach
</ul>