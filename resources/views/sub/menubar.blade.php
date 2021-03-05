<nav class="navbar navbar-expand-lg navbar-light" id="top-menu">
    <a class="navbar-brand" href="{{ route('root') }}">
        <img src="/img/we_gov_logo_blue 1.png" title="" alt="">
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-nav" aria-controls="navbar-nav" aria-expanded="true" aria-label="Toggle navigation">
        <img src="/img/menu_icon.png" alt="" title="" style="height: 20px;">
    </button>
    <div class="collapse navbar-collapse" id="navbar-nav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <a class="nav-link" href="#">News</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Events</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Resources</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">Tools</a>
            </li>
            {{-- @if ($active=='orgs')
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
            </li> --}}
        </ul>
        <div class="right_menuarea">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#">Add About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Subscribe</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Write For Us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Contact</a>
                </li>
            </ul>
            <div class="input-group">
                <!--<div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1"><i class="bi bi-search"></i></span>
                </div>
                -->
                <div class="gcse-search"></div>
                <!--<input type="text" class="form-control" placeholder="Search" aria-describedby="basic-addon1" id="search-box">-->
            </div>
        </div>
    </div>
</nav>

<div>
  <ol class="breadcrumb">
	@foreach ($breadcrumbs as $n=>$br)
		@if (!$br[0])
			<li class="breadcrumb-item active" aria-current="page">{{ $br[1] }}</li>
		@else
			<li class="breadcrumb-item"><a href="{!! $br[0] !!}">{{ $br[1] }}</a></li>
		@endif
	@endforeach
  </ol>
</div>
