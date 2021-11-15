<div class="inner_container">
    <nav class="navbar navbar-expand-lg navbar-light" id="top-menu">
        <a class="navbar-brand" href="https://wegov.nyc">
            <img src="/img/we_gov_logo_blue 1.png" title="" alt="">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-nav" aria-controls="navbar-nav" aria-expanded="true" aria-label="Toggle navigation">
            <img src="/img/menu_icon.png" alt="" title="" style="height: 20px;">
        </button>
        <div class="collapse navbar-collapse" id="navbar-nav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="https://wegov.nyc/news-events/">News & Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://wegov.nyc/tools/">Tools</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://wegov.nyc/about/">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://www.notion.so/wegovnyc/Get-Involved-d31cee2e3ea04051b600e0a5b902daab">Get Involved</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://opencollective.com/wegovnyc">Donate</a>
                </li>
            </ul>
            <div class="right_menuarea">
                <div class="input-group">
                    <div class="gcse-search"></div>
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
</div>
