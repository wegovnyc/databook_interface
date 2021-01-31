<nav class="navbar navbar-expand-lg navbar-light" id="top-menu">
  <a class="navbar-brand" href="{{ route('root') }}">
    <img src="/img/we-gov-logo.png" width="150" alt="">
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-nav" aria-controls="navbar-nav" aria-expanded="true" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbar-nav">
    <ul class="navbar-nav">
	@if ($active=='orgs')
      <li class="nav-item active">
    @else
      <li class="nav-item">
    @endif
        <a class="nav-link" href="{{ route('orgs') }}">Organizations</a>
      </li>
	@if ($active=='about')
      <li class="nav-item active">
    @else
      <li class="nav-item">
    @endif
        <a class="nav-link" href="{{ route('about') }}">About</a>
      </li>
    </ul>
	
	<div class="input-group ml-2" style="max-width:16em;">
	  <div class="input-group-prepend">
		<span class="input-group-text" id="basic-addon1"><i class="bi bi-search"></i></span>
	  </div>
	  <input type="text" class="form-control" placeholder="Search" aria-describedby="basic-addon1">
	</div>
	  
  </div>
</nav>

<nav aria-label="breadcrumb">
  <ol class="breadcrumb">
	@foreach ($breadcrumbs as $n=>$br)
		@if (!$br[0])
			<li class="breadcrumb-item active" aria-current="page">{{ $br[1] }}</li>
		@else
			<li class="breadcrumb-item"><a href="{!! $br[0] !!}">{{ $br[1] }}</a></li>
		@endif
	@endforeach
  </ol>
</nav>