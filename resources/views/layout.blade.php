<!doctype html>
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
			width: 100%;
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
		
	</style>

</head>

<body>
    <div id="app" class="container">
        <header>
            @yield('menubar')
        </header>

        <main class="py-3">
            @yield('content')
        </main>
    </div>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
</html>
