<nav class="navbar navbar-expand-lg navbar-light" id="top-menu">
  <a class="navbar-brand" href="{{ route('index') }}">
    <img src="/img/we-gov-logo.png" width="150" alt="">
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="true" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
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
  </div>
</nav>