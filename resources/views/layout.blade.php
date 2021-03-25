<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name', 'WeGov Research') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
	<script type="text/javascript" language="javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
	<script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.23/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="https://cdn.datatables.net/v/dt/jq-3.3.1/dt-1.10.23/r-2.2.7/sp-1.2.2/sl-1.3.1/datatables.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
	<script type="text/javascript" src="/js/script.js"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Styles -->
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jq-3.3.1/dt-1.10.23/r-2.2.7/sp-1.2.2/sl-1.3.1/datatables.min.css"/>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('css/responsive.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
	<script async src="https://cse.google.com/cse.js?cx=2b80c98605cf9ab55"></script>
	
	<style>
		.bi-tags, .bi-funnel {padding-right: .5rem;}
		.tag-label, .tag-label a {color:#777777; font-weight:600; padding-left:.1em;margin-right:-2px;}
		.tag-label:hover {color:#171717;cursor:pointer;text-decoration:none;}
		.no-underline:hover {text-decoration:none;}
		.tag-label+.tag-label::before {
			padding-right: .2rem;
			color: #6c757d;
			content: ", ";
		}
	</style>

</head>

<body>
    <div id="app" class="container">
        <header>
            @yield('menubar')
        </header>

        <main>
            @yield('content')
        </main>

		<footer>
			<div id="newsletter-subs">
                <div class="row">
                    <label for="newsletter-email" class="col-md-8">Stay up date with our community-powered newsletter.</label>
                    <div class="col-md-4 email_address">
                        <small>Your email address</small>
                        <div class="input-group">
                            <input type="text" class="form-control" id="newsletter-email">
                            <div class="input-group-append">
                                <button class="btn btn-info" type="button" onclick="subscribe_newsletter()">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
			</div>
			<div id="return-to-top" style="display:none;">
				<a href="#" onclick="topFunction()"><span>Return to top</span> <i class="bi bi-arrow-up-circle-fill"></i></a>
			</div>

			<div id="footer-menu" class="row">
				<div class="col-md-3">
					<h6>Documentation</h6>
					<div><a href="//wegov.nyc/news/">News</a></div>
					<div><a href="//wegov.nyc/events/">Events</a></div>
					<div><a href="#">Resources</a></div>
					<div><a href="//wegov.nyc/tools/">Tools</a></div>
				</div>
				<div class="col-md-3">
					<h6>Contribute</h6>
					<div><a href="https://www.notion.so/Contact-Us-54b075fa86ec47ebae48dae159s5afc2c">Write for us</a></div>
					<div><a href="#">Host an event</a></div>
					<div><a href="https://github.com/wegovnyc">Edit on Github</a></div>
				</div>
				<div class="col-md-3">
					<h6>About</h6>
					<div><a href="//wegov.nyc/about/">About us</a></div>
					<div><a href="#">Site Policies</a></div>
				</div>
				<div class="col-md-3">
					<h6>Social</h6>
					<div><a href="#">Twitter</a></div>
					<div><a href="#">Facebook</a></div>
					<div><a href="https://join.slack.com/t/wegovnyc/shared_invite/zt-jftnat8l-ahmZuhd73E0Hl8lEESY4JQ">Slack</a></div>
					<div><a href="#">RSS</a></div>
					<div><a href="#">Email us</a></div>
				</div>
			</div>

			<div id="footer-black">
				<a href="{{ route('root') }}">
				    <img src="/img/we-gov-logo-white.png" alt="" style="max-height:60px; margin-top:2px;">
			    </a>
				<div class="d-inline middle_text">
				    <a href="{{ route('root') }}">WeGovNYC</a> is a project of <a href="https://sarapis.org/">Sarapis</a>, a 501.c.3 nonprofit.
				</div>
				<div class="d-inline">
                    <a href="#"><img src="/img/cc.xlarge.png" height="60" alt=""></a>
                    <a href="#" style="margin: 0 10px;"><img src="/img/by.xlarge.png" height="60" alt=""></a>
                    <a href="#"><img src="/img/sa.xlarge.png" height="60" alt=""></a>
				</div>
			</div>
		</footer>
    </div>
</body>
</html>
