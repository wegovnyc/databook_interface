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

    <!-- Styles -->
	<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/jq-3.3.1/dt-1.10.23/r-2.2.7/sp-1.2.2/sl-1.3.1/datatables.min.css"/>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
	
	<style>
		select.filter {
		    border: 1px solid #aaa;
			border-radius: 3px;
			background-color: transparent;
			padding: 4px;
			margin-right: 16px;
			width: 100%;
		}
		select.filter-top {
		    border: 1px solid #aaa;
			border-radius: 3px;
			background-color: transparent;
			padding: 4px;
			margin-right: 8px;
			width: 47%;
		}
		.toolbar {
			display:inline-block;
			width: 70%;
		}
		th.filter {
			padding: 10px 4px 6px 4px !important;
		}
		td.details-control {
			background: url('/img/details_open.png') no-repeat center center;
			cursor: pointer;
		}
		tr.shown td.details-control {
			background: url('/img/details_close.png') no-repeat center center;
		}
		a.toggle-vis {
			cursor: pointer;
			color: #3174c7;
			text-decoration: none;
		}
		.toggle-box {
			padding: 10px 0px 20px;
		}
		#top-menu .nav-link {
			color: white!important;
		}
		.active>.nav-link {
			font-weight: 600!important;
		}
		#top-menu {
			background: #112e51;
		}
		
		.org-header {
			background: #e9ecef;
		}
		#org-header {
			height: 5em;
			width:100%;
		}
		.org-logo {
			padding: 12px;
			max-height: 4em;
			max-width: 12em;
		}
		#org-header h4 {
			padding: 10px 20px;
		}
		.icon {
			top: -2px; 
			position:relative;
			color: #ccc;
		}
		.buttons-colvis {
			margin-right: 20px!important;
			position: relative;
			top: -2px;
		}
		#navbar-nav {
			display: flex;
			justify-content: space-between;
		}
		
		#newsletter-subs {
			height: 8em;
			width:100%;
			background: #112e51;
			margin: 1em 0;
			position:relative;
			padding: 1em;
		}
		#newsletter-subs div.row {
			width: 100%;
			position: absolute;
			top: 50%;
		    -ms-transform: translateY(-50%);
		    transform: translateY(-50%);			
		}
		#newsletter-subs label, #newsletter-subs div.col-form-label {
			color: white;
			font-size:20px;
			font-weigth:600px;
		}
		
		#return-to-top {
			padding: 0 1rem!important;
		}
		
		#footer-menu {
			width:100%;
			margin: 1em 0 0 0;
			padding: 1em;
			background: #e9ecef;
		}
		#footer-menu a {
			display:block;
			padding: 0.3em 0;
		}

		#footer-black {
			width:100%;
			height: 4em;
			margin: 0;
			padding: 0 2px 0 4px;
			background: black;
			display: flex;
			justify-content: space-between;
		}
		
		#orgsTable .card {
			height: 350px;
		}
		#orgsTable .card img {
			max-width: 250px;
			max-height: 120px;
			margin:0 auto 20px;
		}
		
	</style>
	<script async src="https://cse.google.com/cse.js?cx=2b80c98605cf9ab55"></script>
</head>

<body>
    <div id="app" class="container">
        <header>
            @yield('menubar')
        </header>

        <main class="pb-1">
            @yield('content')
        </main>
		
		<footer>
			<div id="newsletter-subs">
			  <div class="form-group row">
				<label for="newsletter-email" class="col-sm-6 col-form-label">Stay up date with our community-powered newsletter.</label>
				<div class="col-sm-6" style="position:relative;top:-10px;">
					<small style="color:white;">Your email address</small><br/>
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
				<a href="#" onclick="topFunction()">Return to top <i class="bi bi-arrow-up-circle-fill"></i></a>
			</div>
			
			<div id="footer-menu" class="row">
				<div class="col-3">
					<h6>Documentation</h6>
					<div><a href="#">News</a></div>
					<div><a href="#">Events</a></div>
					<div><a href="#">Resources</a></div>
					<div><a href="#">Tools</a></div>
				</div>
				
				<div class="col-3">
					<h6>Contribute</h6>
					<div><a href="#">Write for us</a></div>
					<div><a href="#">Host an event</a></div>
					<div><a href="#">Edit on Github</a></div>
				</div>
				
				<div class="col-3">
					<h6>About</h6>
					<div><a href="#">About us</a></div>
					<div><a href="#">Site Policies</a></div>
				</div>
				
				<div class="col-3">
					<h6>Social</h6>
					<div><a href="#">Twitter</a></div>
					<div><a href="#">Facebook</a></div>
					<div><a href="#">Slack</a></div>
					<div><a href="#">RSS</a></div>
					<div><a href="#">Email us</a></div>
				</div>
				
			</div>
			
			<div id="footer-black">
				<a href="{{ route('root') }}">
				  <img src="/img/we-gov-logo-white.png" alt="" style="max-height:60px; margin-top:2px;">
			    </a>
				<div class="d-inline" style="margin:auto; color:white;">
				  <a href="{{ route('root') }}">WeGovNYC</a> is a project of <a href="https://sarapis.org/">Sarapis</a>, a 501.c.3 nonprofit.
				</div>
				<div class="d-inline" style="margin:auto 2px;">
				  <a href="#"><img src="/img/cc.xlarge.png" width="50" height="50" alt=""></a>
				  <a href="#"><img src="/img/by.xlarge.png" width="50" height="50" alt=""></a>
				  <a href="#"><img src="/img/sa.xlarge.png" width="50" height="50" alt=""></a>
				</div>

			</div>
		</footer>
    </div>
</body>
</html>
